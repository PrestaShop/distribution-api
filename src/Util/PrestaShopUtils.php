<?php

declare(strict_types=1);

namespace App\Util;

use App\Exception\NoAssetException;
use App\Model\PrestaShop;
use Github\Client as GithubClient;
use Google\Cloud\Storage\Bucket;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt;
use PhpParser\ParserFactory;
use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpClient\Response\StreamableInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;
use ZipArchive;

abstract class PrestaShopUtils
{
    private HttpClientInterface $client;
    protected GithubClient $githubClient;
    protected string $repositoryFullName;
    protected string $prestaShopDir;
    protected string $prestaShopMinVersion;
    private PublicDownloadUrlProvider $publicDownloadUrlProvider;
    private Bucket $bucket;
    private ReleaseNoteUtils $releaseNoteUtils;
    /**
     * @var array<string, array{php_min_version: string, php_max_version: string}>|null
     */
    private ?array $prestashop17PhpCompatData = null;

    public function __construct(
        GithubClient $githubClient,
        HttpClientInterface $client,
        Bucket $bucket,
        PublicDownloadUrlProvider $publicDownloadUrlProvider,
        ReleaseNoteUtils $releaseNoteUtils,
        string $repositoryFullName,
        string $prestaShopMinVersion,
        string $prestaShopDir,
    ) {
        $this->githubClient = $githubClient;
        $this->client = $client;
        $this->bucket = $bucket;
        $this->publicDownloadUrlProvider = $publicDownloadUrlProvider;
        $this->releaseNoteUtils = $releaseNoteUtils;
        $this->repositoryFullName = $repositoryFullName;
        $this->prestaShopMinVersion = $prestaShopMinVersion;
        $this->prestaShopDir = $prestaShopDir;
    }

    /**
     * @param array{'tag_name': string, 'assets': array<array{'name': string, 'browser_download_url': string}>} $item
     */
    abstract protected function buildModelFromRepository(mixed $item): PrestaShop;

    /**
     * @return PrestaShop[]
     */
    public function getVersions(): array
    {
        $page = 1;
        $versions = [];
        $releasesApi = $this->githubClient->repo()->releases();
        [$owner, $repo] = explode('/', $this->repositoryFullName, 2);
        while (count($results = $releasesApi->all($owner, $repo, ['page' => $page++])) > 0) {
            $versions = array_merge($versions, array_filter(
                $results,
                fn ($item) => $this->hasZipAsset($item) && $this->isVersionGreaterThanOrEqualToMin($item['tag_name'])
            ));
        }

        return array_map(fn ($item) => $this->buildModelFromRepository($item), $versions);
    }

    /**
     * @return PrestaShop[]
     */
    public function getLocalVersions(): array
    {
        $versions = [];
        $exclude = ['.', '..'];
        if (!is_dir($this->prestaShopDir) || !$prestaShopScandir = scandir($this->prestaShopDir)) {
            return [];
        }
        foreach ($prestaShopScandir as $prestaShopVersion) {
            if (in_array($prestaShopVersion, $exclude) || !is_dir($this->prestaShopDir . '/' . $prestaShopVersion)) {
                continue;
            }
            $versionPath = $this->prestaShopDir . '/' . $prestaShopVersion;
            if (!is_file($versionPath . '/prestashop.zip')) {
                continue;
            }

            $completeVersion = VersionUtils::parseVersion($prestaShopVersion);
            $distributionVersion = $completeVersion['distribution'];
            $distribution = $distributionVersion ? PrestaShop::DISTRIBUTION_CLASSIC : PrestaShop::DISTRIBUTION_OPEN_SOURCE;
            $prestashopVersion = $completeVersion['base'] . ($completeVersion['stability'] ? '-' . $completeVersion['stability'] : '');

            $versions[] = $this->buildModelFromLocal($prestashopVersion, $distribution, $versionPath, $distributionVersion);
        }

        return $versions;
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function download(PrestaShop $prestaShop): void
    {
        $versionPath = $prestaShop->getCompleteVersion();

        $path = $this->prestaShopDir . '/' . $versionPath;
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        if ($prestaShop->getGithubZipUrl() === null) {
            throw new RuntimeException(sprintf('Unable to download PrestaShop %s zip because it has no Github url', $prestaShop->getVersion()));
        }

        /** @var StreamableInterface $response */
        $response = $this->client->request('GET', $prestaShop->getGithubZipUrl());
        file_put_contents($path . '/prestashop.zip', $response->toStream());

        if ($prestaShop->getGithubXmlUrl() !== null) {
            /** @var StreamableInterface $response */
            $response = $this->client->request('GET', $prestaShop->getGithubXmlUrl());
            file_put_contents($path . '/prestashop.xml', $response->toStream());
        }
    }

    /**
     * @param array{'tag_name': string, 'assets': array<array{'name': string, 'browser_download_url': string}>} $item
     */
    protected function hasZipAsset(array $item): bool
    {
        try {
            $this->getZipAssetUrl($item);
        } catch (NoAssetException) {
            return false;
        }

        return true;
    }

    /**
     * @param array{'tag_name': string, 'assets': array<array{'name': string, 'browser_download_url': string}>} $item
     */
    protected function getZipAssetUrl(array $item): string
    {
        $zipName = $this->getZipName($item);
        foreach ($item['assets'] as $asset) {
            if ($asset['name'] === $zipName) {
                return $asset['browser_download_url'];
            }
        }

        throw new NoAssetException('No zip asset found', NoAssetException::NO_ZIP_ASSET);
    }

    /**
     * @param array{'tag_name': string, 'assets': array<array{'name': string, 'browser_download_url': string}>} $item
     */
    protected function getXmlAssetUrl(array $item): string
    {
        $zipName = $this->getXmlName($item);
        foreach ($item['assets'] as $asset) {
            if ($asset['name'] === $zipName) {
                return $asset['browser_download_url'];
            }
        }

        throw new NoAssetException('No xml asset found', NoAssetException::NO_XML_ASSET);
    }

    /**
     * @param array{'tag_name': string, 'assets': array<array{'name': string, 'browser_download_url': string}>} $item
     */
    private function getZipName(array $item): string
    {
        return sprintf('prestashop_%s.zip', $item['tag_name']);
    }

    /**
     * @param array{'tag_name': string, 'assets': array<array{'name': string, 'browser_download_url': string}>} $item
     */
    private function getXmlName(array $item): string
    {
        return sprintf('prestashop_%s.xml', $item['tag_name']);
    }

    /**
     * @return PrestaShop[]
     */
    public function getVersionsFromBucket(): array
    {
        try {
            /** @var array<array<string, string>> $prestaShopsJson */
            $prestaShopsJson = json_decode($this->bucket->object('prestashop.json')->downloadAsString(), true) ?: [];
        } catch (Throwable) {
            $prestaShopsJson = [];
        }
        $prestaShops = [];

        foreach ($prestaShopsJson as $prestaShopJson) {
            if (!$this->isVersionGreaterThanOrEqualToMin($prestaShopJson['version'])) {
                continue;
            }
            if (empty($prestaShopJson['xml_download_url']) || empty($prestaShopJson['zip_md5']) || empty($prestaShopJson['distribution'])) {
                // Refresh the details if a version was added from an old version of the API
                continue;
            }
            if (empty($prestaShopJson['release_notes_url']) && $prestaShopJson['stability'] === 'stable') {
                // Refresh the details if a stable version lacks its release notes.
                continue;
            }
            $prestashop = new PrestaShop(
                $prestaShopJson['version'],
                $prestaShopJson['distribution'],
                $prestaShopJson['distribution_version'],
            );
            $prestashop->setMaxPhpVersion($prestaShopJson['php_max_version']);
            $prestashop->setMinPhpVersion($prestaShopJson['php_min_version']);
            $prestashop->setZipMD5($prestaShopJson['zip_md5']);
            $prestashop->setZipDownloadUrl($this->publicDownloadUrlProvider->getPrestaShopZipDownloadUrl($prestashop->getCompleteVersion(), $prestashop->getDistribution()));
            $prestashop->setXmlDownloadUrl($this->publicDownloadUrlProvider->getPrestaShopXmlDownloadUrl($prestashop->getCompleteVersion(), $prestashop->getDistribution()));
            $prestashop->setReleaseNoteUrl($this->releaseNoteUtils->getReleaseNote($prestashop->getCompleteVersion()));
            $prestaShops[] = $prestashop;
        }

        return $prestaShops;
    }

    protected function buildModelFromLocal(string $prestaShopVersion, string $distribution, string $versionPath, ?string $distributionVersion): PrestaShop
    {
        $prestashop = new PrestaShop($prestaShopVersion, $distribution, $distributionVersion);
        $this->setVersionsCompat($prestashop);
        $prestashop->setZipMD5(md5_file($versionPath . '/prestashop.zip') ?: null);
        $prestashop->setZipDownloadUrl($this->publicDownloadUrlProvider->getPrestaShopZipDownloadUrl($prestashop->getCompleteVersion(), $prestashop->getDistribution()));
        $prestashop->setReleaseNoteUrl($this->releaseNoteUtils->getReleaseNote($prestashop->getCompleteVersion()));
        if (is_file($versionPath . '/prestashop.xml')) {
            $prestashop->setXmlDownloadUrl($this->publicDownloadUrlProvider->getPrestaShopXmlDownloadUrl($prestashop->getCompleteVersion(), $prestashop->getDistribution()));
        }

        return $prestashop;
    }

    private function setVersionsCompat(PrestaShop $prestaShop): void
    {
        if (version_compare($prestaShop->getVersion(), '8', '<')) {
            $versionCompat = $this->getPhpVersionCompatFromJson($prestaShop->getVersion());
            $prestaShop->setMinPhpVersion($versionCompat['php_min_version']);
            $prestaShop->setMaxPhpVersion($versionCompat['php_max_version']);

            return;
        }

        $version = $prestaShop->getCompleteVersion();
        $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
        $installZip = new ZipArchive();
        $installZip->open($this->prestaShopDir . '/' . $version . '/prestashop.zip');
        $installZip->extractTo($this->prestaShopDir . '/' . $version . '/prestashop/');
        $installZip->close();

        $installZip->open($this->prestaShopDir . '/' . $version . '/prestashop/prestashop.zip');
        $content = $installZip->getFromName('install/install_version.php');
        $installZip->close();

        (new Filesystem())->remove($this->prestaShopDir . '/' . $version . '/prestashop');

        if (!$content) {
            return;
        }
        $parsed = $parser->parse($content) ?? [];

        foreach ($parsed as $item) {
            if ($this->nodeHasDefine($item, '_PS_INSTALL_MINIMUM_PHP_VERSION_')) {
                $prestaShop->setMinPhpVersion($this->getDefineValue($item));
            } elseif ($this->nodeHasDefine($item, '_PS_INSTALL_MAXIMUM_PHP_VERSION_')) {
                $prestaShop->setMaxPhpVersion($this->getDefineValue($item));
            }
        }
    }

    /**
     * Load json prestashop 1.7 compatibility and assign it to class variable.
     *
     * @return void
     */
    private function loadPrestashop17PhpCompatJson(): void
    {
        $jsonPath = __DIR__ . '/../../resources/json/prestashop17PhpCompat.json';

        if (!file_exists($jsonPath)) {
            throw new RuntimeException("JSON file not found at : $jsonPath");
        }

        $jsonContent = file_get_contents($jsonPath);
        if ($jsonContent === false) {
            throw new RuntimeException("Failed to read JSON file at : $jsonPath");
        }

        /** @var array<string, array{php_min_version: string, php_max_version: string}> $ps17Compat */
        $ps17Compat = json_decode($jsonContent, true);
        if (!is_array($ps17Compat)) {
            throw new RuntimeException("Invalid JSON structure in file: $jsonPath");
        }

        $this->prestashop17PhpCompatData = $ps17Compat;
    }

    /**
     * @return array{ php_min_version: string|null, php_max_version: string|null }
     */
    private function getPhpVersionCompatFromJson(string $prestaShopVersion): array
    {
        if ($this->prestashop17PhpCompatData === null) {
            $this->loadPrestashop17PhpCompatJson();
        }

        $semverVersion = (new VersionUtils())->formatVersionToSemver($prestaShopVersion);

        return $this->prestashop17PhpCompatData[$semverVersion] ?? ['php_min_version' => null, 'php_max_version' => null];
    }

    private function nodeHasDefine(Stmt $node, string $define): bool
    {
        if (
            $node instanceof Stmt\Expression
            && $node->expr instanceof FuncCall
            && $node->expr->name instanceof Name
            && $node->expr->name->parts[0] === 'define'
            && $node->expr->getArgs()[0]->value instanceof String_
            && $node->expr->getArgs()[0]->value->value === $define
        ) {
            return true;
        }

        return false;
    }

    private function getDefineValue(Stmt $node): string
    {
        if (
            !$node instanceof Stmt\Expression
            || !$node->expr instanceof FuncCall
            || !$node->expr->getArgs()[1]->value instanceof String_
        ) {
            return '';
        }

        return (string) $node->expr->getArgs()[1]->value->value;
    }

    private function isVersionGreaterThanOrEqualToMin(string $version): bool
    {
        return version_compare($version, $this->prestaShopMinVersion, '>=');
    }
}
