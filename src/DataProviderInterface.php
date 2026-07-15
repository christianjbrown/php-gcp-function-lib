<?php

declare(strict_types=1);

namespace ChristianBrown\CloudFunction;

use Psr\Http\Message\ServerRequestInterface;

interface DataProviderInterface
{
    /**
     * @return mixed[]
     */
    public function getData(ServerRequestInterface $request): array;
}
