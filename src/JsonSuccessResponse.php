<?php

declare(strict_types=1);

namespace ChristianBrown\GcpFunction;

final class JsonSuccessResponse extends AbstractJsonResponse implements JsonSuccessResponseInterface
{
    public function __construct(?FunctionConfigInterface $functionConfig, array $data = [], int $statusCode = self::STATUS_OK, ?string $requestOrigin = null)
    {
        parent::__construct($functionConfig, $data, true, null, $statusCode, $requestOrigin);
    }
}
