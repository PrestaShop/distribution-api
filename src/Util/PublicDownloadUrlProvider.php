<?php

declare(strict_types=1);

namespace App\Util;

use App\Model\Version;

class PublicDownloadUrlProvider
{
    private string $baseUrl;

    public function __construct(string $publicAssetsBaseUrl)
    {
        $this->baseUrl = $publicAssetsBaseUrl;
    }

    public function getPrestaShopZipDownloadUrl(string $version): string
    {
        return $this->baseUrl . '/assets/prestashop/' . $version . '/prestashop.zip';
    }

    public function getPrestaShopXmlDownloadUrl(string $version): string
    {
        return $this->baseUrl . '/assets/prestashop/' . $version . '/prestashop.xml';
    }

    public function getModuleDownloadUrl(string $moduleName, Version $version): string
    {
        return $this->baseUrl . '/assets/modules/' . $moduleName . '/' . $version->getTag() . '/' . $moduleName . '.zip';
    }

    public function getModuleIconUrl(string $moduleName, Version $version): string
    {
        return $this->baseUrl . '/assets/modules/' . $moduleName . '/' . $version->getTag() . '/logo.png';
    }
}
