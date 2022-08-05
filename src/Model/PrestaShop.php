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
    private ?string $githubUrl = null;
    private ?string $downloadUrl = null;

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

    public function getGithubUrl(): ?string
    {
        return $this->githubUrl;
    }

    public function setGithubUrl(?string $githubUrl): void
    {
        $this->githubUrl = $githubUrl;
    }

    public function getDownloadUrl(): ?string
    {
        return $this->downloadUrl;
    }

    public function setDownloadUrl(?string $downloadUrl): void
    {
        $this->downloadUrl = $downloadUrl;
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
            'download_url' => $this->getDownloadUrl(),
            'stability' =>  $this->getStability(),
        ];
    }
}
