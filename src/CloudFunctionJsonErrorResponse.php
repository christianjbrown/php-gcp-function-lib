<?php

declare(strict_types=1);

namespace ChristianBrown\CloudFunction;

final class CloudFunctionJsonErrorResponse extends AbstractCloudFunctionResponse implements CloudFunctionJsonErrorResponseInterface
{
    public function __construct(?CloudFunctionRequestConfigInterface $requestConfig, ?string $error = null, int $statusCode = self::DEFAULT_ERROR_STATUS_CODE)
    {
        parent::__construct($requestConfig, [], false, $error, $statusCode);
    }
}
