<?php

declare(strict_types=1);

namespace ChristianBrown\CloudFunction;

interface CloudFunctionJsonErrorResponseInterface extends CloudFunctionResponseInterface
{
    public const DEFAULT_ERROR_STATUS_CODE = 500;
}
