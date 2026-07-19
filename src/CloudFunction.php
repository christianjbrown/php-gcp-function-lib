<?php

declare(strict_types=1);

namespace ChristianBrown\GcpFunction;

use ChristianBrown\UserFriendlyException\UserFriendlyExceptionInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

use function hash_equals;

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
            return new JsonErrorResponse($this->functionConfig, self::ERROR_NOT_AUTHORIZED, ResponseInterface::STATUS_UNAUTHORIZED, $requestOrigin);
        }

        $data = $this->dataProvider->getData($request);

        return new JsonSuccessResponse($this->functionConfig, $data, ResponseInterface::STATUS_OK, $requestOrigin);
    }

    private static function isAuthorized(ServerRequestInterface $request, FunctionConfigInterface $config): bool
    {
        $requiredHeaderKey = (string) $config->getRequiredHeaderKey();
        $requiredHeaderValue = (string) $config->getRequiredHeaderValue();

        if ('' === $requiredHeaderKey) {
            // Neither part of the gate is configured: this is only allowed when
            // the function is explicitly opted in to unauthenticated access, so
            // a dropped/emptied secret fails closed instead of silently opening.
            if ('' === $requiredHeaderValue) {
                return $config->getAllowUnauthenticated();
            }

            // Only the value is configured: a partial gate is a misconfiguration
            // and is treated as deny.
            return false;
        }

        // Only the key is configured: a partial gate is a misconfiguration and
        // is treated as deny.
        if ('' === $requiredHeaderValue) {
            return false;
        }

        return hash_equals($requiredHeaderValue, $request->getHeaderLine($requiredHeaderKey));
    }
}
