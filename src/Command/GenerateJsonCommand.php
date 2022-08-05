<?php

declare(strict_types=1);

namespace App\Command;

use App\Exception\FilesystemException;
use App\Model\PrestaShop;
use App\ModuleCollection;
use App\Util\ModuleUtils;
use App\Util\PrestaShopUtils;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateJsonCommand extends Command
{
    protected static $defaultName = 'generateJson';

    private ModuleUtils $moduleUtils;
    private PrestaShopUtils $prestaShopUtils;
    private string $jsonDir;

    public function __construct(
        ModuleUtils $moduleUtils,
        PrestaShopUtils $prestaShopUtils,
        string $jsonDir
    ) {
        parent::__construct();
        $this->moduleUtils = $moduleUtils;
        $this->prestaShopUtils = $prestaShopUtils;
        $this->jsonDir = $jsonDir;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $modules = $this->moduleUtils->getLocalModules();
        $prestashopVersions = array_merge(
            $this->prestaShopUtils->getVersionsFromBucket(),
            $this->prestaShopUtils->getLocalVersions()
        );

        if (empty($modules) || empty($prestashopVersions)) {
            $output->writeln('<error>No module or PrestaShop version found!</error>');
            $output->writeln(sprintf(
                '<question>Did you run the `%s` and `%s` command?</question>',
                DownloadNativeModuleMainClassesCommand::getDefaultName(),
                DownloadNewPrestaShopReleasesCommand::getDefaultName()
            ));

            return static::FAILURE;
        }

        foreach ($modules as $module) {
            foreach ($module->getVersions() as $version) {
                $output->writeln(sprintf('<info>Parsing module %s %s</info>', $module->getName(), $version->getTag()));
                $this->moduleUtils->setVersionData($module->getName(), $version);
            }
            $this->moduleUtils->overrideVersionCompliancyFromYaml($module);
        }

        $stable = null;
        $rc = null;
        $beta = null;

        foreach ($prestashopVersions as $prestashopVersion) {
            if ($this->isMoreRecentChannel($stable, $prestashopVersion, PrestaShop::CHANNEL_STABLE)) {
                $stable = $prestashopVersion;
            }
            if ($this->isMoreRecentChannel($rc, $prestashopVersion, PrestaShop::CHANNEL_RC)) {
                $rc = $prestashopVersion;
            }
            if ($this->isMoreRecentChannel($beta, $prestashopVersion, PrestaShop::CHANNEL_BETA)) {
                $beta = $prestashopVersion;
            }
        }

        $output->writeln('<info>Generating main modules.json</info>');
        $this->generatePrestaShopModulesJson($modules, $prestashopVersions, $output);

        $output->writeln('<info>Generating prestashop.json</info>');
        $this->generatePrestaShopJson($prestashopVersions);

        $output->writeln('<info>Generating channels\' json</info>');
        $this->generateChannelsJson($stable, $rc, $beta);

        return static::SUCCESS;
    }

    /**
     * @param PrestaShop[] $prestashopVersions
     */
    private function generatePrestaShopModulesJson(
        ModuleCollection $modules,
        array $prestashopVersions,
        OutputInterface $output
    ): void {
        $infos = [];
        foreach ($prestashopVersions as $prestashopVersion) {
            $infos[$prestashopVersion->getVersion()] = [];
            foreach ($modules as $module) {
                foreach ($module->getVersions() as $version) {
                    if (null === $version->getVersionCompliancyMin() || null === $version->getVersion()) {
                        continue;
                    }
                    if (
                        version_compare($prestashopVersion->getVersion(), $version->getVersionCompliancyMin(), '>=')
                        && (
                            $version->getVersionCompliancyMax() === null
                            || version_compare($prestashopVersion->getVersion(), $version->getVersionCompliancyMax(), '<=')
                        )
                        && (
                            empty($infos[$prestashopVersion->getVersion()][$module->getName()])
                            || version_compare($version->getVersion(), $infos[$prestashopVersion->getVersion()][$module->getName()]->getVersion(), '>')
                        )
                    ) {
                        $infos[$prestashopVersion->getVersion()][$module->getName()] = $version;
                    }
                }
            }
        }

        $modulesPath = $this->jsonDir . '/modules';
        if (!is_dir($modulesPath)) {
            if (mkdir($modulesPath) === false) {
                throw new FilesystemException(sprintf('Failed to create directory "%s"', $modulesPath));
            }
        }

        foreach ($infos as $prestashopVersion => $modules) {
            $output->writeln(sprintf('<info>Generate json for PrestaShop %s</info>', $prestashopVersion));
            $filename = $modulesPath . '/' . $prestashopVersion . '.json';
            if (file_put_contents($filename, json_encode($modules)) === false) {
                throw new FilesystemException(sprintf('Failed to write file "%s"', $filename));
            }
        }
    }

    /**
     * @param PrestaShop[] $prestashopVersions
     */
    private function generatePrestaShopJson(array $prestashopVersions): void
    {
        $prestashopPath = $this->jsonDir . '/prestashop.json';
        if (file_put_contents($prestashopPath, json_encode($prestashopVersions)) === false) {
            throw new FilesystemException(sprintf('Failed to write file "%s"', $prestashopPath));
        }
    }

    private function generateChannelsJson(?PrestaShop $stable, ?PrestaShop $rc, ?PrestaShop $beta): void
    {
        $prestashopPath = $this->jsonDir . '/prestashop';
        if (!is_dir($prestashopPath)) {
            if (mkdir($prestashopPath, 0777, true) === false) {
                throw new FilesystemException(sprintf('Failed to create directory "%s"', $prestashopPath));
            }
        }

        if ($stable !== null) {
            $stablePath = $prestashopPath . '/stable.json';
            if (file_put_contents($stablePath, json_encode($stable)) === false) {
                throw new FilesystemException(sprintf('Failed to write file "%s"', $stablePath));
            }
        }
        if ($rc !== null) {
            $rcPath = $prestashopPath . '/rc.json';
            if (file_put_contents($rcPath, json_encode($rc)) === false) {
                throw new FilesystemException(sprintf('Failed to write file "%s"', $rcPath));
            }
        }
        if ($beta !== null) {
            $betaPath = $prestashopPath . '/beta.json';
            if (file_put_contents($betaPath, json_encode($beta)) === false) {
                throw new FilesystemException(sprintf('Failed to write file "%s"', $betaPath));
            }
        }
    }

    private function isMoreRecentChannel(?PrestaShop $current, PrestaShop $new, string $channel): bool
    {
        $isChannel = false;
        switch ($channel) {
            case PrestaShop::CHANNEL_STABLE:
                $isChannel = $new->isStable();
                break;
            case PrestaShop::CHANNEL_RC:
                $isChannel = $new->isRC();
                break;
            case PrestaShop::CHANNEL_BETA:
                $isChannel = $new->isBeta();
                break;
        }

        return $isChannel && ($current === null || version_compare($new->getVersion(), $current->getVersion(), '>'));
    }
}
