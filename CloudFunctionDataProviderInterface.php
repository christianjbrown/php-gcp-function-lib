<?php

declare(strict_types=1);

namespace CloudFunction;

use Psr\Http\Message\ServerRequestInterface;

interface CloudFunctionDataProviderInterface
{
    public function getData(array $env, ServerRequestInterface $request): array;
}
