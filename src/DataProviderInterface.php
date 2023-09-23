<?php

declare(strict_types=1);

namespace ChristianBrown\CloudFunction;

use Psr\Http\Message\ServerRequestInterface;

interface DataProviderInterface
{
    public function getData(array $env, ServerRequestInterface $request): array;
}
