<?php

declare(strict_types=1);

namespace ChristianBrown\GcpFunction;

interface CorsHeaderBuilderInterface
{
    /**
     * @param array<string, string> $headers
     *
     * @return array<string, string>
     */
    public function build(array $headers, ?FunctionConfigInterface $functionConfig, ?string $requestOrigin): array;
}
