<?php

declare(strict_types=1);

namespace ChristianBrown\CloudFunction;

final class FunctionConfig implements FunctionConfigInterface
{
    private bool $debug = false;
    private string $kRevision;
    private ?string $requiredHeaderKey = null;
    private ?string $requiredHeaderValue = null;
    private ?string $requiredOrigin = null;
    private ?int $useCacheButRequestTtl = null;
    private ?int $useCacheIfErrorTtl = null;
    private ?int $useCacheTtl = null;

    public function __construct(string $kRevision)
    {
        $this->kRevision = $kRevision;
    }

    public function getDebug(): bool
    {
        return $this->debug;
    }

    public function getKrevision(): string
    {
        return $this->kRevision;
    }

    public function getRequiredHeaderKey(): ?string
    {
        return $this->requiredHeaderKey;
    }

    public function getRequiredHeaderValue(): ?string
    {
        return $this->requiredHeaderValue;
    }

    public function getRequiredOrigin(): ?string
    {
        return $this->requiredOrigin;
    }

    public function getUseCacheButRequestTtl(): ?int
    {
        return $this->useCacheButRequestTtl;
    }

    public function getUseCacheIfErrorTtl(): ?int
    {
        return $this->useCacheIfErrorTtl;
    }

    public function getUseCacheTtl(): ?int
    {
        return $this->useCacheTtl;
    }

    public function setDebug(bool $value): self
    {
        $this->debug = $value;

        return $this;
    }

    public function setRequiredHeaderKey(?string $value): self
    {
        $this->requiredHeaderKey = $value;

        return $this;
    }

    public function setRequiredHeaderValue(?string $value): self
    {
        $this->requiredHeaderValue = $value;

        return $this;
    }

    public function setRequiredOrigin(?string $value): self
    {
        $this->requiredOrigin = $value;

        return $this;
    }

    public function setUseCacheButRequestTtl(?int $value): FunctionConfigInterface
    {
        $this->useCacheButRequestTtl = $value;

        return $this;
    }

    public function setUseCacheIfErrorTtl(?int $value): FunctionConfigInterface
    {
        $this->useCacheIfErrorTtl = $value;

        return $this;
    }

    public function setUseCacheTtl(?int $value): FunctionConfigInterface
    {
        $this->useCacheTtl = $value;

        return $this;
    }
}
