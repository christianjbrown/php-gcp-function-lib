<?php

declare(strict_types=1);

namespace ChristianBrown\CloudFunction;

final class JsonSuccessResponse extends AbstractJsonResponse implements JsonSuccessResponseInterface
{
    public function __construct(?FunctionConfigInterface $functionConfig, array $data = [], int $statusCode = 200)
    {
        parent::__construct($functionConfig, $data, true, null, $statusCode);
    }
}
