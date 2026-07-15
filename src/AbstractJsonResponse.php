<?php

// phpcs:disable Generic.Metrics.CyclomaticComplexity.MaxExceeded

declare(strict_types=1);

namespace ChristianBrown\CloudFunction;

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
    /**
     * @noinspection PhpUnusedLocalVariableInspection
     */
    public function __construct(?FunctionConfigInterface $functionConfig, array $data = [], bool $success = true, ?string $error = null, int $statusCode = 200, ?string $requestOrigin = null)
    {
        $time = time();

        $bodyJson = [
            self::RESPONSE_API_KEY_SUCCESS => $success,
            self::RESPONSE_API_KEY_TIMESTAMP_UNIX => $time,
            self::RESPONSE_API_KEY_TIMESTAMP_ISO8601 => gmdate('c', $time),
        ];
        if ($data) {
            $bodyJson[self::RESPONSE_API_KEY_DATA] = $data;
        }
        if ($functionConfig instanceof FunctionConfigInterface) {
            $bodyJson[self::RESPONSE_API_KEY_VERSION] = $functionConfig->getKrevision();
        }
        if (null !== $error) {
            $bodyJson[self::RESPONSE_API_KEY_ERROR] = $error;
        }

        ksort($bodyJson);

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
            $body = @json_encode($bodyJson, JSON_PRETTY_PRINT) ?: '';
        }

        $headers = self::HEADERS;
        if ($functionConfig instanceof FunctionConfigInterface) {
            $requiredOrigin = $functionConfig->getRequiredOrigin();
            if (!empty($requiredOrigin)) {
                $allowOrigin = $requiredOrigin;
                if (null !== $requestOrigin && self::isLocalOrigin($requestOrigin)) {
                    $allowOrigin = $requestOrigin;
                }
                $headers[self::HEADER_KEY_ALLOW_ORIGIN] = $allowOrigin;
                $varyList = [self::HEADER_VARY_ACCEPT_ENCODING, self::HEADER_VARY_ORIGIN];
                $requiredHeaderKey = $functionConfig->getRequiredHeaderKey();
                if ($requiredHeaderKey) {
                    $varyList[] = $requiredHeaderKey;
                }
                $headers[self::HEADER_KEY_VARY] = implode(',', $varyList);
            }
        }

        if ($success && $functionConfig instanceof FunctionConfigInterface) {
            $cacheControlParts = [];
            $surrogateControlParts = [];
            if ($functionConfig->getUseCacheTtl()) {
                $maxAge = sprintf('max-age=%d', $functionConfig->getUseCacheTtl());
                $cacheControlParts[] = sprintf('s-maxage=%d', $functionConfig->getUseCacheTtl());
                $cacheControlParts[] = $maxAge;
                $surrogateControlParts[] = $maxAge;
            }
            if ($functionConfig->getUseCacheButRequestTtl()) {
                $staleWhilstRevalidate = sprintf('stale-while-revalidate=%d', $functionConfig->getUseCacheButRequestTtl());
                $cacheControlParts[] = $staleWhilstRevalidate;
                $surrogateControlParts[] = $staleWhilstRevalidate;
            }
            if ($functionConfig->getUseCacheIfErrorTtl()) {
                $staleIfError = sprintf('stale-if-error=%d', $functionConfig->getUseCacheIfErrorTtl());
                $cacheControlParts[] = $staleIfError;
                $surrogateControlParts[] = $staleIfError;
            }
            if ($cacheControlParts) {
                $headers[self::HEADER_KEY_CACHE_CONTROL] = implode(', ', $cacheControlParts);
            }
            if ($surrogateControlParts) {
                $headers[self::HEADER_KEY_SURROGATE_CONTROL] = implode(', ', $surrogateControlParts);
            }
        }

        parent::__construct($statusCode, $headers, $body);
    }

    private static function isLocalOrigin(string $origin): bool
    {
        if (empty($origin)) {
            return false;
        }

        $host = parse_url($origin, PHP_URL_HOST);

        return is_string($host) && in_array($host, self::HOSTS_LOCAL, true);
    }
}
