<?php

declare(strict_types=1);

namespace Tests\Util;

use App\Util\PrestaShopUtils;
use App\Util\PublicDownloadUrlProvider;
use Google\Cloud\Storage\Bucket;
use GuzzleHttp\Client;
use Tests\AbstractMockedGithubClientTestCase;

class PrestaShopUtilsTest extends AbstractMockedGithubClientTestCase
{
    /**
     * @dataProvider provider
     */
    public function testGetVersions(string $minPrestaShopVersion, bool $contains, string $prestaShopVersion): void
    {
        $prestaShopUtil = new PrestaShopUtils(
            $this->createGithubClientMock(),
            $this->createMock(Client::class),
            $this->createMock(Bucket::class),
            new PublicDownloadUrlProvider(''),
            $minPrestaShopVersion,
            __DIR__ . '/../ressources/prestashop'
        );
        $versions = [];

        foreach ($prestaShopUtil->getVersions() as $version) {
            $versions[] = $version->getVersion();
        }

        if ($contains) {
            $this->assertContains($prestaShopVersion, $versions);
        } else {
            $this->assertNotContains($prestaShopVersion, $versions);
        }
    }

    public function provider(): iterable
    {
        yield ['12.0.0', false, '1.6.1.24'];
        yield ['1.7.8.7', true, '1.7.8.7'];
        yield ['1.7.8.7', false, '12.0.0'];
        yield ['1.6.1.24', true, '1.7.8.7'];
    }
}
