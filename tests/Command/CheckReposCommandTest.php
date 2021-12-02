<?php

declare(strict_types=1);

namespace Tests\Command;

use App\Command\CheckReposCommand;
use App\Model\Version;
use App\Util\ModuleUtils;

class CheckReposCommandTest extends AbstractCommandTestCase
{
    private CheckReposCommand $command;
    private ModuleUtils $moduleUtils;

    public function setUp(): void
    {
        parent::setUp();
        $this->moduleUtils = $this->createMock(ModuleUtils::class);
        $this->moduleUtils->method('getNativeModuleList')->willReturn(['ps_mainmenu']);
        $this->command = new CheckReposCommand($this->moduleUtils);
    }

    public function testNoAssets(): void
    {
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

    public function testMissingAsset(): void
    {
        $this->moduleUtils->method('getVersions')
            ->willReturn(
                array_map(fn ($item) => new Version($item['version'], $item['url']), json_decode(
                    file_get_contents(__DIR__ . '/../ressources/stubs/ps_mainmenu-missing-asset.json'),
                    true
                ))
            );

        $this->output
            ->expects($this->exactly(2))
            ->method('writeln')
            ->withConsecutive(
                ['<info>Checking module ps_mainmenu</info>'],
                ['<error>No asset for release 1.1.0 of module ps_mainmenu</error>']
            );

        $this->command->execute($this->input, $this->output);
    }

    public function testModuleOK(): void
    {
        $this->moduleUtils->method('getVersions')
            ->willReturn(
                array_map(fn ($item) => new Version($item['version'], $item['url']), json_decode(
                    file_get_contents(__DIR__ . '/../ressources/stubs/ps_mainmenu-ok.json'),
                    true
                ))
            );

        $this->output
            ->expects($this->once())
            ->method('writeln')
            ->withConsecutive(
                ['<info>Checking module ps_mainmenu</info>'],
            );

        $this->command->execute($this->input, $this->output);
    }
}
