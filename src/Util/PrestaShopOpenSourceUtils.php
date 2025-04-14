<?php

declare(strict_types=1);

namespace App\Util;

use App\Exception\NoAssetException;
use App\Model\PrestaShop;

class PrestaShopOpenSourceUtils extends PrestaShopUtils
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
            $prestaShop = new PrestaShop($item['tag_name'], PrestaShop::DISTRIBUTION_OPEN_SOURCE);
            $prestaShop->setGithubZipUrl($this->getZipAssetUrl($item));
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
        $versions = [];
        $exclude = ['.', '..'];
        if (!is_dir($this->prestaShopDir) || !$prestaShopScandir = scandir($this->prestaShopDir)) {
            return [];
        }
        foreach ($prestaShopScandir as $prestaShopVersion) {
            if (in_array($prestaShopVersion, $exclude) || !is_dir($this->prestaShopDir . '/' . $prestaShopVersion)) {
                continue;
            }
            $versionPath = $this->prestaShopDir . '/' . $prestaShopVersion;
            if (!is_file($versionPath . '/prestashop.zip')) {
                continue;
            }

            $versions[] = $this->buildModelFromLocal($prestaShopVersion, PrestaShop::DISTRIBUTION_OPEN_SOURCE, $versionPath, null);
        }

        return $versions;
    }

    protected function isVersionGreaterThanOrEqualToMin(string $version): bool
    {
        return version_compare($version, $this->prestaShopMinVersion, '>=');
    }
}
