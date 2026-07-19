<?php

declare(strict_types=1);

namespace ChristianBrown\GcpFunction;

use function in_array;
use function is_string;
use function parse_url;

use const PHP_URL_HOST;

final class AllowOriginResolver implements AllowOriginResolverInterface
{
    public function resolve(string $requiredOrigin, ?string $requestOrigin, bool $debug): string
    {
        if (null === $requestOrigin) {
            return $requiredOrigin;
        }
        // Reflecting a localhost origin is a development convenience only; never
        // do it outside debug so production always pins the configured origin.
        if (!$debug) {
            return $requiredOrigin;
        }
        if (!$this->isLocalOrigin($requestOrigin)) {
            return $requiredOrigin;
        }

        return $requestOrigin;
    }

    private function isLocalOrigin(string $origin): bool
    {
        if (empty($origin)) {
            return false;
        }

        $host = parse_url($origin, PHP_URL_HOST);
        if (!is_string($host)) {
            return false;
        }

        return in_array($host, ResponseInterface::HOSTS_LOCAL, true);
    }
}
