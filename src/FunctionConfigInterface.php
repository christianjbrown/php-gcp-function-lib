<?php

declare(strict_types=1);

namespace ChristianBrown\CloudFunction;

interface FunctionConfigInterface
{
    public function getDebug(): bool;

    public function getKrevision(): string;

    public function getRequiredHeaderKey(): ?string;

    public function getRequiredHeaderValue(): ?string;

    public function getRequiredOrigin(): ?string;

    public function getUseCacheButRequestTtl(): ?int;

    public function getUseCacheIfErrorTtl(): ?int;

    public function getUseCacheTtl(): ?int;

    public function setDebug(bool $value): self;

    public function setRequiredHeaderKey(?string $value): self;

    public function setRequiredHeaderValue(?string $value): self;

    public function setRequiredOrigin(?string $value): self;

    public function setUseCacheButRequestTtl(?int $value): self;

    public function setUseCacheIfErrorTtl(?int $value): self;

    public function setUseCacheTtl(?int $value): self;
}
