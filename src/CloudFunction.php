<?php

declare(strict_types=1);

namespace ChristianBrown\CloudFunction;

use ChristianBrown\UserFriendlyException\UserFriendlyExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

final class CloudFunction implements CloudFunctionInterface
{
    private DataProviderInterface $dataProvider;
    private array $env;
    private ConfigTransformerInterface $requestConfigTransformer;

    public function __construct(DataProviderInterface $dataProvider, array $env)
    {
        $this->requestConfigTransformer = new ConfigTransformer();
        $this->dataProvider = $dataProvider;
        $this->env = $env;
    }

    public function run(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $requestConfig = $this->requestConfigTransformer->transform($this->env);
        } /* @noinspection PhpUnusedLocalVariableInspection */ catch (Throwable $exception) {
            return new JsonErrorResponse(null, self::ERROR_UNHANDLED);
        }

        try {
            $isAuthorized = self::isAuthorized($request, $requestConfig);
            if (!$isAuthorized) {
                return new JsonErrorResponse($requestConfig, self::ERROR_NOT_AUTHORIZED, 401);
            }

            $data = $this->dataProvider->getData($this->env, $request);

            $response = new JsonSuccessResponse($requestConfig, $data);
        } catch (UserFriendlyExceptionInterface $exception) {
            $response = new JsonErrorResponse($requestConfig, $exception->getMessage());
        } catch (Throwable $exception) {
            if ($requestConfig->getDebug()) {
                $response = new JsonErrorResponse($requestConfig, $exception->getMessage());
            } else {
                $response = new JsonErrorResponse($requestConfig, self::ERROR_UNHANDLED);
            }
        }

        return $response;
    }

    private static function isAuthorized(ServerRequestInterface $request, RequestConfigInterface $config): bool
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
