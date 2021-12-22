<?php

declare(strict_types=1);

namespace App\Util;

use App\Model\Module;
use App\Model\Version;
use Github\Client as GithubClient;
use GuzzleHttp\Client;
use Psssst\ModuleParser;

class ModuleUtils
{
    private const PS_VERSION = '_PS_VERSION_';
    private const GITHUB_MAIN_CLASS_ENDPOINT = 'https://raw.githubusercontent.com/PrestaShop/%s/%s/%s.php';

    private ModuleParser $parser;
    private Client $client;
    private GithubClient $githubClient;
    private string $moduleDir;

    public function __construct(
        ModuleParser $moduleParser,
        Client $client,
        GithubClient $githubClient,
        string $moduleDir
    ) {
        $this->parser = $moduleParser;
        $this->client = $client;
        $this->githubClient = $githubClient;
        $this->moduleDir = $moduleDir;
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

    public function downloadMainClass(string $moduleName, Version $version): void
    {
        $path = join('/', [$this->moduleDir, $moduleName, $version->getTag()]);
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        $response = $this->client->get(sprintf(self::GITHUB_MAIN_CLASS_ENDPOINT, $moduleName, $version->getTag(), $moduleName));
        file_put_contents($path . '/' . $moduleName . '.php', $response->getBody());
    }

    /**
     * @return Module[]
     */
    public function getLocalModules(): array
    {
        $modules = [];
        $exclude = ['.', '..'];
        if (!is_dir($this->moduleDir) || !$modulesScandir = scandir($this->moduleDir)) {
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
        $versionDir = join('/', [$this->moduleDir, $moduleName, $version->getTag()]);

        $info = current($this->parser->parseModule($versionDir));

        $version->setVersion($info['version']);
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
