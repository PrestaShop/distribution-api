<?php

declare(strict_types=1);

namespace App\Util;

use App\Exception\NoAssetException;
use App\Model\PrestaShop;

class PrestaShopClassicUtils extends PrestaShopUtils
{
    /**
     * @param array{'tag_name': string, 'assets': array<array{'name': string, 'browser_download_url': string}>} $item
     */
    protected function buildModelFromRepository(mixed $item): PrestaShop
    {
        $versions = VersionUtils::parseVersion($item['tag_name']);
        $prestashopStabilityVersion = $versions['stability'] ? '-' . $versions['stability'] : '';
        $prestashopVersion = $versions['base'] . $prestashopStabilityVersion;
        $prestaShop = new PrestaShop($prestashopVersion, PrestaShop::DISTRIBUTION_CLASSIC);
        $prestaShop->setGithubZipUrl($this->getZipAssetUrl($item));
        $prestaShop->setDistributionVersion($versions['distribution']);
        try {
            $prestaShop->setGithubXmlUrl($this->getXmlAssetUrl($item));
        } catch (NoAssetException) {
        }

        return $prestaShop;
    }
}
