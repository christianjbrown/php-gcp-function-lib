<?php

declare(strict_types=1);

namespace ChristianBrown\CloudRunFunction;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface CloudRunFunctionInterface
{
    public const string ERROR_NOT_AUTHORIZED = 'Not authorized';
    public const string ERROR_UNHANDLED = 'An unhandled error occurred';

    public function run(ServerRequestInterface $request): ResponseInterface;
}
