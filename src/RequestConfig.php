<?php

declare(strict_types=1);

namespace ChristianBrown\CloudFunction;

final class RequestConfig implements RequestConfigInterface
{
    private bool $debug = false;
    private int $kRevision;
    private ?string $requiredHeaderKey = null;
    private ?string $requiredHeaderValue = null;
    private ?string $requiredOrigin = null;

    public function __construct(int $kRevision)
    {
        $this->kRevision = $kRevision;
    }

    public function getDebug(): bool
    {
        return $this->debug;
    }

    public function getKrevision(): int
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
}
