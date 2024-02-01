<?php

declare(strict_types=1);

namespace Tests\Command;

use App\Command\GenerateJsonCommand;
use App\Model\PrestaShop;
use App\Util\ModuleUtils;
use App\Util\PrestaShopUtils;
use App\Util\PublicDownloadUrlProvider;
use Google\Cloud\Storage\Bucket;
use GuzzleHttp\Client;
use Psssst\ModuleParser;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class GenerateJsonCommandTest extends AbstractCommandTestCase
{
    private const MIN_PRESTASHOP_VERSION = '8.0.0';

    private GenerateJsonCommand $command;

    public function setUp(): void
    {
        parent::setUp();
        (new Filesystem())->remove((new Finder())->in(__DIR__ . '/../output'));

        $githubClient = $this->createGithubClientMock();
        $bucket = $this->createMock(Bucket::class);
        $urlProvider = new PublicDownloadUrlProvider('');

        $moduleUtils = new ModuleUtils(
            new ModuleParser(),
            $this->createMock(Client::class),
            $githubClient,
            $bucket,
            $urlProvider,
            'prestashop/native-modules',
            self::MIN_PRESTASHOP_VERSION,
            __DIR__ . '/../ressources/modules',
        );
        $prestaShopUtils = new PrestaShopUtils(
            $githubClient,
            $this->createMock(Client::class),
            $bucket,
            $urlProvider,
            self::MIN_PRESTASHOP_VERSION,
            __DIR__ . '/../ressources/prestashop',
        );

        $this->command = new GenerateJsonCommand(
            $moduleUtils,
            $prestaShopUtils,
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
                new Prestashop('8.1.4'),
                new Prestashop('8.1.3'),
                new Prestashop('9.0.0'),
                new Prestashop('9.0.3'),
                new Prestashop('1.7.8.10'),
            ], [
                new Prestashop('8.1.4'),
                new Prestashop('8.1.3'),
                new Prestashop('9.0.0'),
                new Prestashop('9.0.3'),
                new Prestashop('1.7.8.10'),
                new Prestashop('10.0.0'),
                new Prestashop('9.1.0'),
                new Prestashop('9.0.4'),
                new Prestashop('8.2.0'),
                new Prestashop('8.1.5'),
        ]];
        // Scenario to avoid adding 1.7 versions as a previous major
        yield [[
            new Prestashop('8.1.4'),
            new Prestashop('8.1.3'),
            new Prestashop('1.7.8.10'),
        ], [
            new Prestashop('8.1.4'),
            new Prestashop('8.1.3'),
            new Prestashop('1.7.8.10'),
            new Prestashop('9.0.0'),
            new Prestashop('8.2.0'),
            new Prestashop('8.1.5'),
        ]];
        // Scenario to avoid considering beta as a stable channel
        yield [[
            new Prestashop('8.1.4'),
            new Prestashop('8.1.3'),
            new Prestashop('9.0.0-beta'),
            new Prestashop('1.7.8.10'),
        ], [
            new Prestashop('8.1.4'),
            new Prestashop('8.1.3'),
            new Prestashop('9.0.0-beta'),
            new Prestashop('1.7.8.10'),
            new Prestashop('9.0.0'),
            new Prestashop('8.2.0'),
            new Prestashop('8.1.5'),
        ]];
    }
}
