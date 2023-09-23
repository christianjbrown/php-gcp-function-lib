<?php

declare(strict_types=1);

namespace CloudFunction;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface CloudFunctionInterface
{
    public const ERROR_NOT_AUTHORIZED = 'Not authorized';
    public const ERROR_UNHANDLED = 'An unhandled error occurred';

    public function run(ServerRequestInterface $request): ResponseInterface;
}
