<?php

declare(strict_types=1);

namespace ChristianBrown\CloudRunFunction;

final class JsonErrorResponse extends AbstractJsonResponse implements JsonErrorResponseInterface
{
    public function __construct(?FunctionConfigInterface $functionConfig, ?string $error = null, int $statusCode = self::DEFAULT_ERROR_STATUS_CODE, ?string $requestOrigin = null)
    {
        parent::__construct($functionConfig, [], false, $error, $statusCode, $requestOrigin);
    }
}
