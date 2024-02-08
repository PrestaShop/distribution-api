<?php

declare(strict_types=1);

namespace App\Util;

use App\Model\PrestaShop;

class VersionUtils
{
    /**
     * Returns highest available stable version from a list of Prestashop versions.
     * Ignores beta and release candidates.
     *
     * @param PrestaShop[] $list
     *
     * @return PrestaShop|null
     */
    public function getHighestStableVersionFromList(array $list = []): ?Prestashop
    {
        if (empty($list)) {
            return null;
        }

        // Get highest version available
        $highestVersion = null;
        foreach ($list as $version) {
            if (($highestVersion === null || version_compare($version->getVersion(), $highestVersion->getVersion(), '>')) &&
              $version->isStable()) {
                $highestVersion = $version;
            }
        }

        return $highestVersion;
    }

    /**
     * Returns highest available stable version of a previous major from a list of Prestashop versions.
     * Ignores beta and release candidates.
     *
     * @param PrestaShop[] $list
     *
     * @return PrestaShop|null
     */
    public function getHighestStablePreviousVersionFromList(array $list = []): ?Prestashop
    {
        if (empty($list)) {
            return null;
        }

        $highestVersion = $this->getHighestStableVersionFromList($list);

        if (empty($highestVersion)) {
            return null;
        }

        $possiblePreviousMajor = $highestVersion->getMajorVersionNumber() - 1;
        $highestPreviousVersion = null;
        foreach ($list as $version) {
            if (($highestPreviousVersion === null || version_compare($version->getVersion(), $highestPreviousVersion->getVersion(), '>')) &&
                $version->getMajorVersionNumber() == $possiblePreviousMajor &&
                $version->isStable()) {
                $highestPreviousVersion = $version;
            }
        }

        return $highestPreviousVersion;
    }
}
