<?php

declare(strict_types=1);

namespace ChristianBrown\CloudFunction;

interface JsonErrorResponseInterface extends ResponseInterface
{
    public const int DEFAULT_ERROR_STATUS_CODE = 500;
}
