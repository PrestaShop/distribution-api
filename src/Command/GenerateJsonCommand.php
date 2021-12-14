<?php

declare(strict_types=1);

namespace App\Command;

use App\Model\Module;
use App\Model\PrestaShop;
use App\Util\ModuleUtils;
use App\Util\PrestaShopUtils;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateJsonCommand extends Command
{
    private const CHANNEL_STABLE = 'stable';
    private const CHANNEL_RC = 'rc';
    private const CHANNEL_BETA = 'beta';

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
        $prestashopVersions = $this->prestaShopUtils->getLocalVersions();

        if (empty($modules) || empty($prestashopVersions)) {
            $output->writeln('<error>No module or PrestaShop version found!</error>');
            $output->writeln(sprintf(
                '<question>Did you run the `%s` and `%s` command?</question>',
                DownloadNativeModuleMainClasses::getDefaultName(),
                DownloadPrestaShopInstallVersions::getDefaultName()
            ));

            return static::FAILURE;
        }

        foreach ($modules as $module) {
            foreach ($module->getVersions() as $version) {
                $output->writeln(sprintf('<info>Parsing module %s %s</info>', $module->getName(), $version->getTag()));
                $this->moduleUtils->setVersionCompliancy($module->getName(), $version);
            }
        }

        $stable = null;
        $rc = null;
        $beta = null;

        foreach ($prestashopVersions as $prestashopVersion) {
            $output->writeln(sprintf('<info>Parsing PrestaShop %s</info>', $prestashopVersion->getVersion()));
            $this->prestaShopUtils->setVersionsCompat($prestashopVersion);

            if ($this->isMoreRecentChannel($stable, $prestashopVersion, self::CHANNEL_STABLE)) {
                $stable = $prestashopVersion;
            }
            if ($this->isMoreRecentChannel($rc, $prestashopVersion, self::CHANNEL_RC)) {
                $rc = $prestashopVersion;
            }
            if ($this->isMoreRecentChannel($beta, $prestashopVersion, self::CHANNEL_BETA)) {
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
     * @param Module[] $modules
     * @param PrestaShop[] $prestashopVersions
     */
    private function generatePrestaShopModulesJson(
        array $modules,
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
                        version_compare($prestashopVersion->getVersion(), $version->getVersionCompliancyMin(), '>')
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

        foreach ($infos as $prestashopVersion => $modules) {
            $output->writeln(sprintf('<info>Generate json for PrestaShop %s</info>', $prestashopVersion));
            $prestashopVersionPath = $this->jsonDir . '/' . $prestashopVersion;
            if (!is_dir($prestashopVersionPath)) {
                mkdir($prestashopVersionPath);
            }
            file_put_contents($prestashopVersionPath . '/modules.json', json_encode($modules));
        }
    }

    /**
     * @param PrestaShop[] $prestashopVersions
     */
    private function generatePrestaShopJson(array $prestashopVersions): void
    {
        file_put_contents($this->jsonDir . '/prestashop.json', json_encode($prestashopVersions));
    }

    private function generateChannelsJson(?PrestaShop $stable, ?PrestaShop $rc, ?PrestaShop $beta): void
    {
        $stablePath = $this->jsonDir . '/stable';
        $rcPath = $this->jsonDir . '/rc';
        $betaPath = $this->jsonDir . '/beta';
        if ($stable !== null) {
            if (!is_dir($stablePath)) {
                mkdir($stablePath, 0777, true);
            }
            file_put_contents($stablePath . '/prestashop.json', json_encode($stable));
        }
        if ($rc !== null) {
            if (!is_dir($rcPath)) {
                mkdir($rcPath, 0777, true);
            }
            file_put_contents($rcPath . '/prestashop.json', json_encode($rc));
        }
        if ($beta !== null) {
            if (!is_dir($betaPath)) {
                mkdir($betaPath, 0777, true);
            }
            file_put_contents($betaPath . '/prestashop.json', json_encode($beta));
        }
    }

    private function isMoreRecentChannel(?PrestaShop $current, PrestaShop $new, string $channel): bool
    {
        $isChannel = false;
        switch ($channel) {
            case self::CHANNEL_STABLE:
                $isChannel = $new->isStable();
                break;
            case self::CHANNEL_RC:
                $isChannel = $new->isRC();
                break;
            case self::CHANNEL_BETA:
                $isChannel = $new->isBeta();
                break;
        }

        return $isChannel && ($current === null || version_compare($new->getVersion(), $current->getVersion(), '>'));
    }
}
