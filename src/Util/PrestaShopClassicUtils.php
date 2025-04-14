<?php

declare(strict_types=1);

namespace App\Util;

use App\Exception\NoAssetException;
use App\Model\PrestaShop;

class PrestaShopClassicUtils extends PrestaShopUtils
{
    /**
     * @return PrestaShop[]
     */
    public function getVersions(): array
    {
        $page = 1;
        $versions = [];
        $releasesApi = $this->githubClient->repo()->releases();
        [$owner, $repo] = explode('/', $this->repositoryFullName, 2);
        while (count($results = $releasesApi->all($owner, $repo, ['page' => $page++])) > 0) {
            $versions = array_merge($versions, array_filter(
                $results,
                fn ($item) => $this->hasZipAsset($item) && $this->isVersionGreaterThanOrEqualToMin($item['tag_name'])
            ));
        }

        return array_map(function ($item): PrestaShop {
            $versions = VersionUtils::parseVersion($item['tag_name']);

            $prestaShop = new PrestaShop($versions['base'], PrestaShop::DISTRIBUTION_CLASSIC);
            $prestaShop->setGithubZipUrl($this->getZipAssetUrl($item));
            $prestaShop->setDistributionVersion($versions['distribution']);
            try {
                $prestaShop->setGithubXmlUrl($this->getXmlAssetUrl($item));
            } catch (NoAssetException) {
            }

            return $prestaShop;
        }, $versions);
    }

    /**
     * @return PrestaShop[]
     */
    public function getLocalVersions(): array
    {
        $prestashopVersions = [];
        $exclude = ['.', '..'];
        if (!is_dir($this->prestaShopDir) || !$prestaShopScandir = scandir($this->prestaShopDir)) {
            return [];
        }
        foreach ($prestaShopScandir as $classicVersion) {
            if (in_array($classicVersion, $exclude) || !is_dir($this->prestaShopDir . '/' . $classicVersion)) {
                continue;
            }
            $versionPath = $this->prestaShopDir . '/' . $classicVersion;
            if (!is_file($versionPath . '/prestashop.zip')) {
                continue;
            }
            $versions = VersionUtils::parseVersion($classicVersion);
            $prestashopVersions[] = $this->buildModelFromLocal($versions['base'], PrestaShop::DISTRIBUTION_CLASSIC, $versionPath, $versions['distribution']);
        }

        return $prestashopVersions;
    }

    protected function isVersionGreaterThanOrEqualToMin(string $version): bool
    {
        $version = VersionUtils::removeClassicInVersionTag($version);

        return version_compare($version, $this->prestaShopMinVersion, '>=');
    }
}
