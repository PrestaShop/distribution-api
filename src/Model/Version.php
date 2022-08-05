<?php

declare(strict_types=1);

namespace App\Model;

use JsonSerializable;

class Version implements JsonSerializable
{
    private string $tag;
    private ?string $displayName;
    private ?string $icon;
    private ?string $githubUrl;
    private ?string $downloadUrl;
    private ?string $version;
    private ?string $author;
    private ?string $versionCompliancyMin;
    private ?string $versionCompliancyMax;
    private ?string $tab;
    private ?string $description;

    public function __construct(string $tag, string $githubUrl = null)
    {
        $this->tag = $tag;
        $this->githubUrl = $githubUrl;
    }

    public function getTag(): string
    {
        return $this->tag;
    }

    public function getGithubUrl(): ?string
    {
        return $this->githubUrl;
    }

    public function getDownloadUrl(): ?string
    {
        return $this->downloadUrl;
    }

    public function setDownloadUrl(?string $downloadUrl): static
    {
        $this->downloadUrl = $downloadUrl;

        return $this;
    }

    public function getDisplayName(): ?string
    {
        return $this->displayName;
    }

    public function setDisplayName(?string $displayName): static
    {
        $this->displayName = $displayName;

        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function getTab(): ?string
    {
        return $this->tab;
    }

    public function setTab(?string $tab): static
    {
        $this->tab = $tab;

        return $this;
    }

    public function getAuthor(): ?string
    {
        return $this->author;
    }

    public function setAuthor(?string $author): static
    {
        $this->author = $author;

        return $this;
    }

    public function getVersion(): ?string
    {
        return $this->version;
    }

    public function setVersion(?string $version): static
    {
        $this->version = $version;

        return $this;
    }

    public function getVersionCompliancyMin(): ?string
    {
        return $this->versionCompliancyMin;
    }

    public function setVersionCompliancyMin(?string $versionCompliancyMin): static
    {
        $this->versionCompliancyMin = $versionCompliancyMin;

        return $this;
    }

    public function getVersionCompliancyMax(): ?string
    {
        return $this->versionCompliancyMax;
    }

    public function setVersionCompliancyMax(?string $versionCompliancyMax): static
    {
        $this->versionCompliancyMax = $versionCompliancyMax;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function jsonSerialize(): mixed
    {
        return [
            'display_name' => $this->getDisplayName(),
            'tab' => $this->getTab(),
            'description' => $this->getDescription(),
            'author' => $this->getAuthor(),
            'version' => $this->getVersion(),
            'prestashop_min_version' => $this->getVersionCompliancyMin(),
            'prestashop_max_version' => $this->getVersionCompliancyMax(),
            'download_url' => $this->getDownloadUrl(),
            'icon' => $this->getIcon(),
        ];
    }
}
