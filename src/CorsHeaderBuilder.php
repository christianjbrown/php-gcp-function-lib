<?php

declare(strict_types=1);

namespace ChristianBrown\GcpFunction;

use function implode;

final class CorsHeaderBuilder implements CorsHeaderBuilderInterface
{
    private AllowOriginResolverInterface $allowOriginResolver;

    public function __construct(AllowOriginResolverInterface $allowOriginResolver)
    {
        $this->allowOriginResolver = $allowOriginResolver;
    }

    /**
     * @param array<string, string> $headers
     *
     * @return array<string, string>
     */
    public function build(array $headers, ?FunctionConfigInterface $functionConfig, ?string $requestOrigin): array
    {
        if (!$functionConfig instanceof FunctionConfigInterface) {
            return $headers;
        }

        $requiredOrigin = $functionConfig->getRequiredOrigin();
        if (empty($requiredOrigin)) {
            return $headers;
        }

        $headers[ResponseInterface::HEADER_KEY_ALLOW_ORIGIN] = $this->allowOriginResolver->resolve($requiredOrigin, $requestOrigin, $functionConfig->getDebug());

        $varyList = [ResponseInterface::HEADER_VARY_ACCEPT_ENCODING, ResponseInterface::HEADER_VARY_ORIGIN];
        $requiredHeaderKey = $functionConfig->getRequiredHeaderKey();
        if ($requiredHeaderKey) {
            $varyList[] = $requiredHeaderKey;
        }
        $headers[ResponseInterface::HEADER_KEY_VARY] = implode(',', $varyList);

        return $headers;
    }
}
