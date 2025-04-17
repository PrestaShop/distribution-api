<?php

declare(strict_types=1);

namespace App\Util;

use App\Exception\NoAssetException;
use App\Model\PrestaShop;

class PrestaShopOpenSourceUtils extends PrestaShopUtils
{
    /**
     * @param array{'tag_name': string, 'assets': array<array{'name': string, 'browser_download_url': string}>} $item
     */
    protected function buildModelFromRepository(mixed $item): PrestaShop
    {
        $prestaShop = new PrestaShop($item['tag_name'], PrestaShop::DISTRIBUTION_OPEN_SOURCE);
        $prestaShop->setGithubZipUrl($this->getZipAssetUrl($item));
        try {
            $prestaShop->setGithubXmlUrl($this->getXmlAssetUrl($item));
        } catch (NoAssetException) {
        }

        return $prestaShop;
    }
}
