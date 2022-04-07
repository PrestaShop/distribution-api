<?php

declare(strict_types=1);

namespace App\Model;

use JsonSerializable;

class Version implements JsonSerializable
{
    private string $tag;
    private ?string $displayName;
    private ?string $icon;
    private ?string $url;
    private ?string $version;
    private ?string $author;
    private ?string $versionCompliancyMin;
    private ?string $versionCompliancyMax;
    private ?string $tab;
    private ?string $description;

    public function __construct(string $tag, string $url = null)
    {
        $this->tag = $tag;
        $this->url = $url;
    }

    public function getTag(): string
    {
        return $this->tag;
    }

    public function getUrl(): ?string
    {
        return $this->url;
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
            'download_url' => $this->getUrl(),
            'icon' => $this->getIcon(),
        ];
    }
}
