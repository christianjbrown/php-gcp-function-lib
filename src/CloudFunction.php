<?php

declare(strict_types=1);

namespace ChristianBrown\CloudFunction;

use ChristianBrown\UserFriendlyException\UserFriendlyExceptionInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

final class CloudFunction implements CloudFunctionInterface
{
    private DataProviderInterface $dataProvider;
    private FunctionConfigInterface $functionConfig;

    public function __construct(DataProviderInterface $dataProvider, FunctionConfigInterface $functionConfig)
    {
        $this->dataProvider = $dataProvider;
        $this->functionConfig = $functionConfig;
    }

    public function run(ServerRequestInterface $request): ResponseInterface
    {
        $requestOrigin = $request->getHeaderLine(ResponseInterface::HEADER_KEY_ORIGIN);

        try {
            return $this->handle($request, $requestOrigin);
        } catch (UserFriendlyExceptionInterface $exception) {
            return new JsonErrorResponse($this->functionConfig, $exception->getMessage(), JsonErrorResponseInterface::DEFAULT_ERROR_STATUS_CODE, $requestOrigin);
        } catch (Throwable $exception) {
            return $this->buildUnhandledResponse($exception, $requestOrigin);
        }
    }

    private function buildUnhandledResponse(Throwable $exception, string $requestOrigin): ResponseInterface
    {
        if ($this->functionConfig->getDebug()) {
            return new JsonErrorResponse($this->functionConfig, $exception->getMessage(), JsonErrorResponseInterface::DEFAULT_ERROR_STATUS_CODE, $requestOrigin);
        }

        return new JsonErrorResponse($this->functionConfig, self::ERROR_UNHANDLED, JsonErrorResponseInterface::DEFAULT_ERROR_STATUS_CODE, $requestOrigin);
    }

    private function handle(ServerRequestInterface $request, string $requestOrigin): ResponseInterface
    {
        if (!self::isAuthorized($request, $this->functionConfig)) {
            return new JsonErrorResponse($this->functionConfig, self::ERROR_NOT_AUTHORIZED, 401, $requestOrigin);
        }

        $data = $this->dataProvider->getData($request);

        return new JsonSuccessResponse($this->functionConfig, $data, 200, $requestOrigin);
    }

    private static function isAuthorized(ServerRequestInterface $request, FunctionConfigInterface $config): bool
    {
        $requiredHeaderKey = $config->getRequiredHeaderKey();
        if (!$requiredHeaderKey) {
            return true;
        }
        if (!$request->hasHeader($requiredHeaderKey)) {
            return false;
        }

        return $config->getRequiredHeaderValue() === $request->getHeaderLine($requiredHeaderKey);
    }
}
