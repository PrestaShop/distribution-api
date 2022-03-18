<?php

declare(strict_types=1);

namespace App\Model;

use JsonSerializable;

class Version implements JsonSerializable
{
    private string $tag;
    private ?string $url;
    private ?string $version;
    private ?string $versionCompliancyMin;
    private ?string $versionCompliancyMax;

    public function __construct(string $tag, string $url = null)
    {
        $this->tag = $tag;
        $this->url = $url;
    }

    public function getTag(): ?string
    {
        return $this->tag;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function getVersion(): ?string
    {
        return $this->version;
    }

    public function setVersion(?string $version): void
    {
        $this->version = $version;
    }

    public function getVersionCompliancyMin(): ?string
    {
        return $this->versionCompliancyMin;
    }

    public function setVersionCompliancyMin(?string $versionCompliancyMin): void
    {
        $this->versionCompliancyMin = $versionCompliancyMin;
    }

    public function getVersionCompliancyMax(): ?string
    {
        return $this->versionCompliancyMax;
    }

    public function setVersionCompliancyMax(?string $versionCompliancyMax): void
    {
        $this->versionCompliancyMax = $versionCompliancyMax;
    }

    public function jsonSerialize(): mixed
    {
        return [
            'version' => $this->getVersion(),
            'versionCompliancyMin' => $this->getVersionCompliancyMin(),
            'versionCompliancyMax' => $this->getVersionCompliancyMax(),
        ];
    }
}
