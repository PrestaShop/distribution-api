<?php

declare(strict_types=1);

namespace Tests\Command;

use App\Command\GenerateJsonCommand;
use App\Model\Module;
use App\Model\PrestaShop;
use App\Model\Version;
use App\Util\ModuleUtils;
use App\Util\PrestaShopUtils;
use Github\Client as GithubClient;
use GuzzleHttp\Client;
use Psssst\ModuleParser;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class GenerateJsonCommandTest extends AbstractCommandTestCase
{
    private GenerateJsonCommand $command;

    public function setUp(): void
    {
        parent::setUp();
        (new Filesystem())->remove((new Finder())->in(__DIR__ . '/../output'));
        $githubClient = $this->createMock(GithubClient::class);
        $moduleUtils = $this->getMockBuilder(ModuleUtils::class)
            ->setConstructorArgs([
                new ModuleParser(),
                $this->createMock(Client::class),
                $githubClient,
                __DIR__ . '/../ressources/modules',
            ])
            ->onlyMethods(['getLocalModules'])
            ->getMock()
        ;
        $moduleUtils->method('getLocalModules')->willReturn([
            new Module('autoupgrade', [
                new Version('v4.10.1'),
                new Version('v4.11.0'),
                new Version('v4.12.0'),
            ]),
            new Module('psgdpr', [
                new Version('v1.2.0'),
                new Version('v1.2.1'),
                new Version('v1.3.0'),
            ]),
        ]);
        $prestaShopUtils = $this->getMockBuilder(PrestaShopUtils::class)
            ->setConstructorArgs([
                $githubClient,
                $this->createMock(Client::class),
                __DIR__ . '/../ressources/prestashop',
            ])
            ->onlyMethods(['getLocalVersions'])
            ->getMock()
        ;
        $prestaShopUtils->method('getLocalVersions')->willReturn([
            new PrestaShop('1.6.1.4'),
            new PrestaShop('1.6.1.24'),
            new PrestaShop('1.7.0.0'),
            new PrestaShop('1.7.7.8'),
            new PrestaShop('1.7.8.1'),
            new PrestaShop('1.7.8.0-rc.1'),
            new PrestaShop('1.7.8.0-beta.1'),
        ]);

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
            $baseExpected . '/1.6.1.4/modules.json',
            $baseOutput . '/1.6.1.4/modules.json'
        );
        $this->assertJsonFileEqualsJsonFile(
            $baseExpected . '/1.6.1.4/modules.json',
            $baseOutput . '/1.6.1.24/modules.json'
        );
        $this->assertJsonFileEqualsJsonFile(
            $baseExpected . '/1.7.0.0/modules.json',
            $baseOutput . '/1.7.0.0/modules.json'
        );
        $this->assertJsonFileEqualsJsonFile(
            $baseExpected . '/1.7.7.8/modules.json',
            $baseOutput . '/1.7.7.8/modules.json'
        );
        $this->assertJsonFileEqualsJsonFile(
            $baseExpected . '/1.7.8.1/modules.json',
            $baseOutput . '/1.7.8.1/modules.json'
        );
        $this->assertJsonFileEqualsJsonFile(
            $baseExpected . '/1.7.8.0-rc.1/modules.json',
            $baseOutput . '/1.7.8.0-rc.1/modules.json'
        );
        $this->assertJsonFileEqualsJsonFile(
            $baseExpected . '/1.7.8.0-beta.1/modules.json',
            $baseOutput . '/1.7.8.0-beta.1/modules.json'
        );

        $this->assertJsonFileEqualsJsonFile(
            $baseExpected . '/prestashop.json',
            $baseOutput . '/prestashop.json'
        );
        $this->assertJsonFileEqualsJsonFile(
            $baseExpected . '/stable/prestashop.json',
            $baseOutput . '/stable/prestashop.json'
        );
        $this->assertJsonFileEqualsJsonFile(
            $baseExpected . '/rc/prestashop.json',
            $baseOutput . '/rc/prestashop.json'
        );
        $this->assertJsonFileEqualsJsonFile(
            $baseExpected . '/beta/prestashop.json',
            $baseOutput . '/beta/prestashop.json'
        );
    }
}
