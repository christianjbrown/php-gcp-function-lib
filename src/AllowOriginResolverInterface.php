<?php

declare(strict_types=1);

namespace ChristianBrown\CloudRunFunction;

interface AllowOriginResolverInterface
{
    public function resolve(string $requiredOrigin, ?string $requestOrigin, bool $debug, bool $allowLocalOrigins): string;
}
