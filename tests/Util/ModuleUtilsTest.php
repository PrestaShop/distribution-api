<?php

declare(strict_types=1);

namespace Tests\Util;

use App\Model\PrestaShop;
use App\Model\Version;
use App\Util\ModuleUtils;
use App\Util\PublicDownloadUrlProvider;
use Google\Cloud\Storage\Bucket;
use Psssst\ModuleParser;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Tests\AbstractMockedGithubClientTestCase;

class ModuleUtilsTest extends AbstractMockedGithubClientTestCase
{
    /**
     * @dataProvider provider
     */
    public function testIsModuleCompatibleWithMinPrestaShopVersion(
        string $minPrestaShopVersion,
        string $moduleName,
        string $moduleVersion,
        bool $expected,
    ): void {
        $moduleUtils = new ModuleUtils(
            new ModuleParser(),
            $this->createMock(HttpClientInterface::class),
            $this->createGithubClientMock(PrestaShop::DISTRIBUTION_OPEN_SOURCE),
            $this->createMock(Bucket::class),
            new PublicDownloadUrlProvider(''),
            'PrestaShop/native-modules',
            $minPrestaShopVersion,
            __DIR__ . '/../ressources/modules'
        );

        $this->assertSame(
            $expected,
            $moduleUtils->isModuleCompatibleWithMinPrestaShopVersion($moduleName, new Version($moduleVersion))
        );
    }

    public function provider(): iterable
    {
        yield ['8.0.0', 'psgdpr', 'v1.3.0', true];
        yield ['1.7.8.6', 'psgdpr', 'v1.3.0', true];
        yield ['1.6.1.24', 'psgdpr', 'v1.3.0', false];
    }
}
