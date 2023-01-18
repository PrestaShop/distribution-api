<?php

declare(strict_types=1);

namespace Tests\Command;

use App\Command\CheckReposCommand;
use App\Model\PrestaShop;
use App\Model\Version;
use App\Util\ModuleUtils;
use App\Util\PrestaShopUtils;

class CheckReposCommandTest extends AbstractCommandTestCase
{
    private CheckReposCommand $command;
    private PrestaShopUtils $prestaShopUtils;
    private ModuleUtils $moduleUtils;

    public function setUp(): void
    {
        parent::setUp();
        $this->prestaShopUtils = $this->createMock(PrestaShopUtils::class);
        $this->moduleUtils = $this->createMock(ModuleUtils::class);
        $this->command = new CheckReposCommand($this->moduleUtils, $this->prestaShopUtils);
    }

    public function testNoModuleAssets(): void
    {
        $this->moduleUtils->method('getNativeModuleList')->willReturn(['ps_mainmenu']);
        $this->moduleUtils->method('getVersions')
            ->willReturn(
                array_map(fn ($item) => new Version($item['version'], $item['url']), json_decode(
                    file_get_contents(__DIR__ . '/../ressources/stubs/ps_mainmenu-no-asset.json'),
                    true
                ))
            );

        $this->output
            ->expects($this->exactly(2))
            ->method('writeln')
            ->withConsecutive(
                ['<info>Checking module ps_mainmenu</info>'],
                ['<error>No release for module ps_mainmenu</error>']
            );

        $this->command->execute($this->input, $this->output);
    }

    public function testMissingModuleAsset(): void
    {
        $this->moduleUtils->method('getNativeModuleList')->willReturn(['ps_mainmenu']);
        $this->moduleUtils->method('getVersions')
            ->willReturn(
                array_map(fn ($item) => new Version($item['version'], $item['url']), json_decode(
                    file_get_contents(__DIR__ . '/../ressources/stubs/ps_mainmenu-missing-asset.json'),
                    true
                ))
            );

        $this->output
            ->expects($this->exactly(4))
            ->method('writeln')
            ->withConsecutive(
                ['<info>Checking module ps_mainmenu</info>'],
                ['<comment>Assets found for release 1.0.0 of module ps_mainmenu</comment>'],
                ['<error>No asset for release 1.1.0 of module ps_mainmenu</error>'],
                ['<comment>Assets found for release 1.2.0 of module ps_mainmenu</comment>']
            );

        $this->command->execute($this->input, $this->output);
    }

    public function testModuleOK(): void
    {
        $this->moduleUtils->method('getNativeModuleList')->willReturn(['ps_mainmenu']);
        $this->moduleUtils->method('getVersions')
            ->willReturn(
                array_map(fn ($item) => new Version($item['version'], $item['url']), json_decode(
                    file_get_contents(__DIR__ . '/../ressources/stubs/ps_mainmenu-ok.json'),
                    true
                ))
            );

        $this->output
            ->expects($this->exactly(4))
            ->method('writeln')
            ->withConsecutive(
                ['<info>Checking module ps_mainmenu</info>'],
                ['<comment>Assets found for release 1.0.0 of module ps_mainmenu</comment>'],
                ['<comment>Assets found for release 1.1.0 of module ps_mainmenu</comment>'],
                ['<comment>Assets found for release 1.2.0 of module ps_mainmenu</comment>']
            );

        $this->command->execute($this->input, $this->output);
    }

    public function testMissingPrestaShopZipAndXml(): void
    {
        $this->prestaShopUtils->method('getVersions')->willReturn([new PrestaShop('8.0.0')]);

        $this->output
            ->expects($this->exactly(3))
            ->method('writeln')
            ->withConsecutive(
                ['<info>Checking PrestaShop 8.0.0</info>'],
                ['<error>No Zip asset</error>'],
                ['<error>No Xml asset</error>'],
            );

        $this->command->execute($this->input, $this->output);
    }

    public function testMissingPrestaShopZip(): void
    {
        $prestaShop = new PrestaShop('8.0.0');
        $prestaShop->setGithubXmlUrl('https://fake.url/prestashop.xml');
        $this->prestaShopUtils->method('getVersions')->willReturn([$prestaShop]);

        $this->output
            ->expects($this->exactly(2))
            ->method('writeln')
            ->withConsecutive(
                ['<info>Checking PrestaShop 8.0.0</info>'],
                ['<error>No Zip asset</error>'],
            );

        $this->command->execute($this->input, $this->output);
    }

    public function testMissingPrestaShopXml(): void
    {
        $prestaShop = new PrestaShop('8.0.0');
        $prestaShop->setGithubZipUrl('https://fake.url/prestashop.zip');
        $this->prestaShopUtils->method('getVersions')->willReturn([$prestaShop]);

        $this->output
            ->expects($this->exactly(2))
            ->method('writeln')
            ->withConsecutive(
                ['<info>Checking PrestaShop 8.0.0</info>'],
                ['<error>No Xml asset</error>'],
            );

        $this->command->execute($this->input, $this->output);
    }

    public function testPrestaShopOK(): void
    {
        $prestaShop = new PrestaShop('8.0.0');
        $prestaShop->setGithubZipUrl('https://fake.url/prestahop.zip');
        $prestaShop->setGithubXmlUrl('https://fake.url/prestashop.xml');
        $this->prestaShopUtils->method('getVersions')->willReturn([$prestaShop]);

        $this->output
            ->expects($this->exactly(2))
            ->method('writeln')
            ->withConsecutive(
                ['<info>Checking PrestaShop 8.0.0</info>'],
                ['<comment>All good!</comment>'],
            );

        $this->command->execute($this->input, $this->output);
    }
}
