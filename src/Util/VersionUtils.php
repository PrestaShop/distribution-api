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

    public static function removeClassicInVersionTag(string $version): string
    {
        if (!str_contains($version, PrestaShop::DISTRIBUTION_CLASSIC)) {
            return $version;
        }

        return preg_replace('/^(classic-?)|(-?classic)$/i', '', $version) ?? $version;
    }

    /**
     * @param string $version the full version string to parse
     *
     * @return array{
     *     base: string,
     *     distribution: string
     * } An associative array containing:
     *     - 'base': the base version (possibly with a beta/rc tag),
     *     - 'distribution': the distribution version number
     *
     * @throws InvalidArgumentException if the version string format is not recognized
     */
    public static function parseVersion($version): array
    {
        $version = self::removeClassicInVersionTag($version);

        $lastDashPos = strrpos($version, '-');

        if ($lastDashPos === false) {
            throw new InvalidArgumentException(sprintf('Unable to parse version "%s".', $version));
        }

        $baseVersion = substr($version, 0, $lastDashPos);
        $distVersion = substr($version, $lastDashPos + 1);

        // Validate both parts with simple regex
        if (
            !preg_match('/^\d+\.\d+\.\d+(-(?:beta|rc)\.\d+)?$/', $baseVersion)
            && !preg_match('/^\d+\.\d+\.\d+-[a-z]+\.\d+$/', $baseVersion)
        ) {
            throw new InvalidArgumentException(sprintf('Unable to parse version "%s".', $version));
        }

        if (!preg_match('/^\d+\.\d+$/', $distVersion)) {
            throw new InvalidArgumentException(sprintf('Incomplete version string "%s".', $version));
        }

        return [
            'base' => $baseVersion,
            'distribution' => $distVersion,
        ];
    }
}
