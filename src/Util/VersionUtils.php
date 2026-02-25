<?php

declare(strict_types=1);

namespace App\Util;

use App\Model\PrestaShop;
use InvalidArgumentException;

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
    public function getHighestStableVersionFromList(array $list = []): ?PrestaShop
    {
        if (empty($list)) {
            return null;
        }

        // Get highest version available
        $highestVersion = null;
        foreach ($list as $version) {
            if (($highestVersion === null || version_compare($version->getVersion(), $highestVersion->getVersion(), '>'))
              && $version->isStable()) {
                $highestVersion = $version;
            }
        }

        return $highestVersion;
    }

    /**
     * Returns highest available version under development from a list of Prestashop versions.
     * Should only return beta or rc versions.
     *
     * @param PrestaShop[] $list
     *
     * @return PrestaShop|null
     */
    public function getHighestVersionUnderDevelopmentFromList(array $list = []): ?PrestaShop
    {
        if (empty($list)) {
            return null;
        }

        // First get only versions under development
        $versionsUnderDevelopment = [];
        $stableVersions = [];
        foreach ($list as $version) {
            if (!$version->isStable()) {
                $versionsUnderDevelopment[] = $version;
            } else {
                $stableVersions[] = $version;
            }
        }

        if (empty($versionsUnderDevelopment)) {
            return null;
        }

        // Then remove the ones that have been released
        foreach ($stableVersions as $stableVersion) {
            foreach ($versionsUnderDevelopment as $key => $developmentVersion) {
                if ($developmentVersion->getMajorVersionNumber() === $stableVersion->getMajorVersionNumber()
                    && $developmentVersion->getMinorVersionNumber() === $stableVersion->getMinorVersionNumber()
                    && $developmentVersion->getPatchVersionNumber() === $stableVersion->getPatchVersionNumber()) {
                    unset($versionsUnderDevelopment[$key]);
                    break;
                }
            }
        }

        if (empty($versionsUnderDevelopment)) {
            return null;
        }

        // Get highest version available
        $highestVersionUnderDevelopment = null;
        foreach ($versionsUnderDevelopment as $developmentVersion) {
            if ($highestVersionUnderDevelopment === null || version_compare($developmentVersion->getVersion(), $highestVersionUnderDevelopment->getVersion(), '>')) {
                $highestVersionUnderDevelopment = $developmentVersion;
            }
        }

        return $highestVersionUnderDevelopment;
    }

    /**
     * Returns highest available stable version of a previous major from a list of Prestashop versions.
     * Ignores beta and release candidates.
     *
     * @param PrestaShop[] $list
     *
     * @return PrestaShop|null
     */
    public function getHighestStablePreviousVersionFromList(array $list = []): ?PrestaShop
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
            if (($highestPreviousVersion === null || version_compare($version->getVersion(), $highestPreviousVersion->getVersion(), '>'))
                && $version->getMajorVersionNumber() == $possiblePreviousMajor
                && $version->isStable()) {
                $highestPreviousVersion = $version;
            }
        }

        return $highestPreviousVersion;
    }

    /**
     * Formats a version string to Semantic Versioning (SemVer) with three segments (X.Y.Z).
     *
     * Examples:
     * - '8.0'     => '8.0.0'
     * - '1.7.0.6' => '1.7.0'
     * - '1.7.0-beta' => '1.7.0'
     *
     * @param string $version
     *
     * @return string
     */
    public function formatVersionToSemver(string $version): string
    {
        $version = trim($version);
        $version = (string) preg_replace('/[-+].*$/', '', $version);

        $parts = explode('.', $version);
        $parts = array_slice($parts, 0, 3);
        $parts = array_pad($parts, 3, '0');

        return implode('.', $parts);
    }

    /**
     * Parses a version string into base, stability tag, and distribution version.
     *
     * Examples:
     *   - 9.0.0-1.0            → base: 9.0.0, stability: null, distribution: 1.0
     *   - 9.0.0-1.0-beta.1     → base: 9.0.0, stability: beta.1, distribution: 1.0
     *   - 9.0.0-beta.1         → base: 9.0.0, stability: beta.1, distribution: null
     *   - 9.0.0                → base: 9.0.0, stability: null, distribution: null
     *
     * @param string $version
     *
     * @return array{
     *     base: string,
     *     stability: string|null,
     *     distribution: string|null
     * }
     *
     * @throws InvalidArgumentException
     */
    public static function parseVersion(string $version): array
    {
        $base = null;
        $stability = null;
        $distribution = null;

        // Match with distribution and optional stability (e.g. 9.0.0-1.0-beta.1)
        if (preg_match('/^([\d\.]+)-([\d\.]+)-(beta|rc)\.(\d+)$/', $version, $matches)) {
            $base = $matches[1];
            $distribution = $matches[2];
            $stability = $matches[3] . '.' . $matches[4];
        }
        // Match with distribution only
        elseif (preg_match('/^([\d\.]+)-([\d\.]+)$/', $version, $matches)) {
            $base = $matches[1];
            $distribution = $matches[2];
        }
        // Match with stability only (e.g. 9.0.0-beta.1)
        elseif (preg_match('/^([\d\.]+)-(beta|rc)\.(\d+)$/', $version, $matches)) {
            $base = $matches[1];
            $stability = $matches[2] . '.' . $matches[3];
        }
        // Match base only (e.g. 9.0.0 or 1.6.1.24)
        elseif (preg_match('/^\d+(?:\.\d+){2,}$/', $version)) {
            $base = $version;
        }

        if ($base === null) {
            throw new InvalidArgumentException(sprintf('Unable to parse version "%s".', $version));
        }

        return [
            'base' => $base,
            'stability' => $stability,
            'distribution' => $distribution,
        ];
    }
}
