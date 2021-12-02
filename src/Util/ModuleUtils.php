<?php

declare(strict_types=1);

namespace App\Util;

use App\Model\Module;
use App\Model\Version;
use Github\Client as GithubClient;
use GuzzleHttp\Client;
use Psssst\ModuleParser;
use ZipArchive;

class ModuleUtils
{
    private const PS_VERSION = '_PS_VERSION_';

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
     * @return Version[]
     */
    public function getVersions(string $moduleName, bool $withAssetOnly = true): array
    {
        $page = 1;
        $versions = [];
        $releasesApi = $this->githubClient->repo()->releases();
        while (count($results = $releasesApi->all('PrestaShop', $moduleName, ['page' => $page++])) > 0) {
            $versions = array_merge(
                $versions,
                array_filter($results, fn ($item) => (!empty($item['assets'] || !$withAssetOnly) && !$item['draft']))
            );
        }

        return array_map(fn ($item) => new Version(
            $item['tag_name'],
            !empty($item['assets']) ? current($item['assets'])['browser_download_url'] : null
        ), $versions);
    }

    public function download(string $moduleName, Version $version): void
    {
        if ($version->getUrl() === null) {
            return;
        }

        $path = join('/', [$this->moduleDir, $moduleName, $version->getVersion()]);
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        $response = $this->client->get($version->getUrl());
        file_put_contents($path . '/' . $moduleName . '.zip', $response->getBody());
    }

    /**
     * @return Module[]
     */
    public function getLocalModules(): array
    {
        $modules = [];
        $exclude = ['.', '..'];
        if (!$modulesScandir = scandir($this->moduleDir)) {
            return [];
        }
        foreach ($modulesScandir as $moduleName) {
            if (in_array($moduleName, $exclude) || !is_dir($this->moduleDir . '/' . $moduleName)) {
                continue;
            }
            if (!$moduleVersionsScandir = scandir($this->moduleDir . '/' . $moduleName)) {
                continue;
            }
            $module = new Module($moduleName);
            foreach ($moduleVersionsScandir as $version) {
                if (in_array($version, $exclude)) {
                    continue;
                }
                $module->addVersion(new Version($version));
            }
            $modules[] = $module;
        }

        return $modules;
    }

    public function setVersionCompliancy(string $moduleName, Version $version): void
    {
        $filename = join('/', [$this->moduleDir, $moduleName, $version->getVersion(), $moduleName . '.zip']);

        if (!is_dir($this->tmpDir)) {
            mkdir($this->tmpDir, 0777, true);
        }

        $zip = new ZipArchive();
        $zip->open($filename);
        $zip->extractTo($this->tmpDir);
        $zip->close();

        $info = current($this->parser->parseModule($this->tmpDir . '/' . $moduleName));

        $version->setVersionCompliancyMin($info['versionCompliancyMin'] === self::PS_VERSION ? null : $info['versionCompliancyMin']);
        $version->setVersionCompliancyMax($info['versionCompliancyMax'] === self::PS_VERSION ? null : $info['versionCompliancyMax']);
    }

    /**
     * @return array<string>
     */
    public function getNativeModuleList(): array
    {
        $tree = $this->githubClient->git()->trees()->show('PrestaShop', 'PrestaShop-modules', 'master');

        $modules = array_filter($tree['tree'], fn ($item) => $item['type'] === 'commit');

        return array_map(fn ($item) => $item['path'], $modules);
    }
}
