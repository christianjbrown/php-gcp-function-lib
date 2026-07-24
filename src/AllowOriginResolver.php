<?php

declare(strict_types=1);

namespace ChristianBrown\CloudRunFunction;

use function in_array;
use function is_string;
use function mb_trim;
use function parse_url;

use const PHP_URL_HOST;

final class AllowOriginResolver implements AllowOriginResolverInterface
{
    public function resolve(string $requiredOrigin, ?string $requestOrigin, bool $debug, bool $allowLocalOrigins): string
    {
        if (null === $requestOrigin) {
            return $requiredOrigin;
        }
        // Reflecting a loopback origin is a development convenience; only do it
        // when explicitly allowed (or in debug) so production otherwise always
        // pins the configured origin.
        if (!$debug) {
            if (!$allowLocalOrigins) {
                return $requiredOrigin;
            }
        }
        if (!self::isLocalOrigin($requestOrigin)) {
            return $requiredOrigin;
        }

        return $requestOrigin;
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

        // parse_url keeps the brackets around an IPv6 host (e.g. "[::1]"); strip
        // them so the loopback address matches HOSTS_LOCAL.
        return in_array(mb_trim($host, '[]'), ResponseInterface::HOSTS_LOCAL, true);
    }
}
