<?php

declare(strict_types=1);

namespace App\Model;

use InvalidArgumentException;
use JsonSerializable;

class PrestaShop implements JsonSerializable
{
    public const CHANNEL_STABLE = 'stable';
    public const CHANNEL_RC = 'rc';
    public const CHANNEL_BETA = 'beta';

    public const DISTRIBUTION_CLASSIC = 'classic';
    public const DISTRIBUTION_OPEN_SOURCE = 'open_source';
    public const DISTRIBUTIONS_LIST = [self::DISTRIBUTION_OPEN_SOURCE, self::DISTRIBUTION_CLASSIC];

    private string $version;
    private string $distribution;
    private ?string $minPhpVersion = null;
    private ?string $maxPhpVersion = null;
    private ?string $githubZipUrl = null;
    private ?string $githubXmlUrl = null;
    private ?string $zipDownloadUrl = null;
    private ?string $xmlDownloadUrl = null;
    private ?string $zipMD5 = null;
    private ?string $releaseNoteUrl = null;

    public function __construct(string $version, string $distribution = self::DISTRIBUTION_OPEN_SOURCE)
    {
        if (!in_array($distribution, self::DISTRIBUTIONS_LIST)) {
            $distributions = array_map(fn ($f) => sprintf('"%s"', $f), self::DISTRIBUTIONS_LIST);
            throw new InvalidArgumentException(sprintf('Invalid distribution "%s" provided. Accepted values are: %s.', $distribution, implode(', ', $distributions)));
        }

        $this->version = $version;
        $this->distribution = $distribution;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getDistribution(): string
    {
        return $this->distribution;
    }

    public function getNextMajorVersion(): string
    {
        return implode('.', [
            $this->getMajorVersionNumber() + 1,
            0,
            0,
        ]);
    }

    public function getNextMinorVersion(): string
    {
        return implode('.', [
            $this->getMajorVersionNumber(),
            $this->getMinorVersionNumber() + 1,
            0,
        ]);
    }

    public function getNextPatchVersion(): string
    {
        return implode('.', [
            $this->getMajorVersionNumber(),
            $this->getMinorVersionNumber(),
            $this->getPatchVersionNumber() + 1,
        ]);
    }

    public function getMajorVersionNumber(): int
    {
        $version = $this->stripExtraDataFromVersion($this->version);
        $version = explode('.', $version);

        return (int) $version[0];
    }

    public function getMinorVersionNumber(): int
    {
        $version = $this->stripExtraDataFromVersion($this->version);
        $version = explode('.', $version);

        return (int) $version[1];
    }

    public function getPatchVersionNumber(): int
    {
        $version = $this->stripExtraDataFromVersion($this->version);
        $version = explode('.', $version);

        return (int) $version[2];
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

    /**
     * @return self::CHANNEL_*
     */
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

    public function getReleaseNoteUrl(): ?string
    {
        return $this->releaseNoteUrl;
    }

    public function setReleaseNoteUrl(?string $releaseNoteUrl): void
    {
        $this->releaseNoteUrl = $releaseNoteUrl;
    }

    public function jsonSerialize(): mixed
    {
        return [
            'version' => $this->getVersion(),
            'distribution' => $this->getDistribution(),
            'php_max_version' => $this->getMaxPhpVersion(),
            'php_min_version' => $this->getMinPhpVersion(),
            'zip_download_url' => $this->getZipDownloadUrl(),
            'zip_md5' => $this->getZipMD5(),
            'xml_download_url' => $this->getXmlDownloadUrl(),
            'stability' => $this->getStability(),
            'release_notes_url' => $this->getReleaseNoteUrl(),
        ];
    }
}
