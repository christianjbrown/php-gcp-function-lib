<?php

declare(strict_types=1);

namespace ChristianBrown\CloudRunFunction;

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

        self::applyAllowLocalOrigins($config, $env);
        self::applyAllowUnauthenticated($config, $env);
        self::applyDebug($config, $env);
        self::applyRequiredHeaderKey($config, $env);
        self::applyRequiredHeaderValue($config, $env);
        self::applyRequiredOrigin($config, $env);
        self::applySurrogateKey($config, $env);
        self::applyUseCacheTtl($config, $env);
        self::applyUseCacheButRequestTtl($config, $env);
        self::applyUseCacheIfErrorTtl($config, $env);

        return $config;
    }

    /**
     * @phpstan-param mixed[] $env
     */
    private static function applyAllowLocalOrigins(FunctionConfigInterface $config, array $env): void
    {
        if (empty($env[self::ENV_ALLOW_LOCAL_ORIGINS])) {
            return;
        }
        if ('true' !== $env[self::ENV_ALLOW_LOCAL_ORIGINS]) {
            return;
        }
        $config->setAllowLocalOrigins(true);
    }

    /**
     * @phpstan-param mixed[] $env
     */
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

    /**
     * @phpstan-param mixed[] $env
     */
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

    /**
     * @phpstan-param mixed[] $env
     */
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

    /**
     * @phpstan-param mixed[] $env
     */
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

    /**
     * @phpstan-param mixed[] $env
     */
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

    /**
     * @phpstan-param mixed[] $env
     */
    private static function applySurrogateKey(FunctionConfigInterface $config, array $env): void
    {
        if (empty($env[self::ENV_SURROGATE_KEY])) {
            return;
        }
        if (!is_string($env[self::ENV_SURROGATE_KEY])) {
            return;
        }
        $config->setSurrogateKey($env[self::ENV_SURROGATE_KEY]);
    }

    /**
     * @phpstan-param mixed[] $env
     */
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

    /**
     * @phpstan-param mixed[] $env
     */
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

    /**
     * @phpstan-param mixed[] $env
     */
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
