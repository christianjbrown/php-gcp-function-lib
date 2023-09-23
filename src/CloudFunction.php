<?php

declare(strict_types=1);

namespace ChristianBrown\CloudFunction;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ChristianBrown\UserFriendlyException\UserFriendlyExceptionInterface;
use Throwable;

final class CloudFunction implements CloudFunctionInterface
{
    private CloudFunctionDataProviderInterface $dataProvider;
    private array $env;
    private CloudFunctionRequestConfigTransformerInterface $requestConfigTransformer;

    public function __construct(CloudFunctionDataProviderInterface $dataProvider, array $env)
    {
        $this->requestConfigTransformer = new CloudFunctionRequestConfigTransformer();
        $this->dataProvider = $dataProvider;
        $this->env = $env;
    }

    public function run(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $requestConfig = $this->requestConfigTransformer->transform($this->env);
        } /* @noinspection PhpUnusedLocalVariableInspection */ catch (Throwable $exception) {
            return new CloudFunctionJsonErrorResponse(null, self::ERROR_UNHANDLED);
        }

        try {
            $isAuthorized = self::isAuthorized($request, $requestConfig);
            if (!$isAuthorized) {
                return new CloudFunctionJsonErrorResponse($requestConfig, self::ERROR_NOT_AUTHORIZED, 401);
            }

            $data = $this->dataProvider->getData($this->env, $request);

            $response = new CloudFunctionJsonSuccessResponse($requestConfig, $data);
        } catch (UserFriendlyExceptionInterface $exception) {
            $response = new CloudFunctionJsonErrorResponse($requestConfig, $exception->getMessage());
        } catch (Throwable $exception) {
            if ($requestConfig->getDebug()) {
                $response = new CloudFunctionJsonErrorResponse($requestConfig, $exception->getMessage());
            } else {
                $response = new CloudFunctionJsonErrorResponse($requestConfig, self::ERROR_UNHANDLED);
            }
        }

        return $response;
    }

    private static function isAuthorized(ServerRequestInterface $request, CloudFunctionRequestConfigInterface $config): bool
    {
        $authorized = true;
        $requiredHeaderKey = $config->getRequiredHeaderKey();
        if ($requiredHeaderKey) {
            $requiredHeaderValue = $config->getRequiredHeaderValue();
            if (!$request->hasHeader($requiredHeaderKey) || [$requiredHeaderValue] !== $request->getHeader($requiredHeaderKey)) {
                $authorized = false;
            }
        }

        return $authorized;
    }
}
