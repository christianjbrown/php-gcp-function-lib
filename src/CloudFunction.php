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
            $isAuthorized = self::isAuthorized($request, $this->functionConfig);
            if (!$isAuthorized) {
                return new JsonErrorResponse($this->functionConfig, self::ERROR_NOT_AUTHORIZED, 401, $requestOrigin);
            }

            $data = $this->dataProvider->getData($request);

            $response = new JsonSuccessResponse($this->functionConfig, $data, 200, $requestOrigin);
        } catch (UserFriendlyExceptionInterface $exception) {
            $response = new JsonErrorResponse($this->functionConfig, $exception->getMessage(), JsonErrorResponseInterface::DEFAULT_ERROR_STATUS_CODE, $requestOrigin);
        } catch (Throwable $exception) {
            if ($this->functionConfig->getDebug()) {
                $response = new JsonErrorResponse($this->functionConfig, $exception->getMessage(), JsonErrorResponseInterface::DEFAULT_ERROR_STATUS_CODE, $requestOrigin);
            } else {
                $response = new JsonErrorResponse($this->functionConfig, self::ERROR_UNHANDLED, JsonErrorResponseInterface::DEFAULT_ERROR_STATUS_CODE, $requestOrigin);
            }
        }

        return $response;
    }

    private static function isAuthorized(ServerRequestInterface $request, FunctionConfigInterface $config): bool
    {
        $authorized = true;
        $requiredHeaderKey = $config->getRequiredHeaderKey();
        if ($requiredHeaderKey) {
            $requiredHeaderValue = $config->getRequiredHeaderValue();
            if (!$request->hasHeader($requiredHeaderKey) || $requiredHeaderValue !== $request->getHeaderLine($requiredHeaderKey)) {
                $authorized = false;
            }
        }

        return $authorized;
    }
}
