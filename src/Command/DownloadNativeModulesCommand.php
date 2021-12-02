<?php

declare(strict_types=1);

namespace App\Command;

use App\Util\ModuleUtils;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DownloadNativeModulesCommand extends Command
{
    protected static $defaultName = 'downloadNativeModules';

    private ModuleUtils $moduleUtils;

    public function __construct(ModuleUtils $moduleUtils)
    {
        parent::__construct();
        $this->moduleUtils = $moduleUtils;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $modules = $this->moduleUtils->getNativeModuleList();
        $output->writeln(sprintf('<info>%d modules found</info>', count($modules)));

        foreach ($modules as $module) {
            $versions = $this->moduleUtils->getVersions($module);
            foreach ($versions as $version) {
                $output->writeln(sprintf('<info>Downloading %s %s</info>', $module, $version->getVersion()));
                $this->moduleUtils->download($module, $version);
            }
        }

        return static::SUCCESS;
    }
}
