<?php

declare(strict_types=1);

namespace App\Util;

use App\Model\Module;
use App\Model\Version;
use App\ModuleCollection;
use Github\Client as GithubClient;
use Google\Cloud\Storage\Bucket;
use GuzzleHttp\Client;
use Psssst\ModuleParser;
use RuntimeException;
use Symfony\Component\Yaml\Yaml;
use ZipArchive;

class ModuleUtils
{
    private const PS_VERSION = '_PS_VERSION_';
    private const GITHUB_MAIN_CLASS_ENDPOINT = 'https://raw.githubusercontent.com/PrestaShop/%s/%s/%s.php';

    private ModuleParser $parser;
    private Client $client;
    private GithubClient $githubClient;
    private Bucket $bucket;
    private PublicDownloadUrlProvider $publicDownloadUrlProvider;
    private string $moduleListRepository;
    private string $moduleDir;
    private string $prestaShopMinVersion;

    public function __construct(
        ModuleParser $moduleParser,
        Client $client,
        GithubClient $githubClient,
        Bucket $bucket,
        PublicDownloadUrlProvider $publicDownloadUrlProvider,
        string $moduleListRepository,
        string $prestaShopMinVersion,
        string $moduleDir
    ) {
        $this->parser = $moduleParser;
        $this->client = $client;
        $this->githubClient = $githubClient;
        $this->bucket = $bucket;
        $this->publicDownloadUrlProvider = $publicDownloadUrlProvider;
        $this->moduleListRepository = $moduleListRepository;
        $this->prestaShopMinVersion = $prestaShopMinVersion;
        $this->moduleDir = $moduleDir;
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

    public function getFromBucket(): ModuleCollection
    {
        $modules = $this->bucket->objects(['prefix' => 'assets/modules/']);
        $list = [];
        foreach ($modules as $module) {
            $parts = explode('/', $module->info()['name']);
            if (!isset($list[$parts[count($parts) - 3]])) {
                $list[$parts[count($parts) - 3]] = [];
            }
            $list[$parts[count($parts) - 3]][] = $parts[count($parts) - 2];
        }

        $tet = [];
        foreach ($list as $moduleName => $tagNames) {
            $v = [];
            foreach ($tagNames as $tagName) {
                $v[] = new Version($tagName);
            }
            $tet[] = new Module($moduleName, $v);
        }

        return new ModuleCollection(...$tet);
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

    public function download(string $moduleName, Version $version): void
    {
        $path = join('/', [$this->moduleDir, $moduleName, $version->getTag()]);
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        if ($version->getGithubUrl() === null) {
            throw new RuntimeException(sprintf('Unable to download %s %s because it has no Github url', $moduleName, $version->getTag()));
        }

        $response = $this->client->get($version->getGithubUrl());
        file_put_contents($path . '/' . $moduleName . '.zip', $response->getBody());
    }

    public function extractLogo(string $moduleName, Version $version): void
    {
        $path = join('/', [$this->moduleDir, $moduleName, $version->getTag(), $moduleName . '.zip']);
        if (!file_exists($path)) {
            return;
        }

        $moduleZip = new ZipArchive();
        $moduleZip->open($path);
        $icon = $moduleZip->getFromName($moduleName . '/logo.png');
        $moduleZip->close();

        file_put_contents(join('/', [$this->moduleDir, $moduleName, $version->getTag(), 'logo.png']), $icon);
    }

    public function isModuleCompatibleWithMinPrestaShopVersion(string $moduleName, Version $version): bool
    {
        $this->setVersionData($moduleName, $version);
        $this->overrideVersionCompliancyFromYaml(new Module($moduleName, [$version]));

        return $version->getVersionCompliancyMin() !== null
            && version_compare($version->getVersionCompliancyMin(), $this->prestaShopMinVersion, '<=');
    }

    public function getLocalModules(): ModuleCollection
    {
        $modules = [];
        $exclude = ['.', '..'];
        if (!is_dir($this->moduleDir) || !$modulesScandir = scandir($this->moduleDir)) {
            return new ModuleCollection();
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

        return new ModuleCollection(...$modules);
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
            ->setDownloadUrl($this->publicDownloadUrlProvider->getModuleDownloadUrl($moduleName, $version))
            ->setIcon($this->publicDownloadUrlProvider->getModuleIconUrl($moduleName, $version))
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
}
