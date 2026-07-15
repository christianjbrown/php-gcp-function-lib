<?php

declare(strict_types=1);

namespace ChristianBrown\GcpFunction;

use GuzzleHttp\Psr7\Response;
use JsonException;

use function gmdate;
use function implode;
use function in_array;
use function is_string;
use function parse_url;
use function sprintf;

use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const PHP_URL_HOST;

abstract class AbstractJsonResponse extends Response implements ResponseInterface
{
    public function __construct(?FunctionConfigInterface $functionConfig, array $data = [], bool $success = true, ?string $error = null, int $statusCode = 200, ?string $requestOrigin = null)
    {
        $time = time();

        $bodyJson = self::buildBody($functionConfig, $data, $success, $error, $time);

        [$body, $success, $statusCode] = self::encode($bodyJson, $success, $statusCode);

        $headers = self::buildCorsHeaders(self::HEADERS, $functionConfig, $requestOrigin);
        $headers = self::buildCacheHeaders($headers, $functionConfig, $success);

        parent::__construct($statusCode, $headers, $body);
    }

    /**
     * @param array<string, string> $headers
     * @param string[]              $cacheControlParts
     *
     * @return array<string, string>
     */
    private static function appendCacheControl(array $headers, array $cacheControlParts): array
    {
        if ($cacheControlParts) {
            $headers[self::HEADER_KEY_CACHE_CONTROL] = implode(', ', $cacheControlParts);
        }

        return $headers;
    }

    /**
     * @param mixed[] $bodyJson
     * @param mixed[] $data
     *
     * @return mixed[]
     */
    private static function appendData(array $bodyJson, array $data): array
    {
        if ($data) {
            $bodyJson[self::RESPONSE_API_KEY_DATA] = $data;
        }

        return $bodyJson;
    }

    /**
     * @param mixed[] $bodyJson
     *
     * @return mixed[]
     */
    private static function appendError(array $bodyJson, ?string $error): array
    {
        if (null !== $error) {
            $bodyJson[self::RESPONSE_API_KEY_ERROR] = $error;
        }

        return $bodyJson;
    }

    /**
     * @param string[] $cacheControlParts
     * @param string[] $surrogateControlParts
     *
     * @return array{0: string[], 1: string[]}
     */
    private static function appendStaleIfError(array $cacheControlParts, array $surrogateControlParts, ?int $ttl): array
    {
        if ($ttl) {
            $staleIfError = sprintf('stale-if-error=%d', $ttl);
            $cacheControlParts[] = $staleIfError;
            $surrogateControlParts[] = $staleIfError;
        }

        return [$cacheControlParts, $surrogateControlParts];
    }

    /**
     * @param string[] $cacheControlParts
     * @param string[] $surrogateControlParts
     *
     * @return array{0: string[], 1: string[]}
     */
    private static function appendStaleWhileRevalidate(array $cacheControlParts, array $surrogateControlParts, ?int $ttl): array
    {
        if ($ttl) {
            $staleWhileRevalidate = sprintf('stale-while-revalidate=%d', $ttl);
            $cacheControlParts[] = $staleWhileRevalidate;
            $surrogateControlParts[] = $staleWhileRevalidate;
        }

        return [$cacheControlParts, $surrogateControlParts];
    }

    /**
     * @param array<string, string> $headers
     * @param string[]              $surrogateControlParts
     *
     * @return array<string, string>
     */
    private static function appendSurrogateControl(array $headers, array $surrogateControlParts): array
    {
        if ($surrogateControlParts) {
            $headers[self::HEADER_KEY_SURROGATE_CONTROL] = implode(', ', $surrogateControlParts);
        }

        return $headers;
    }

    /**
     * @param string[] $cacheControlParts
     * @param string[] $surrogateControlParts
     *
     * @return array{0: string[], 1: string[]}
     */
    private static function appendUseCacheTtl(array $cacheControlParts, array $surrogateControlParts, ?int $ttl): array
    {
        if ($ttl) {
            $maxAge = sprintf('max-age=%d', $ttl);
            $cacheControlParts[] = sprintf('s-maxage=%d', $ttl);
            $cacheControlParts[] = $maxAge;
            $surrogateControlParts[] = $maxAge;
        }

        return [$cacheControlParts, $surrogateControlParts];
    }

    /**
     * @param mixed[] $bodyJson
     *
     * @return mixed[]
     */
    private static function appendVersion(array $bodyJson, ?FunctionConfigInterface $functionConfig): array
    {
        if ($functionConfig instanceof FunctionConfigInterface) {
            $bodyJson[self::RESPONSE_API_KEY_VERSION] = $functionConfig->getKrevision();
        }

        return $bodyJson;
    }

    /**
     * @return mixed[]
     */
    private static function buildBody(?FunctionConfigInterface $functionConfig, array $data, bool $success, ?string $error, int $time): array
    {
        $bodyJson = [
            self::RESPONSE_API_KEY_SUCCESS => $success,
            self::RESPONSE_API_KEY_TIMESTAMP_UNIX => $time,
            self::RESPONSE_API_KEY_TIMESTAMP_ISO8601 => gmdate('c', $time),
        ];

        $bodyJson = self::appendData($bodyJson, $data);
        $bodyJson = self::appendVersion($bodyJson, $functionConfig);
        $bodyJson = self::appendError($bodyJson, $error);

        ksort($bodyJson);

        return $bodyJson;
    }

    /**
     * @param array<string, string> $headers
     *
     * @return array<string, string>
     */
    private static function buildCacheHeaders(array $headers, ?FunctionConfigInterface $functionConfig, bool $success): array
    {
        if (!$success) {
            return $headers;
        }
        if (!$functionConfig instanceof FunctionConfigInterface) {
            return $headers;
        }

        $cacheControlParts = [];
        $surrogateControlParts = [];
        [$cacheControlParts, $surrogateControlParts] = self::appendUseCacheTtl($cacheControlParts, $surrogateControlParts, $functionConfig->getUseCacheTtl());
        [$cacheControlParts, $surrogateControlParts] = self::appendStaleWhileRevalidate($cacheControlParts, $surrogateControlParts, $functionConfig->getUseCacheButRequestTtl());
        [$cacheControlParts, $surrogateControlParts] = self::appendStaleIfError($cacheControlParts, $surrogateControlParts, $functionConfig->getUseCacheIfErrorTtl());

        $headers = self::appendCacheControl($headers, $cacheControlParts);
        $headers = self::appendSurrogateControl($headers, $surrogateControlParts);

        return $headers;
    }

    /**
     * @param array<string, string> $headers
     *
     * @return array<string, string>
     */
    private static function buildCorsHeaders(array $headers, ?FunctionConfigInterface $functionConfig, ?string $requestOrigin): array
    {
        if (!$functionConfig instanceof FunctionConfigInterface) {
            return $headers;
        }

        $requiredOrigin = $functionConfig->getRequiredOrigin();
        if (empty($requiredOrigin)) {
            return $headers;
        }

        $headers[self::HEADER_KEY_ALLOW_ORIGIN] = self::resolveAllowOrigin($requiredOrigin, $requestOrigin);

        $varyList = [self::HEADER_VARY_ACCEPT_ENCODING, self::HEADER_VARY_ORIGIN];
        $requiredHeaderKey = $functionConfig->getRequiredHeaderKey();
        if ($requiredHeaderKey) {
            $varyList[] = $requiredHeaderKey;
        }
        $headers[self::HEADER_KEY_VARY] = implode(',', $varyList);

        return $headers;
    }

    /**
     * @param mixed[] $bodyJson
     *
     * @return array{0: string, 1: bool, 2: int}
     */
    private static function encode(array $bodyJson, bool $success, int $statusCode): array
    {
        try {
            $body = json_encode($bodyJson, JSON_THROW_ON_ERROR + JSON_PRETTY_PRINT);
        } catch (JsonException $exception) {
            $success = false;
            $statusCode = 500;
            $bodyJson[self::RESPONSE_API_KEY_SUCCESS] = false;
            unset($bodyJson[self::RESPONSE_API_KEY_DATA]);
            $bodyJson[self::RESPONSE_API_KEY_ERROR] = self::ERROR_JSON_ENCODING;
            /**
             * @noinspection PhpUsageOfSilenceOperatorInspection
             */
            $body = (string) @json_encode($bodyJson, JSON_PRETTY_PRINT);
        }

        return [$body, $success, $statusCode];
    }

    private static function isLocalOrigin(string $origin): bool
    {
        if (empty($origin)) {
            return false;
        }

        $host = parse_url($origin, PHP_URL_HOST);
        if (!is_string($host)) {
            return false;
        }

        return in_array($host, self::HOSTS_LOCAL, true);
    }

    private static function resolveAllowOrigin(string $requiredOrigin, ?string $requestOrigin): string
    {
        if (null === $requestOrigin) {
            return $requiredOrigin;
        }
        if (!self::isLocalOrigin($requestOrigin)) {
            return $requiredOrigin;
        }

        return $requestOrigin;
    }
}
