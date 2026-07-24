<?php

declare(strict_types=1);

namespace ChristianBrown\CloudRunFunction;

use GuzzleHttp\Psr7\Response;

use function time;

abstract class AbstractJsonResponse extends Response implements ResponseInterface
{
    /**
     * @phpstan-param mixed[] $data
     */
    public function __construct(?FunctionConfigInterface $functionConfig, array $data = [], bool $success = true, ?string $error = null, int $statusCode = self::STATUS_OK, ?string $requestOrigin = null)
    {
        $time = time();

        $bodyBuilder = new ResponseBodyBuilder();
        $bodyJson = $bodyBuilder->build($data, $functionConfig, $success, $error, $time);
        [$body, $success, $statusCode] = $bodyBuilder->encode($bodyJson, $success, $statusCode);

        $corsHeaderBuilder = new CorsHeaderBuilder(new AllowOriginResolver());
        $headers = $corsHeaderBuilder->build(self::HEADERS, $functionConfig, $requestOrigin);

        $cacheHeaderBuilder = new CacheHeaderBuilder();
        $headers = $cacheHeaderBuilder->build($headers, $functionConfig, $success);

        parent::__construct($statusCode, $headers, $body);
    }
}
