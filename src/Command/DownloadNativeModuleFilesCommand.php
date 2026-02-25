<?php

declare(strict_types=1);

namespace App\Command;

use App\ModuleCollection;
use App\Util\ModuleUtils;
use Google\Cloud\Core\Exception\NotFoundException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DownloadNativeModuleFilesCommand extends Command
{
    protected static $defaultName = 'downloadNativeModuleFiles';

    private ModuleUtils $moduleUtils;

    public function __construct(ModuleUtils $moduleUtils)
    {
        parent::__construct();
        $this->moduleUtils = $moduleUtils;
    }

    protected function configure(): void
    {
        parent::configure();
        $this->addOption('ignore-bucket-failure', null, InputOption::VALUE_NONE, 'Ignore bucket existing modules');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $modules = $this->moduleUtils->getNativeModuleList();
        $output->writeln(sprintf('<info>%d modules found</info>', count($modules)));
        $ignoreBucketFailure = $input->getOption('ignore-bucket-failure');
        try {
            $existing = $this->moduleUtils->getFromBucket();
        } catch (NotFoundException $e) {
            if (!$ignoreBucketFailure) {
                throw $e;
            }

            // Catch error so that you can run this command locally without linking to a bucket (only Github token is enough)
            $output->writeln(sprintf('<error>Could not download list of modules from bucket %s</error>', $e->getMessage()));
            // Empty list when bucket is not reachable
            $existing = new ModuleCollection();
        }

        foreach ($modules as $module) {
            $versions = $this->moduleUtils->getVersions($module);
            foreach ($versions as $version) {
                $output->writeln(sprintf('<info>Downloading %s %s</info>', $module, $version->getTag()));
                $this->moduleUtils->downloadMainClass($module, $version);
                if (!$existing->contains($module, $version)) {
                    $output->writeln(sprintf('<info>Downloading new version of %s (%s)</info>', $module, $version->getTag()));
                    $this->moduleUtils->download($module, $version);
                    $this->moduleUtils->extractLogo($module, $version);
                }
                if ($this->moduleUtils->isModuleCompatibleWithMinPrestaShopVersion($module, $version)) {
                    break;
                }
            }
        }

        return static::SUCCESS;
    }
}
