<?php

declare(strict_types=1);

namespace App\Command;

use App\Util\ModuleUtils;
use Github\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DownloadNativeModulesCommand extends Command
{
    protected static $defaultName = 'downloadNativeModules';

    private Client $client;
    private ModuleUtils $moduleUtils;

    public function __construct(Client $client, ModuleUtils $moduleUtils)
    {
        parent::__construct();
        $this->client = $client;
        $this->moduleUtils = $moduleUtils;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $modules = $this->getNativeModules();
        $output->writeln(sprintf('<info>%s modules found</info>', count($modules)));

        foreach ($modules as $module) {
            $versions = $this->moduleUtils->getVersions($module);
            foreach ($versions as $version) {
                $output->writeln(sprintf('<info>Downloading %s %s</info>', $module, $version['version']));
                $this->moduleUtils->download($module, $version);
            }
        }

        return static::SUCCESS;
    }

    /**
     * @return array<string>
     */
    private function getNativeModules(): array
    {
        $tree = $this->client->git()->trees()->show('PrestaShop', 'PrestaShop-modules', 'master');

        $modules = array_filter($tree['tree'], fn ($item) => $item['type'] === 'commit');

        return array_map(fn ($item) => $item['path'], $modules);
    }
}
