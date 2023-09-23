<?php

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

        if (empty($env[self::ENV_K_REVISION]) || !is_numeric($env[self::ENV_K_REVISION])) {
            throw new RuntimeException(sprintf('%s not set or not a number', self::ENV_K_REVISION));
        }
        $kRevision = (int) $env[self::ENV_K_REVISION];

        $config = new FunctionConfig($kRevision);

        // Optional values

        if (!empty($env[self::ENV_DEBUG]) && 'false' !== $env[self::ENV_DEBUG]) {
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

        return $config;
    }
}
