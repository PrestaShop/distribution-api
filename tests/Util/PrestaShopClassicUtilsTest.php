<?php

declare(strict_types=1);

namespace Tests\Util;

use App\Model\PrestaShop;
use App\Util\PrestaShopClassicUtils;
use App\Util\PublicDownloadUrlProvider;
use App\Util\ReleaseNoteUtils;
use Google\Cloud\Storage\Bucket;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Tests\AbstractMockedGithubClientTestCase;

class PrestaShopClassicUtilsTest extends AbstractMockedGithubClientTestCase
{
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
            __DIR__ . '/../ressources/prestashop-classic'
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

    public function testReleaseNote(): void
    {
        $releaseNoteUtils = $this->createMock(ReleaseNoteUtils::class);
        $releaseNoteUtils->method('getReleaseNote')->willReturnMap([
            ['9.0.0', 'url1'],
            ['9.0.0-0.1', null],
            ['9.0.0-0.2', 'url2'],
        ]);
        $prestaShopClassicUtil = new PrestaShopClassicUtils(
            $this->createGithubClientMock(PrestaShop::DISTRIBUTION_CLASSIC),
            $this->createMock(HttpClientInterface::class),
            $this->createMock(Bucket::class),
            new PublicDownloadUrlProvider(''),
            $releaseNoteUtils,
            'prestashop/presta-shot',
            '9.0.0',
            __DIR__ . '/../ressources/prestashop-classic'
        );

        $classicReleases = $prestaShopClassicUtil->getLocalVersions();
        $firstReleaseOfPrestaShop9Classic = $classicReleases[0];
        $secondReleaseOfPrestaShop9Classic = $classicReleases[1];
        $this->assertEquals($firstReleaseOfPrestaShop9Classic->getCompleteVersion(), '9.0.0-0.2');
        // A specific release note exists for this classic release
        $this->assertEquals($firstReleaseOfPrestaShop9Classic->getReleaseNoteUrl(), 'url2');

        $this->assertEquals($secondReleaseOfPrestaShop9Classic->getCompleteVersion(), '9.0.0-3.0');
        // There is no dedicated release note. Fallback on the OS release notes.
        $this->assertEquals($secondReleaseOfPrestaShop9Classic->getReleaseNoteUrl(), 'url1');
    }

    public function classicProvider(): iterable
    {
        yield ['12.0.0-0.1', false, '1.6.1.24', '0.1'];
        yield ['9.2.0-1.6', false, '9.2.0', '1.5'];
        yield ['9.1.0-1.5', true, '9.2.0', '1.5'];
        yield ['9.0.0-0.1', true, '9.0.0', '0.1'];
    }
}
