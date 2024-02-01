<?php

declare(strict_types=1);

namespace App\Model;

use JsonSerializable;

class PrestaShop implements JsonSerializable
{
    public const CHANNEL_STABLE = 'stable';
    public const CHANNEL_RC = 'rc';
    public const CHANNEL_BETA = 'beta';

    private string $version;
    private ?string $minPhpVersion = null;
    private ?string $maxPhpVersion = null;
    private ?string $githubZipUrl = null;
    private ?string $githubXmlUrl = null;
    private ?string $zipDownloadUrl = null;
    private ?string $xmlDownloadUrl = null;
    private ?string $zipMD5 = null;

    public function __construct(string $version)
    {
        $this->version = $version;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getMajorVersionNumber(): string
    {
        $version = $this->stripExtraDataFromVersion($this->version);
        $version = explode('.', $version);

        return $version[0];
    }

    public function getMinorVersionNumber(): string
    {
        $version = $this->stripExtraDataFromVersion($this->version);
        $version = explode('.', $version);

        return $version[1];
    }

    public function getPatchVersionNumber(): string
    {
        $version = $this->stripExtraDataFromVersion($this->version);
        $version = explode('.', $version);

        return $version[2];
    }

    private function stripExtraDataFromVersion(string $version): string
    {
        if (str_starts_with($version, '1.')) {
            $version = substr($version, 2);
        }
        $version = explode('-', $version);

        return $version[0];
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

    public function getGithubZipUrl(): ?string
    {
        return $this->githubZipUrl;
    }

    public function setGithubZipUrl(?string $githubZipUrl): void
    {
        $this->githubZipUrl = $githubZipUrl;
    }

    public function getGithubXmlUrl(): ?string
    {
        return $this->githubXmlUrl;
    }

    public function setGithubXmlUrl(?string $githubXmlUrl): void
    {
        $this->githubXmlUrl = $githubXmlUrl;
    }

    public function getZipDownloadUrl(): ?string
    {
        return $this->zipDownloadUrl;
    }

    public function setZipDownloadUrl(?string $zipDownloadUrl): void
    {
        $this->zipDownloadUrl = $zipDownloadUrl;
    }

    public function getXmlDownloadUrl(): ?string
    {
        return $this->xmlDownloadUrl;
    }

    public function setXmlDownloadUrl(?string $xmlDownloadUrl): void
    {
        $this->xmlDownloadUrl = $xmlDownloadUrl;
    }

    public function getZipMD5(): ?string
    {
        return $this->zipMD5;
    }

    public function setZipMD5(?string $zipMD5): void
    {
        $this->zipMD5 = $zipMD5;
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

    private function getStability(): string
    {
        if ($this->isStable()) {
            return self::CHANNEL_STABLE;
        }
        if ($this->isRC()) {
            return self::CHANNEL_RC;
        }

        return self::CHANNEL_BETA;
    }

    public function jsonSerialize(): mixed
    {
        return [
            'version' => $this->getVersion(),
            'php_max_version' => $this->getMaxPhpVersion(),
            'php_min_version' => $this->getMinPhpVersion(),
            'zip_download_url' => $this->getZipDownloadUrl(),
            'zip_md5' => $this->getZipMD5(),
            'xml_download_url' => $this->getXmlDownloadUrl(),
            'stability' => $this->getStability(),
        ];
    }
}
