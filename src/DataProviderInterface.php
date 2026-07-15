<?php

declare(strict_types=1);

namespace ChristianBrown\GcpFunction;

use Psr\Http\Message\ServerRequestInterface;

interface DataProviderInterface
{
    /**
     * @return mixed[]
     */
    public function getData(ServerRequestInterface $request): array;
}
