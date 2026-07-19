<?php

declare(strict_types=1);

namespace ChristianBrown\GcpFunction;

interface JsonErrorResponseInterface extends ResponseInterface
{
    public const int DEFAULT_ERROR_STATUS_CODE = self::STATUS_INTERNAL_SERVER_ERROR;
}
