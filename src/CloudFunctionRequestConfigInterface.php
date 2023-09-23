<?php

declare(strict_types=1);

namespace ChristianBrown\CloudFunction;

interface CloudFunctionRequestConfigInterface
{
    public function getDebug(): bool;

    public function getKrevision(): int;

    public function getRequiredHeaderKey(): ?string;

    public function getRequiredHeaderValue(): ?string;

    public function getRequiredOrigin(): ?string;

    public function setDebug(bool $value): self;

    public function setRequiredHeaderKey(?string $value): self;

    public function setRequiredHeaderValue(?string $value): self;

    public function setRequiredOrigin(?string $value): self;
}
