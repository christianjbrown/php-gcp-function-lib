<?php

// phpcs:disable Generic.Metrics.CyclomaticComplexity.TooHigh

declare(strict_types=1);

namespace ChristianBrown\CloudFunction;

use RuntimeException;

use function is_numeric;
use function is_string;
use function sprintf;

final class FunctionConfigTransformer implements FunctionConfigTransformerInterface
{
    public function transform(array $env): FunctionConfigInterface
    {
        // Required values

        if (empty($env[self::ENV_K_REVISION]) || !is_string($env[self::ENV_K_REVISION])) {
            throw new RuntimeException(sprintf('%s not set or not a string', self::ENV_K_REVISION));
        }
        $kRevision = $env[self::ENV_K_REVISION];

        $config = new FunctionConfig($kRevision);

        // Optional values

        if (!empty($env[self::ENV_DEBUG]) && 'true' === $env[self::ENV_DEBUG]) {
            $config->setDebug(true);
        }
        if (!empty($env[self::ENV_REQUIRED_HEADER_KEY]) && is_string($env[self::ENV_REQUIRED_HEADER_KEY])) {
            $config->setRequiredHeaderKey($env[self::ENV_REQUIRED_HEADER_KEY]);
        }
        if (!empty($env[self::ENV_REQUIRED_HEADER_VALUE]) && is_string($env[self::ENV_REQUIRED_HEADER_VALUE])) {
            $config->setRequiredHeaderValue($env[self::ENV_REQUIRED_HEADER_VALUE]);
        }
        if (!empty($env[self::ENV_REQUIRED_ORIGIN]) && is_string($env[self::ENV_REQUIRED_ORIGIN])) {
            $config->setRequiredOrigin($env[self::ENV_REQUIRED_ORIGIN]);
        }
        if (!empty($env[self::ENV_USE_CACHE_TTL]) && is_numeric($env[self::ENV_USE_CACHE_TTL])) {
            $config->setUseCacheTtl((int) $env[self::ENV_USE_CACHE_TTL]);
        }
        if (!empty($env[self::ENV_USE_CACHE_BUT_REQUEST_TTL]) && is_numeric($env[self::ENV_USE_CACHE_BUT_REQUEST_TTL])) {
            $config->setUseCacheButRequestTtl((int) $env[self::ENV_USE_CACHE_BUT_REQUEST_TTL]);
        }
        if (!empty($env[self::ENV_USE_CACHE_IF_ERROR_TTL]) && is_numeric($env[self::ENV_USE_CACHE_IF_ERROR_TTL])) {
            $config->setUseCacheIfErrorTtl((int) $env[self::ENV_USE_CACHE_IF_ERROR_TTL]);
        }

        return $config;
    }
}
