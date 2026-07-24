<?php

declare(strict_types=1);

namespace ChristianBrown\CloudRunFunction;

interface ResponseBodyBuilderInterface
{
    /**
     * @param mixed[] $data
     *
     * @return mixed[]
     */
    public function build(array $data, ?FunctionConfigInterface $functionConfig, bool $success, ?string $error, int $time): array;

    /**
     * @param mixed[] $bodyJson
     *
     * @return array{0: string, 1: bool, 2: int}
     */
    public function encode(array $bodyJson, bool $success, int $statusCode): array;
}
