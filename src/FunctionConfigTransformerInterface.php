<?php

declare(strict_types=1);

namespace ChristianBrown\GcpFunction;

interface FunctionConfigTransformerInterface
{
    public const string ENV_DEBUG = 'DEBUG';
    public const string ENV_K_REVISION = 'K_REVISION';
    public const string ENV_REQUIRED_HEADER_KEY = 'REQUIRED_HEADER_KEY';
    public const string ENV_REQUIRED_HEADER_VALUE = 'REQUIRED_HEADER_VALUE';
    public const string ENV_REQUIRED_ORIGIN = 'REQUIRED_ORIGIN';
    public const string ENV_USE_CACHE_BUT_REQUEST_TTL = 'USE_CACHE_BUT_REQUEST_TTL';
    public const string ENV_USE_CACHE_IF_ERROR_TTL = 'USE_CACHE_IF_ERROR_TTL';
    public const string ENV_USE_CACHE_TTL = 'USE_CACHE_TTL';

    /**
     * @param mixed[] $env
     */
    public function transform(array $env): FunctionConfigInterface;
}
