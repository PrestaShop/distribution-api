<?php

declare(strict_types=1);

namespace App\Model;

use JsonSerializable;

class PrestaShop implements JsonSerializable
{
    private string $version;
    private ?string $minPhpVersion = null;
    private ?string $maxPhpVersion = null;

    public function __construct(string $version)
    {
        $this->version = $version;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getMinPhpVersion(): ?string
    {
        return $this->minPhpVersion;
    }

    public function setMinPhpVersion(?string $minPhpVersion): void
    {
        $this->minPhpVersion = $minPhpVersion;
    }

    public function getMaxPhpVersion(): ?string
    {
        return $this->maxPhpVersion;
    }

    public function setMaxPhpVersion(?string $maxPhpVersion): void
    {
        $this->maxPhpVersion = $maxPhpVersion;
    }

    public function isStable(): bool
    {
        return (bool) preg_match('/^[\d\.]+$/', $this->version);
    }

    public function isRC(): bool
    {
        return (bool) preg_match('/^[\d\.]+\-rc\.\d+$/', $this->version);
    }

    public function isBeta(): bool
    {
        return (bool) preg_match('/^[\d\.]+\-beta\.\d+$/', $this->version);
    }

    public function jsonSerialize(): mixed
    {
        return [
            'version' => $this->getVersion(),
            'phpMaxVersion' => $this->getMaxPhpVersion(),
            'phpMinVersion' => $this->getMinPhpVersion(),
        ];
    }
}
