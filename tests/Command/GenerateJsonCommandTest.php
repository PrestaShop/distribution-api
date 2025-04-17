<?php

declare(strict_types=1);

namespace Tests\Command;

use App\Command\GenerateJsonCommand;
use App\Model\PrestaShop;
use App\Util\ModuleUtils;
use App\Util\PrestaShopClassicUtils;
use App\Util\PrestaShopOpenSourceUtils;
use App\Util\PublicDownloadUrlProvider;
use App\Util\ReleaseNoteUtils;
use Google\Cloud\Storage\Bucket;
use Psssst\ModuleParser;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GenerateJsonCommandTest extends AbstractCommandTestCase
{
    private const MIN_PRESTASHOP_VERSION = '1.7.0.0';

    private GenerateJsonCommand $command;

    public function setUp(): void
    {
        parent::setUp();
        (new Filesystem())->remove((new Finder())->in(__DIR__ . '/../output'));

        $githubOsClient = $this->createGithubClientMock(PrestaShop::DISTRIBUTION_OPEN_SOURCE);
        $githubClassicClient = $this->createGithubClientMock(PrestaShop::DISTRIBUTION_CLASSIC);
        $bucket = $this->createMock(Bucket::class);
        $urlProvider = new PublicDownloadUrlProvider('');
        $releaseNoteUtils = new ReleaseNoteUtils();

        $moduleUtils = new ModuleUtils(
            new ModuleParser(),
            $this->createMock(HttpClientInterface::class),
            $githubOsClient,
            $bucket,
            $urlProvider,
            'prestashop/native-modules',
            self::MIN_PRESTASHOP_VERSION,
            __DIR__ . '/../ressources/modules',
        );

        $prestaShopOpenSourceUtils = new PrestaShopOpenSourceUtils(
            $githubOsClient,
            $this->createMock(HttpClientInterface::class),
            $bucket,
            $urlProvider,
            $releaseNoteUtils,
            'Prestashot/Prestashot',
            self::MIN_PRESTASHOP_VERSION,
            __DIR__ . '/../ressources/prestashop',
        );

        $prestaShopClassicUtils = new PrestaShopClassicUtils(
            $githubClassicClient,
            $this->createMock(HttpClientInterface::class),
            $bucket,
            $urlProvider,
            $releaseNoteUtils,
            'Classic/OsIsBetter',
            self::MIN_PRESTASHOP_VERSION,
            __DIR__ . '/../ressources/prestashop-classic',
        );

        $this->command = new GenerateJsonCommand(
            $moduleUtils,
            $prestaShopOpenSourceUtils,
            $prestaShopClassicUtils,
            __DIR__ . '/../output'
        );
    }

    public function testGenerateJson()
    {
        $this->command->execute($this->input, $this->output);
        $baseOutput = __DIR__ . '/../output';
        $baseExpected = __DIR__ . '/../ressources/json';

        $this->assertJsonFileEqualsJsonFile(
            $baseExpected . '/modules/1.6.1.4.json',
            $baseOutput . '/modules/1.6.1.4.json'
        );
        $this->assertJsonFileEqualsJsonFile(
            $baseExpected . '/modules/1.6.1.24.json',
            $baseOutput . '/modules/1.6.1.24.json'
        );
        $this->assertJsonFileEqualsJsonFile(
            $baseExpected . '/modules/1.7.0.0.json',
            $baseOutput . '/modules/1.7.0.0.json'
        );
        $this->assertJsonFileEqualsJsonFile(
            $baseExpected . '/modules/1.7.7.8.json',
            $baseOutput . '/modules/1.7.7.8.json'
        );
        $this->assertJsonFileEqualsJsonFile(
            $baseExpected . '/modules/1.7.8.1.json',
            $baseOutput . '/modules/1.7.8.1.json'
        );
        $this->assertJsonFileEqualsJsonFile(
            $baseExpected . '/modules/1.7.8.0-rc.1.json',
            $baseOutput . '/modules/1.7.8.0-rc.1.json'
        );
        $this->assertJsonFileEqualsJsonFile(
            $baseExpected . '/modules/1.7.8.0-beta.1.json',
            $baseOutput . '/modules/1.7.8.0-beta.1.json'
        );

        $this->assertJsonFileEqualsJsonFile(
            $baseExpected . '/prestashop.json',
            $baseOutput . '/prestashop.json'
        );
        $this->assertJsonFileEqualsJsonFile(
            $baseExpected . '/prestashop/stable.json',
            $baseOutput . '/prestashop/stable.json'
        );
        $this->assertJsonFileEqualsJsonFile(
            $baseExpected . '/prestashop/rc.json',
            $baseOutput . '/prestashop/rc.json'
        );
        $this->assertJsonFileEqualsJsonFile(
            $baseExpected . '/prestashop/beta.json',
            $baseOutput . '/prestashop/beta.json'
        );
    }

    /**
     * @dataProvider versionListProvider
     */
    public function testAddVersionsUnderDelopment(array $before, array $afterExpected)
    {
        $this->assertEquals($afterExpected, $this->command->addVersionsUnderDelopment($before));
    }

    public function versionListProvider(): iterable
    {
        // Pretty normal scenario
        yield [[
            new PrestaShop('8.1.4'),
            new PrestaShop('8.1.3'),
            new PrestaShop('9.0.0'),
            new PrestaShop('9.0.3'),
            new PrestaShop('1.7.8.10'),
        ], [
            new PrestaShop('8.1.4'),
            new PrestaShop('8.1.3'),
            new PrestaShop('9.0.0'),
            new PrestaShop('9.0.3'),
            new PrestaShop('1.7.8.10'),
            new PrestaShop('10.0.0'),
            new PrestaShop('9.1.0'),
            new PrestaShop('9.0.4'),
            new PrestaShop('8.2.0'),
            new PrestaShop('8.1.5'),
        ]];
        // Scenario to avoid adding 1.7 versions as a previous major
        yield [[
            new PrestaShop('8.1.4'),
            new PrestaShop('8.1.3'),
            new PrestaShop('1.7.8.10'),
        ], [
            new PrestaShop('8.1.4'),
            new PrestaShop('8.1.3'),
            new PrestaShop('1.7.8.10'),
            new PrestaShop('9.0.0'),
            new PrestaShop('8.2.0'),
            new PrestaShop('8.1.5'),
        ]];
        // Scenario to avoid considering beta as a stable channel
        yield [[
            new PrestaShop('8.1.4'),
            new PrestaShop('8.1.3'),
            new PrestaShop('9.0.0-beta'),
            new PrestaShop('1.7.8.10'),
        ], [
            new PrestaShop('8.1.4'),
            new PrestaShop('8.1.3'),
            new PrestaShop('9.0.0-beta'),
            new PrestaShop('1.7.8.10'),
            new PrestaShop('9.0.0'),
            new PrestaShop('8.2.0'),
            new PrestaShop('8.1.5'),
        ]];
    }
}
