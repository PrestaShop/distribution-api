<?php

declare(strict_types=1);

namespace App\Util;

use App\Model\PrestaShop;
use Github\Client as GithubClient;
use GuzzleHttp\Client;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt;
use PhpParser\ParserFactory;

class PrestaShopUtils
{
    private const PRESTASHOP_VERSION_COMPAT_ENDPOINT = 'https://raw.githubusercontent.com/PrestaShop/PrestaShop/%s/install-dev/install_version.php';

    private GithubClient $githubClient;
    private Client $client;
    private string $prestaShopDir;
    private string $prestaShopMinVersion;

    public function __construct(
        GithubClient $githubClient,
        Client $client,
        string $prestaShopMinVersion,
        string $prestaShopDir,
    ) {
        $this->githubClient = $githubClient;
        $this->client = $client;
        $this->prestaShopMinVersion = $prestaShopMinVersion;
        $this->prestaShopDir = $prestaShopDir;
    }

    public function download(PrestaShop $prestaShop): void
    {
        $path = $this->prestaShopDir . '/' . $prestaShop->getVersion();
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        $response = $this->client->get(sprintf(self::PRESTASHOP_VERSION_COMPAT_ENDPOINT, $prestaShop->getVersion()));
        file_put_contents($path . '/install_version.php', $response->getBody());
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
                fn ($item) => !empty($item['assets'])
                    && version_compare($item['tag_name'], $this->prestaShopMinVersion, '>=')
            ));
        }

        return array_map(fn ($item) => new PrestaShop($item['tag_name']), $versions);
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
            $versions[] = new PrestaShop($prestaShopVersion);
        }

        return $versions;
    }

    public function setVersionsCompat(PrestaShop $prestaShop): void
    {
        $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
        if (!$content = file_get_contents($this->prestaShopDir . '/' . $prestaShop->getVersion() . '/install_version.php')) {
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
