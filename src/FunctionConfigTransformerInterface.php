<?php

declare(strict_types=1);

namespace ChristianBrown\CloudFunction;

interface FunctionConfigTransformerInterface
{
    public const ENV_DEBUG = 'DEBUG';
    public const ENV_K_REVISION = 'K_REVISION';
    public const ENV_REQUIRED_HEADER_KEY = 'REQUIRED_HEADER_KEY';
    public const ENV_REQUIRED_HEADER_VALUE = 'REQUIRED_HEADER_VALUE';
    public const ENV_REQUIRED_ORIGIN = 'REQUIRED_ORIGIN';

    public function transform(array $env): FunctionConfigInterface;
}
