<?php

declare(strict_types=1);

namespace App\Util;

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

        if ($prestaShop->getGithubUrl() === null) {
            throw new RuntimeException(sprintf('Unable to download PrestaShop %s because it has no Github url', $prestaShop->getVersion()));
        }

        $response = $this->client->get($prestaShop->getGithubUrl());
        file_put_contents($path . '/prestashop.zip', $response->getBody());
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
                fn ($item) => !empty($item['assets']) && $this->isVersionGreaterThanOrEqualToMin($item['tag_name'])
            ));
        }

        return array_map(function ($item): PrestaShop {
            $prestaShop = new PrestaShop($item['tag_name']);
            $prestaShop->setGithubUrl(current($item['assets'])['browser_download_url']);

            return $prestaShop;
        }, $versions);
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
            $prestashop = new PrestaShop($prestaShopJson['version']);
            $prestashop->setMaxPhpVersion($prestaShopJson['php_max_version']);
            $prestashop->setMinPhpVersion($prestaShopJson['php_min_version']);
            $prestashop->setDownloadUrl($this->publicDownloadUrlProvider->getPrestaShopDownloadUrl($prestaShopJson['version']));
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
            $prestashop = new PrestaShop($prestaShopVersion);
            $this->setVersionsCompat($prestashop);
            $prestashop->setDownloadUrl($this->publicDownloadUrlProvider->getPrestaShopDownloadUrl($prestaShopVersion));
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
