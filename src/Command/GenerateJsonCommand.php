<?php

declare(strict_types=1);

namespace App\Command;

use App\Util\ModuleUtils;
use Github\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateJsonCommand extends Command
{
    protected static $defaultName = 'generateJson';

    private ModuleUtils $moduleUtils;
    private Client $githubClient;
    private string $jsonDir;

    public function __construct(
        ModuleUtils $moduleUtils,
        Client $client,
        string $jsonDir = __DIR__ . '/../../public/json'
    ) {
        parent::__construct();
        $this->moduleUtils = $moduleUtils;
        $this->githubClient = $client;
        $this->jsonDir = $jsonDir;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $modules = $this->moduleUtils->getModules();
        foreach ($modules as $module => $versions) {
            if (!isset($infos[$module])) {
                $infos[$module] = [];
            }
            foreach ($versions as $version) {
                $output->writeln(sprintf('<info>Parsing module %s %s</info>', $module, $version));
                $infos[$module][] = $this->moduleUtils->getInformation($module, $version);
            }
        }

        $prestashopVersions = $this->getPrestaShopVersions();
        if (empty($modules) || empty($prestashopVersions)) {
            $output->writeln('<error>No module or PrestaShop version found!</error>');
            $output->writeln('<question>Did you run the `downloadNativeModule` command?</question>');

            return static::FAILURE;
        }

        $output->writeln('<info>Generating main modules.json</info>');
        $this->generateModulesJson($infos);
        $this->generatePrestaShopModulesJson($infos, $prestashopVersions, $output);

        return static::SUCCESS;
    }

    /**
     * @return array<string>
     */
    private function getPrestaShopVersions(): array
    {
        $page = 1;
        $versions = [];
        $releasesApi = $this->githubClient->repo()->releases();
        while (count($results = $releasesApi->all('PrestaShop', 'PrestaShop', ['page' => $page++])) > 0) {
            $versions = array_merge($versions, array_filter($results, fn ($item) => !empty($item['assets'])));
        }

        return array_map(fn ($item) => $item['tag_name'], $versions);
    }

    /**
     * @param array<string, array<int, array<string, string|null>>> $moduleInfos
     */
    private function generateModulesJson(array $moduleInfos): void
    {
        file_put_contents($this->jsonDir . '/modules.json', json_encode($moduleInfos));
    }

    /**
     * @param array<string, array<int, array{'version': string, 'versionCompliancyMin': string|null, 'versionCompliancyMax': string|null}>> $moduleInfos
     * @param non-empty-array<string> $prestashopVersions
     */
    private function generatePrestaShopModulesJson(
        array $moduleInfos,
        array $prestashopVersions,
        OutputInterface $output
    ): void {
        $infos = [];
        foreach ($prestashopVersions as $prestashopVersion) {
            $infos[$prestashopVersion] = [];
            foreach ($moduleInfos as $module => $versions) {
                foreach ($versions as $version) {
                    if (null === $version['versionCompliancyMin']) {
                        continue;
                    }
                    if (
                        version_compare($prestashopVersion, $version['versionCompliancyMin'], '>')
                        && (
                            empty($infos[$prestashopVersion][$module])
                            || version_compare($version['version'], $infos[$prestashopVersion][$module]['version'], '>')
                        )
                    ) {
                        $infos[$prestashopVersion][$module] = $version;
                    }
                }
            }
        }

        $latestPrestashopVersion = $this->getLatestPrestashopVersion($prestashopVersions);
        $output->writeln(sprintf('<info>Latest version of PrestaShop is version %s</info>', $latestPrestashopVersion));

        foreach ($infos as $prestashopVersion => $modules) {
            $output->writeln(sprintf('<info>Generate json for PrestaShop %s</info>', $prestashopVersion));
            $prestashopVersionPath = $this->jsonDir . '/' . $prestashopVersion;
            if (!is_dir($prestashopVersionPath)) {
                mkdir($prestashopVersionPath);
            }

            foreach ($modules as $name => $module) {
                $basePath = $this->jsonDir . '/' . $name;
                if (!is_dir($basePath)) {
                    mkdir($basePath);
                }
                file_put_contents($basePath . '/' . $prestashopVersion . '.json', json_encode($module));
                if ($prestashopVersion === $latestPrestashopVersion) {
                    file_put_contents($basePath . '/latest.json', json_encode($module));
                }
            }

            file_put_contents($prestashopVersionPath . '/modules.json', json_encode($modules));
        }
    }

    /**
     * @param non-empty-array<string> $prestashopVersions
     */
    private function getLatestPrestashopVersion(array $prestashopVersions): string
    {
        $latest = null;
        foreach ($prestashopVersions as $prestashopVersion) {
            if ($latest === null || version_compare($prestashopVersion, $latest, '>')) {
                $latest = $prestashopVersion;
            }
        }

        return $latest;
    }
}
