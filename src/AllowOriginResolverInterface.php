<?php

declare(strict_types=1);

namespace ChristianBrown\GcpFunction;

interface AllowOriginResolverInterface
{
    public function resolve(string $requiredOrigin, ?string $requestOrigin, bool $debug): string;
}
