<?php

declare(strict_types=1);

namespace App\Model;

use JsonSerializable;

class Version implements JsonSerializable
{
    private string $version;
    private ?string $url;
    private ?string $versionCompliancyMin;
    private ?string $versionCompliancyMax;

    public function __construct(string $version, ?string $url = null)
    {
        $pattern = '/[^\d]*?(\d.+)/'; // remove version prefix like the "v" in v1.0.0
        $replace = '$1';
        $this->version = preg_replace($pattern, $replace, $version) ?? $version;
        $this->url = $url;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getUrl(): ?string
    {
        return $this->url;
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

    public function jsonSerialize()
    {
        return [
            'version' => $this->getVersion(),
            'versionCompliancyMin' => $this->getVersionCompliancyMin(),
            'versionCompliancyMax' => $this->getVersionCompliancyMax(),
        ];
    }
}
