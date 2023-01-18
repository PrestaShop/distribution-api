<?php

declare(strict_types=1);

namespace App\Command;

use App\Model\PrestaShop;
use App\Util\ModuleUtils;
use App\Util\PrestaShopUtils;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckReposCommand extends Command
{
    protected static $defaultName = 'checkRepos';

    private ModuleUtils $moduleUtils;
    private PrestaShopUtils $prestaShopUtils;

    public function __construct(ModuleUtils $moduleUtils, PrestaShopUtils $prestaShopUtils)
    {
        parent::__construct();
        $this->moduleUtils = $moduleUtils;
        $this->prestaShopUtils = $prestaShopUtils;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $prestashops = $this->prestaShopUtils->getVersions();
        foreach ($prestashops as $prestashop) {
            $this->checkPrestaShop($prestashop, $output);
        }

        $nativeModules = $this->moduleUtils->getNativeModuleList();
        foreach ($nativeModules as $nativeModule) {
            $this->checkModule($nativeModule, $output);
        }

        return self::SUCCESS;
    }

    private function checkPrestaShop(PrestaShop $prestaShop, OutputInterface $output): void
    {
        $output->writeln(sprintf('<info>Checking PrestaShop %s</info>', $prestaShop->getVersion()));
        $releaseOk = true;
        if ($prestaShop->getGithubZipUrl() === null) {
            $output->writeln('<error>No Zip asset</error>');
            $releaseOk = false;
        }

        if ($prestaShop->getGithubXmlUrl() === null) {
            $output->writeln('<error>No Xml asset</error>');
            $releaseOk = false;
        }

        if ($releaseOk) {
            $output->writeln('<comment>All good!</comment>');
        }
    }

    private function checkModule(string $moduleName, OutputInterface $output): void
    {
        $output->writeln(sprintf('<info>Checking module %s</info>', $moduleName));
        $versions = $this->moduleUtils->getVersions($moduleName, false);
        if (empty($versions)) {
            $output->writeln(sprintf('<error>No release for module %s</error>', $moduleName));
        }
        foreach ($versions as $version) {
            if ($version->getGithubUrl() === null) {
                $output->writeln(
                    sprintf('<error>No asset for release %s of module %s</error>', $version->getTag(), $moduleName)
                );
            } else {
                $output->writeln(
                    sprintf('<comment>Assets found for release %s of module %s</comment>', $version->getTag(), $moduleName)
                );
            }
        }
    }
}
