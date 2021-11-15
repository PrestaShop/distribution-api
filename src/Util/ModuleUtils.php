<?php

declare(strict_types=1);

namespace App\Util;

use Github\Client as GithubClient;
use GuzzleHttp\Client;
use Psssst\ModuleParser;
use ZipArchive;

class ModuleUtils
{
    private ModuleParser $parser;
    private Client $client;
    private GithubClient $githubClient;
    private string $moduleDir;
    private string $tmpDir;

    public function __construct(
        ModuleParser $moduleParser,
        Client $client,
        GithubClient $githubClient,
        string $moduleDir = __DIR__ . '/../../public/modules',
        string $tmpDir = __DIR__ . '/../../var/tmp'
    ) {
        $this->parser = $moduleParser;
        $this->client = $client;
        $this->githubClient = $githubClient;
        $this->moduleDir = $moduleDir;
        $this->tmpDir = $tmpDir;
    }

    public function getModuleDir(): string
    {
        return $this->moduleDir;
    }

    /**
     * @return array<array<string, string>>
     */
    public function getVersions(string $moduleName): array
    {
        $page = 1;
        $versions = [];
        $releasesApi = $this->githubClient->repo()->releases();
        while (count($results = $releasesApi->all('PrestaShop', $moduleName, ['page' => $page++])) > 0) {
            $versions = array_merge(
                $versions,
                array_filter($results, fn ($item) => !empty($item['assets'] && !$item['draft']))
            );
        }

        return array_map(fn ($item) => [
            'version' => $item['tag_name'],
            'url' => current($item['assets'])['browser_download_url'],
        ], $versions);
    }

    /**
     * @param array<string, string> $version
     */
    public function download(string $module, array $version): void
    {
        $path = join('/', [$this->moduleDir, $module, $version['version']]);
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        $response = $this->client->get($version['url']);
        file_put_contents($path . '/' . $module . '.zip', $response->getBody());
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function getModules(): array
    {
        $modules = [];
        $exclude = ['.', '..'];
        if (!$modulesScandir = scandir($this->moduleDir)) {
            return [];
        }
        foreach ($modulesScandir as $module) {
            if (in_array($module, $exclude) || !is_dir($this->moduleDir . '/' . $module)) {
                continue;
            }
            if (!$moduleVersionsScandir = scandir($this->moduleDir . '/' . $module)) {
                continue;
            }
            $modules[$module] = [];
            foreach ($moduleVersionsScandir as $version) {
                if (in_array($version, $exclude)) {
                    continue;
                }
                $modules[$module][] = $version;
            }
        }

        return $modules;
    }

    /**
     * @return array{'version': string, 'versionCompliancyMin': string|null, 'versionCompliancyMax': string|null}
     */
    public function getInformation(string $moduleName, string $version): array
    {
        $filename = join('/', [$this->moduleDir, $moduleName, $version, $moduleName . '.zip']);

        if (!is_dir($this->tmpDir)) {
            mkdir($this->tmpDir, 0777, true);
        }

        $zip = new ZipArchive();
        $zip->open($filename);
        $zip->extractTo($this->tmpDir);
        $zip->close();

        $info = current($this->parser->parseModule($this->tmpDir . '/' . $moduleName));

        return [
            'version' => $info['version'],
            'versionCompliancyMin' => $info['versionCompliancyMin'],
            'versionCompliancyMax' => $info['versionCompliancyMax'],
        ];
    }
}
