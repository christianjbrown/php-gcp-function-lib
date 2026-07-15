<?php

declare(strict_types=1);

namespace ChristianBrown\GcpFunction;

use RuntimeException;

use function is_numeric;
use function is_string;
use function sprintf;

final class FunctionConfigTransformer implements FunctionConfigTransformerInterface
{
    /**
     * @param mixed[] $env
     */
    public function transform(array $env): FunctionConfigInterface
    {
        // Required values

        if (empty($env[self::ENV_K_REVISION])) {
            throw new RuntimeException(sprintf('%s not set or not a string', self::ENV_K_REVISION));
        }
        if (!is_string($env[self::ENV_K_REVISION])) {
            throw new RuntimeException(sprintf('%s not set or not a string', self::ENV_K_REVISION));
        }
        $kRevision = $env[self::ENV_K_REVISION];

        $config = new FunctionConfig($kRevision);

        // Optional values

        self::applyAllowUnauthenticated($config, $env);
        self::applyDebug($config, $env);
        self::applyRequiredHeaderKey($config, $env);
        self::applyRequiredHeaderValue($config, $env);
        self::applyRequiredOrigin($config, $env);
        self::applyUseCacheTtl($config, $env);
        self::applyUseCacheButRequestTtl($config, $env);
        self::applyUseCacheIfErrorTtl($config, $env);

        return $config;
    }

    private static function applyAllowUnauthenticated(FunctionConfigInterface $config, array $env): void
    {
        if (empty($env[self::ENV_ALLOW_UNAUTHENTICATED])) {
            return;
        }
        if ('true' !== $env[self::ENV_ALLOW_UNAUTHENTICATED]) {
            return;
        }
        $config->setAllowUnauthenticated(true);
    }

    private static function applyDebug(FunctionConfigInterface $config, array $env): void
    {
        if (empty($env[self::ENV_DEBUG])) {
            return;
        }
        if ('true' !== $env[self::ENV_DEBUG]) {
            return;
        }
        $config->setDebug(true);
    }

    private static function applyRequiredHeaderKey(FunctionConfigInterface $config, array $env): void
    {
        if (empty($env[self::ENV_REQUIRED_HEADER_KEY])) {
            return;
        }
        if (!is_string($env[self::ENV_REQUIRED_HEADER_KEY])) {
            return;
        }
        $config->setRequiredHeaderKey($env[self::ENV_REQUIRED_HEADER_KEY]);
    }

    private static function applyRequiredHeaderValue(FunctionConfigInterface $config, array $env): void
    {
        if (empty($env[self::ENV_REQUIRED_HEADER_VALUE])) {
            return;
        }
        if (!is_string($env[self::ENV_REQUIRED_HEADER_VALUE])) {
            return;
        }
        $config->setRequiredHeaderValue($env[self::ENV_REQUIRED_HEADER_VALUE]);
    }

    private static function applyRequiredOrigin(FunctionConfigInterface $config, array $env): void
    {
        if (empty($env[self::ENV_REQUIRED_ORIGIN])) {
            return;
        }
        if (!is_string($env[self::ENV_REQUIRED_ORIGIN])) {
            return;
        }
        $config->setRequiredOrigin($env[self::ENV_REQUIRED_ORIGIN]);
    }

    private static function applyUseCacheButRequestTtl(FunctionConfigInterface $config, array $env): void
    {
        if (empty($env[self::ENV_USE_CACHE_BUT_REQUEST_TTL])) {
            return;
        }
        if (!is_numeric($env[self::ENV_USE_CACHE_BUT_REQUEST_TTL])) {
            return;
        }
        $config->setUseCacheButRequestTtl((int) $env[self::ENV_USE_CACHE_BUT_REQUEST_TTL]);
    }

    private static function applyUseCacheIfErrorTtl(FunctionConfigInterface $config, array $env): void
    {
        if (empty($env[self::ENV_USE_CACHE_IF_ERROR_TTL])) {
            return;
        }
        if (!is_numeric($env[self::ENV_USE_CACHE_IF_ERROR_TTL])) {
            return;
        }
        $config->setUseCacheIfErrorTtl((int) $env[self::ENV_USE_CACHE_IF_ERROR_TTL]);
    }

    private static function applyUseCacheTtl(FunctionConfigInterface $config, array $env): void
    {
        if (empty($env[self::ENV_USE_CACHE_TTL])) {
            return;
        }
        if (!is_numeric($env[self::ENV_USE_CACHE_TTL])) {
            return;
        }
        $config->setUseCacheTtl((int) $env[self::ENV_USE_CACHE_TTL]);
    }
}
