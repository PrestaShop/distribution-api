<?php

declare(strict_types=1);

namespace App\Util;

use App\Model\Module;
use App\Model\Version;
use Github\Client as GithubClient;
use GuzzleHttp\Client;
use Psssst\ModuleParser;
use Symfony\Component\Yaml\Yaml;

class ModuleUtils
{
    public const DOWNLOAD_URL_FILENAME = 'download_url.txt';

    private const PS_VERSION = '_PS_VERSION_';
    private const GITHUB_MAIN_CLASS_ENDPOINT = 'https://raw.githubusercontent.com/PrestaShop/%s/%s/%s.php';
    private const GITHUB_LOGO_ENDPOINT = 'https://raw.githubusercontent.com/PrestaShop/%s/%s/logo.png';

    private ModuleParser $parser;
    private Client $client;
    private GithubClient $githubClient;
    private string $moduleListRepository;
    private string $moduleDir;

    public function __construct(
        ModuleParser $moduleParser,
        Client $client,
        GithubClient $githubClient,
        string $moduleListRepository,
        string $moduleDir
    ) {
        $this->parser = $moduleParser;
        $this->client = $client;
        $this->githubClient = $githubClient;
        $this->moduleListRepository = $moduleListRepository;
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
        $this->saveDownloadUrl($moduleName, $version);
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
                $url = $this->getDownloadUrl($moduleName, $version);
                $module->addVersion(new Version($version, $url));
            }
            $modules[] = $module;
        }

        return $modules;
    }

    public function setVersionData(string $moduleName, Version $version): void
    {
        $versionDir = join('/', [$this->moduleDir, $moduleName, $version->getTag()]);

        $info = current($this->parser->parseModule($versionDir));

        $version
            ->setVersion($info['version'])
            ->setVersionCompliancyMin($info['versionCompliancyMin'] === self::PS_VERSION ? null : $info['versionCompliancyMin'])
            ->setVersionCompliancyMax($info['versionCompliancyMax'] === self::PS_VERSION ? null : $info['versionCompliancyMax'])
            ->setTab($info['tab'] ?? null)
            ->setAuthor($info['author'] ?? null)
            ->setIcon($this->getLogoUrl($moduleName, $version))
            ->setDisplayName($info['displayName'] ?? null)
            ->setDescription($info['description'] ?? null)
        ;
    }

    public function overrideVersionCompliancyFromYaml(Module $module): void
    {
        [$username, $repository] = explode('/', $this->moduleListRepository);
        $filename = $module->getName() . '.yml';

        /** @var array<string, string> $file */
        $file = $this->githubClient->repo()->contents()->show($username, $repository, $filename);
        /** @var array<string, array<string, string>> $content */
        $content = Yaml::parse(base64_decode($file['content']));

        foreach ($module->getVersions() as $version) {
            if (isset($content[$version->getTag()])) {
                $version->setVersionCompliancyMax($content[$version->getTag()]['max']);
                $version->setVersionCompliancyMin($content[$version->getTag()]['min']);
            }
        }
    }

    /**
     * @return array<string>
     */
    public function getNativeModuleList(): array
    {
        [$username, $repository] = explode('/', $this->moduleListRepository);

        /** @var array<string, array<string, string>> $files */
        $files = $this->githubClient->repository()->contents()->show($username, $repository);

        $modules = array_filter($files, fn ($item) => str_ends_with($item['name'], '.yml'));

        return array_map(fn ($item) => substr($item['name'], 0, -4), $modules);
    }

    private function saveDownloadUrl(string $moduleName, Version $version): void
    {
        $path = $this->getDownloadUrlFilePath($moduleName, $version->getTag());
        file_put_contents($path, $version->getUrl());
    }

    private function getDownloadUrl(string $moduleName, string $version): ?string
    {
        $path = $this->getDownloadUrlFilePath($moduleName, $version);
        if (!is_file($path)) {
            return null;
        }

        return file_get_contents($path) ?: null;
    }

    private function getDownloadUrlFilePath(string $moduleName, string $version): string
    {
        return join('/', [$this->moduleDir, $moduleName, $version, static::DOWNLOAD_URL_FILENAME]);
    }

    private function getLogoUrl(string $moduleName, Version $version): string
    {
        return sprintf(
            self::GITHUB_LOGO_ENDPOINT,
            $moduleName,
            $version->getTag()
        );
    }
}
