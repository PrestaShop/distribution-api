<?php

declare(strict_types=1);

namespace App\Util;

use App\Exception\NoAssetException;
use App\Model\PrestaShop;
use Github\Client as GithubClient;
use Google\Cloud\Storage\Bucket;
use GuzzleHttp\Client;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt;
use PhpParser\ParserFactory;
use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;
use Throwable;
use ZipArchive;

class PrestaShopUtils
{
    private GithubClient $githubClient;
    private Client $client;
    private Bucket $bucket;
    private string $prestaShopDir;
    private string $prestaShopMinVersion;
    private PublicDownloadUrlProvider $publicDownloadUrlProvider;

    public function __construct(
        GithubClient $githubClient,
        Client $client,
        Bucket $bucket,
        PublicDownloadUrlProvider $publicDownloadUrlProvider,
        string $prestaShopMinVersion,
        string $prestaShopDir,
    ) {
        $this->githubClient = $githubClient;
        $this->client = $client;
        $this->bucket = $bucket;
        $this->publicDownloadUrlProvider = $publicDownloadUrlProvider;
        $this->prestaShopMinVersion = $prestaShopMinVersion;
        $this->prestaShopDir = $prestaShopDir;
    }

    public function download(PrestaShop $prestaShop): void
    {
        $path = $this->prestaShopDir . '/' . $prestaShop->getVersion();
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        if ($prestaShop->getGithubZipUrl() === null) {
            throw new RuntimeException(sprintf('Unable to download PrestaShop %s zip because it has no Github url', $prestaShop->getVersion()));
        }

        $response = $this->client->get($prestaShop->getGithubZipUrl());
        file_put_contents($path . '/prestashop.zip', $response->getBody());

        if ($prestaShop->getGithubXmlUrl() !== null) {
            $response = $this->client->get($prestaShop->getGithubXmlUrl());
            file_put_contents($path . '/prestashop.xml', $response->getBody());
        }
    }

    /**
     * @return PrestaShop[]
     */
    public function getVersions(): array
    {
        $page = 1;
        $versions = [];
        $releasesApi = $this->githubClient->repo()->releases();
        while (count($results = $releasesApi->all('PrestaShop', 'PrestaShop', ['page' => $page++])) > 0) {
            $versions = array_merge($versions, array_filter(
                $results,
                fn ($item) => $this->hasZipAsset($item) && $this->isVersionGreaterThanOrEqualToMin($item['tag_name'])
            ));
        }

        return array_map(function ($item): PrestaShop {
            $prestaShop = new PrestaShop($item['tag_name']);
            $prestaShop->setGithubZipUrl($this->getZipAssetUrl($item));
            try {
                $prestaShop->setGithubXmlUrl($this->getXmlAssetUrl($item));
            } catch (NoAssetException) {
            }

            return $prestaShop;
        }, $versions);
    }

    /**
     * @param array{'tag_name': string, 'assets': array<array{'name': string, 'browser_download_url': string}>} $item
     */
    private function hasZipAsset(array $item): bool
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
    private function getZipAssetUrl(array $item): string
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
    private function getXmlAssetUrl(array $item): string
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
            if (empty($prestaShopJson['xml_download_url']) || empty($prestaShopJson['zip_md5'])) {
                continue;
            }
            $prestashop = new PrestaShop($prestaShopJson['version']);
            $prestashop->setMaxPhpVersion($prestaShopJson['php_max_version']);
            $prestashop->setMinPhpVersion($prestaShopJson['php_min_version']);
            $prestashop->setZipMD5($prestaShopJson['zip_md5']);
            $prestashop->setZipDownloadUrl($this->publicDownloadUrlProvider->getPrestaShopZipDownloadUrl($prestaShopJson['version']));
            $prestashop->setXmlDownloadUrl($this->publicDownloadUrlProvider->getPrestaShopXmlDownloadUrl($prestaShopJson['version']));
            $prestaShops[] = $prestashop;
        }

        return $prestaShops;
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
            $prestashop = new PrestaShop($prestaShopVersion);
            $this->setVersionsCompat($prestashop);
            $prestashop->setZipMD5(md5_file($versionPath . '/prestashop.zip') ?: null);
            $prestashop->setZipDownloadUrl($this->publicDownloadUrlProvider->getPrestaShopZipDownloadUrl($prestaShopVersion));
            if (is_file($versionPath . '/prestashop.xml')) {
                $prestashop->setXmlDownloadUrl($this->publicDownloadUrlProvider->getPrestaShopXmlDownloadUrl($prestaShopVersion));
            }
            $versions[] = $prestashop;
        }

        return $versions;
    }

    private function setVersionsCompat(PrestaShop $prestaShop): void
    {
        $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
        $installZip = new ZipArchive();
        $installZip->open($this->prestaShopDir . '/' . $prestaShop->getVersion() . '/prestashop.zip');
        $installZip->extractTo($this->prestaShopDir . '/' . $prestaShop->getVersion() . '/prestashop/');
        $installZip->close();

        $installZip->open($this->prestaShopDir . '/' . $prestaShop->getVersion() . '/prestashop/prestashop.zip');
        $content = $installZip->getFromName('install/install_version.php');
        $installZip->close();

        (new Filesystem())->remove($this->prestaShopDir . '/' . $prestaShop->getVersion() . '/prestashop');

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

    private function isVersionGreaterThanOrEqualToMin(string $version): bool
    {
        return version_compare($version, $this->prestaShopMinVersion, '>=');
    }

    private function nodeHasDefine(Stmt $node, string $define): bool
    {
        if (
            $node instanceof Stmt\Expression &&
            $node->expr instanceof FuncCall &&
            $node->expr->name instanceof Name &&
            $node->expr->name->parts[0] === 'define' &&
            $node->expr->getArgs()[0]->value instanceof String_ &&
            $node->expr->getArgs()[0]->value->value === $define
        ) {
            return true;
        }

        return false;
    }

    private function getDefineValue(Stmt $node): string
    {
        if (
            !$node instanceof Stmt\Expression ||
            !$node->expr instanceof FuncCall ||
            !$node->expr->getArgs()[1]->value instanceof String_
        ) {
            return '';
        }

        return (string) $node->expr->getArgs()[1]->value->value;
    }
}
