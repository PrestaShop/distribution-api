<?php

declare(strict_types=1);

namespace App\Util;

use App\Model\PrestaShop;
use App\Model\Version;

class PublicDownloadUrlProvider
{
    private string $baseUrl;

    public function __construct(string $publicAssetsBaseUrl)
    {
        $this->baseUrl = $publicAssetsBaseUrl;
    }

    public function getPrestaShopZipDownloadUrl(string $version, string $distribution): string
    {
        return $this->baseUrl . '/assets/' . $this->getDistributionPath($distribution) . '/' . $version . '/prestashop.zip';
    }

    public function getPrestaShopXmlDownloadUrl(string $version, string $distribution): string
    {
        return $this->baseUrl . '/assets/' . $this->getDistributionPath($distribution) . '/' . $version . '/prestashop.xml';
    }

    public function getModuleDownloadUrl(string $moduleName, Version $version): string
    {
        return $this->baseUrl . '/assets/modules/' . $moduleName . '/' . $version->getTag() . '/' . $moduleName . '.zip';
    }

    public function getModuleIconUrl(string $moduleName, Version $version): string
    {
        return $this->baseUrl . '/assets/modules/' . $moduleName . '/' . $version->getTag() . '/logo.png';
    }

    private function getDistributionPath(string $distribution): string
    {
        return $distribution === PrestaShop::DISTRIBUTION_OPEN_SOURCE ? 'prestashop' : 'prestashop-classic';
    }
}
