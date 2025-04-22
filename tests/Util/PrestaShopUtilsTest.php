<?php

declare(strict_types=1);

namespace Tests\Util;

use App\Model\PrestaShop;
use App\Util\PrestaShopClassicUtils;
use App\Util\PrestaShopOpenSourceUtils;
use App\Util\PublicDownloadUrlProvider;
use App\Util\ReleaseNoteUtils;
use Google\Cloud\Storage\Bucket;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Tests\AbstractMockedGithubClientTestCase;

class PrestaShopUtilsTest extends AbstractMockedGithubClientTestCase
{
    /**
     * @dataProvider osProvider
     */
    public function testOpenSourceVersions(string $minPrestaShopVersion, bool $contains, string $prestaShopVersion): void
    {
        $versions = [];

        $prestaShopOsUtil = new PrestaShopOpenSourceUtils(
            $this->createGithubClientMock(PrestaShop::DISTRIBUTION_OPEN_SOURCE),
            $this->createMock(HttpClientInterface::class),
            $this->createMock(Bucket::class),
            new PublicDownloadUrlProvider(''),
            new ReleaseNoteUtils(),
            'prestashop/presta-shot',
            $minPrestaShopVersion,
            __DIR__ . '/../ressources/prestashop'
        );

        foreach ($prestaShopOsUtil->getVersions() as $version) {
            $versions[] = $version->getVersion();
            $this->assertNull($version->getDistributionVersion());
        }

        if ($contains) {
            $this->assertContains($prestaShopVersion, $versions);
        } else {
            $this->assertNotContains($prestaShopVersion, $versions);
        }
    }

    /**
     * @dataProvider classicProvider
     */
    public function testClassicVersions(string $minPrestaShopVersion, bool $contains, string $prestaShopVersion, string $distributionVersion): void
    {
        $versions = [];
        $distributionVersions = [];

        $prestaShopClassicUtil = new PrestaShopClassicUtils(
            $this->createGithubClientMock(PrestaShop::DISTRIBUTION_CLASSIC),
            $this->createMock(HttpClientInterface::class),
            $this->createMock(Bucket::class),
            new PublicDownloadUrlProvider(''),
            new ReleaseNoteUtils(),
            'prestashop/presta-shot',
            $minPrestaShopVersion,
            __DIR__ . '/../ressources/prestashop'
        );

        foreach ($prestaShopClassicUtil->getVersions() as $version) {
            $versions[] = $version->getVersion();
            $distributionVersions[] = $version->getDistributionVersion();
        }

        if ($contains) {
            $this->assertContains($prestaShopVersion, $versions);
            $this->assertContains($distributionVersion, $distributionVersions);
        } else {
            $this->assertNotContains($prestaShopVersion, $versions);
            $this->assertNotContains($distributionVersion, $distributionVersions);
        }
    }

    public function osProvider(): iterable
    {
        yield ['12.0.0', false, '1.6.1.24'];
        yield ['1.7.8.7', true, '1.7.8.7'];
        yield ['1.7.8.7', false, '12.0.0'];
        yield ['1.6.1.24', true, '1.7.8.7'];
    }

    public function classicProvider(): iterable
    {
        yield ['12.0.0-0.1', false, '1.6.1.24', '0.1'];
        yield ['9.2.0-1.6', false, '9.2.0', '1.5'];
        yield ['9.1.0-1.5', true, '9.2.0', '1.5'];
        yield ['9.0.0-0.1', true, '9.0.0', '0.1'];
    }
}
