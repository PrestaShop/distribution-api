<?php

declare(strict_types=1);

namespace App\Model;

use JsonSerializable;

class Module implements JsonSerializable
{
    private string $name;

    /** @var Version[] */
    private array $versions;

    /**
     * @param string $name
     * @param Version[] $versions
     */
    public function __construct(string $name, array $versions = [])
    {
        $this->name = $name;
        $this->versions = $versions;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return Version[]
     */
    public function getVersions(): array
    {
        return $this->versions;
    }

    public function addVersion(Version $version): static
    {
        $this->versions[] = $version;

        return $this;
    }

    public function jsonSerialize(): mixed
    {
        return [$this->getName() => $this->getVersions()];
    }
}
