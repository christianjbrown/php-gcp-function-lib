<?php

declare(strict_types=1);

namespace ChristianBrown\GcpFunction;

use function implode;
use function sprintf;

final class CacheHeaderBuilder implements CacheHeaderBuilderInterface
{
    /**
     * @param array<string, string> $headers
     *
     * @return array<string, string>
     */
    public function build(array $headers, ?FunctionConfigInterface $functionConfig, bool $success): array
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
        $headers = self::appendSurrogateKey($headers, $functionConfig->getSurrogateKey());

        return $headers;
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
            $headers[ResponseInterface::HEADER_KEY_CACHE_CONTROL] = implode(', ', $cacheControlParts);
        }

        return $headers;
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
            $staleIfError = sprintf(self::DIRECTIVE_STALE_IF_ERROR_SPRINTF, $ttl);
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
            $staleWhileRevalidate = sprintf(self::DIRECTIVE_STALE_WHILE_REVALIDATE_SPRINTF, $ttl);
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
            $headers[ResponseInterface::HEADER_KEY_SURROGATE_CONTROL] = implode(', ', $surrogateControlParts);
        }

        return $headers;
    }

    /**
     * @param array<string, string> $headers
     *
     * @return array<string, string>
     */
    private static function appendSurrogateKey(array $headers, ?string $surrogateKey): array
    {
        if ($surrogateKey) {
            $headers[ResponseInterface::HEADER_KEY_SURROGATE_KEY] = $surrogateKey;
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
            $maxAge = sprintf(self::DIRECTIVE_MAX_AGE_SPRINTF, $ttl);
            $cacheControlParts[] = sprintf(self::DIRECTIVE_S_MAXAGE_SPRINTF, $ttl);
            $cacheControlParts[] = $maxAge;
            $surrogateControlParts[] = $maxAge;
        }

        return [$cacheControlParts, $surrogateControlParts];
    }
}
