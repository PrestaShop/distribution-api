<?php

declare(strict_types=1);

namespace App\Command;

use App\Model\Module;
use App\Util\ModuleUtils;
use Github\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class UpdateModuleConfigFilesCommand extends Command
{
    private ModuleUtils $utils;
    private Client $client;
    private string $moduleListRepository;

    protected static $defaultName = 'updateModuleConfigFiles';

    public function __construct(ModuleUtils $moduleUtils, Client $client, string $moduleListRepository)
    {
        parent::__construct();
        $this->utils = $moduleUtils;
        $this->client = $client;
        $this->moduleListRepository = $moduleListRepository;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $modules = $this->utils->getLocalModules();
        foreach ($modules as $module) {
            $this->setVersionData($module);
            $this->generateConfigFile($module, $output);
        }

        return self::SUCCESS;
    }

    private function setVersionData(Module $module): void
    {
        foreach ($module->getVersions() as $version) {
            $this->utils->setVersionData($module->getName(), $version);
        }
    }

    private function generateConfigFile(Module $module, OutputInterface $output): void
    {
        $filename = $module->getName() . '.yml';
        $output->writeln(sprintf('<info>Checking %s</info>', $filename));

        [$username, $repo] = explode('/', $this->moduleListRepository);
        /** @var array<string, string> $currentFile */
        $currentFile = $this->client->repo()->contents()->show($username, $repo, $filename);
        $currentContent = base64_decode($currentFile['content']);

        /** @var array<string, array<string, string>> $versions */
        $versions = Yaml::parse($currentContent);

        foreach ($module->getVersions() as $version) {
            if (isset($versions[$version->getTag()])) {
                continue;
            }

            $versions[$version->getTag()] = [
                'min' => $version->getVersionCompliancyMin(),
                'max' => $version->getVersionCompliancyMax(),
            ];
        }
        ksort($versions);

        $content = Yaml::dump($versions);
        if ($currentContent !== $content) {
            $output->writeln(sprintf('<info>Change detected, committing %s</info>', $filename));
            $this->client->repo()->contents()->update(
                $username,
                $repo,
                $filename,
                $content,
                sprintf('%s file update', $filename),
                $currentFile['sha']
            );
        } else {
            $output->writeln('<info>No change detected</info>');
        }
    }
}
