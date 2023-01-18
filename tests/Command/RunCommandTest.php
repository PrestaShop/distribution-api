<?php

declare(strict_types=1);

namespace Tests\Command;

use App\Command\CleanCommand;
use App\Command\DownloadNativeModuleFilesCommand;
use App\Command\DownloadNewPrestaShopReleasesCommand;
use App\Command\GenerateJsonCommand;
use App\Command\RunCommand;
use App\Command\UpdateModuleConfigFilesCommand;
use App\Command\UploadAssetsCommand;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Command\Command;

class RunCommandTest extends AbstractCommandTestCase
{
    private RunCommand $command;

    /** @var CleanCommand&MockObject */
    private $cleanCommandMock;

    /** @var DownloadNativeModuleFilesCommand&MockObject */
    private $downloadNativeModuleFilesCommandMock;

    /** @var DownloadNewPrestaShopReleasesCommand&MockObject */
    private $downloadNewPrestaShopReleasesCommandMock;

    /** @var UpdateModuleConfigFilesCommand&MockObject */
    private $updateModuleConfigFilesCommandMock;

    /** @var GenerateJsonCommand&MockObject */
    private $generateJsonCommandMock;

    /** @var UploadAssetsCommand&MockObject */
    private $uploadAssetsCommandMock;

    public function setUp(): void
    {
        parent::setUp();

        $this->cleanCommandMock = $this->createMock(CleanCommand::class);
        $this->downloadNativeModuleFilesCommandMock = $this->createMock(DownloadNativeModuleFilesCommand::class);
        $this->downloadNewPrestaShopReleasesCommandMock = $this->createMock(DownloadNewPrestaShopReleasesCommand::class);
        $this->updateModuleConfigFilesCommandMock = $this->createMock(UpdateModuleConfigFilesCommand::class);
        $this->generateJsonCommandMock = $this->createMock(GenerateJsonCommand::class);
        $this->uploadAssetsCommandMock = $this->createMock(UploadAssetsCommand::class);

        $this->command = new RunCommand(
            $this->cleanCommandMock,
            $this->downloadNativeModuleFilesCommandMock,
            $this->downloadNewPrestaShopReleasesCommandMock,
            $this->updateModuleConfigFilesCommandMock,
            $this->generateJsonCommandMock,
            $this->uploadAssetsCommandMock
        );
    }

    public function testSuccess(): void
    {
        $this->cleanCommandMock->method('execute')->willReturn(Command::SUCCESS);
        $this->downloadNativeModuleFilesCommandMock->method('execute')->willReturn(Command::SUCCESS);
        $this->downloadNewPrestaShopReleasesCommandMock->method('execute')->willReturn(Command::SUCCESS);
        $this->updateModuleConfigFilesCommandMock->method('execute')->willReturn(Command::SUCCESS);
        $this->generateJsonCommandMock->method('execute')->willReturn(Command::SUCCESS);
        $this->uploadAssetsCommandMock->method('execute')->willReturn(Command::SUCCESS);

        $this->cleanCommandMock->expects($this->once())->method('execute');
        $this->downloadNativeModuleFilesCommandMock->expects($this->once())->method('execute');
        $this->downloadNewPrestaShopReleasesCommandMock->expects($this->once())->method('execute');
        $this->updateModuleConfigFilesCommandMock->expects($this->once())->method('execute');
        $this->generateJsonCommandMock->expects($this->once())->method('execute');
        $this->uploadAssetsCommandMock->expects($this->once())->method('execute');
        $this->assertSame(Command::SUCCESS, $this->command->execute($this->input, $this->output));
    }

    public function testCleanCommandFail(): void
    {
        $this->cleanCommandMock->method('execute')->willReturn(Command::FAILURE);
        $this->downloadNativeModuleFilesCommandMock->method('execute')->willReturn(Command::SUCCESS);
        $this->downloadNewPrestaShopReleasesCommandMock->method('execute')->willReturn(Command::SUCCESS);
        $this->updateModuleConfigFilesCommandMock->method('execute')->willReturn(Command::SUCCESS);
        $this->generateJsonCommandMock->method('execute')->willReturn(Command::SUCCESS);
        $this->uploadAssetsCommandMock->method('execute')->willReturn(Command::SUCCESS);

        $this->cleanCommandMock->expects($this->once())->method('execute');
        $this->downloadNativeModuleFilesCommandMock->expects($this->never())->method('execute');
        $this->downloadNewPrestaShopReleasesCommandMock->expects($this->never())->method('execute');
        $this->updateModuleConfigFilesCommandMock->expects($this->never())->method('execute');
        $this->generateJsonCommandMock->expects($this->never())->method('execute');
        $this->uploadAssetsCommandMock->expects($this->never())->method('execute');
        $this->assertSame(Command::FAILURE, $this->command->execute($this->input, $this->output));
    }

    public function testDownloadNativeModuleFilesCommandFail(): void
    {
        $this->cleanCommandMock->method('execute')->willReturn(Command::SUCCESS);
        $this->downloadNativeModuleFilesCommandMock->method('execute')->willReturn(Command::FAILURE);
        $this->downloadNewPrestaShopReleasesCommandMock->method('execute')->willReturn(Command::SUCCESS);
        $this->updateModuleConfigFilesCommandMock->method('execute')->willReturn(Command::SUCCESS);
        $this->generateJsonCommandMock->method('execute')->willReturn(Command::SUCCESS);
        $this->uploadAssetsCommandMock->method('execute')->willReturn(Command::SUCCESS);

        $this->cleanCommandMock->expects($this->once())->method('execute');
        $this->downloadNativeModuleFilesCommandMock->expects($this->once())->method('execute');
        $this->downloadNewPrestaShopReleasesCommandMock->expects($this->never())->method('execute');
        $this->updateModuleConfigFilesCommandMock->expects($this->never())->method('execute');
        $this->generateJsonCommandMock->expects($this->never())->method('execute');
        $this->uploadAssetsCommandMock->expects($this->never())->method('execute');
        $this->assertSame(Command::FAILURE, $this->command->execute($this->input, $this->output));
    }

    public function testDownloadNewPrestaShopReleasesCommandFail(): void
    {
        $this->cleanCommandMock->method('execute')->willReturn(Command::SUCCESS);
        $this->downloadNativeModuleFilesCommandMock->method('execute')->willReturn(Command::SUCCESS);
        $this->downloadNewPrestaShopReleasesCommandMock->method('execute')->willReturn(Command::FAILURE);
        $this->updateModuleConfigFilesCommandMock->method('execute')->willReturn(Command::SUCCESS);
        $this->generateJsonCommandMock->method('execute')->willReturn(Command::SUCCESS);
        $this->uploadAssetsCommandMock->method('execute')->willReturn(Command::SUCCESS);

        $this->cleanCommandMock->expects($this->once())->method('execute');
        $this->downloadNativeModuleFilesCommandMock->expects($this->once())->method('execute');
        $this->downloadNewPrestaShopReleasesCommandMock->expects($this->once())->method('execute');
        $this->updateModuleConfigFilesCommandMock->expects($this->never())->method('execute');
        $this->generateJsonCommandMock->expects($this->never())->method('execute');
        $this->uploadAssetsCommandMock->expects($this->never())->method('execute');
        $this->assertSame(Command::FAILURE, $this->command->execute($this->input, $this->output));
    }

    public function testUpdateModuleConfigFilesCommandFail(): void
    {
        $this->cleanCommandMock->method('execute')->willReturn(Command::SUCCESS);
        $this->downloadNativeModuleFilesCommandMock->method('execute')->willReturn(Command::SUCCESS);
        $this->downloadNewPrestaShopReleasesCommandMock->method('execute')->willReturn(Command::SUCCESS);
        $this->updateModuleConfigFilesCommandMock->method('execute')->willReturn(Command::FAILURE);
        $this->generateJsonCommandMock->method('execute')->willReturn(Command::SUCCESS);
        $this->uploadAssetsCommandMock->method('execute')->willReturn(Command::SUCCESS);

        $this->cleanCommandMock->expects($this->once())->method('execute');
        $this->downloadNativeModuleFilesCommandMock->expects($this->once())->method('execute');
        $this->downloadNewPrestaShopReleasesCommandMock->expects($this->once())->method('execute');
        $this->updateModuleConfigFilesCommandMock->expects($this->once())->method('execute');
        $this->generateJsonCommandMock->expects($this->never())->method('execute');
        $this->uploadAssetsCommandMock->expects($this->never())->method('execute');
        $this->assertSame(Command::FAILURE, $this->command->execute($this->input, $this->output));
    }

    public function testGenerateJsonCommandFail(): void
    {
        $this->cleanCommandMock->method('execute')->willReturn(Command::SUCCESS);
        $this->downloadNativeModuleFilesCommandMock->method('execute')->willReturn(Command::SUCCESS);
        $this->downloadNewPrestaShopReleasesCommandMock->method('execute')->willReturn(Command::SUCCESS);
        $this->updateModuleConfigFilesCommandMock->method('execute')->willReturn(Command::SUCCESS);
        $this->generateJsonCommandMock->method('execute')->willReturn(Command::FAILURE);
        $this->uploadAssetsCommandMock->method('execute')->willReturn(Command::SUCCESS);

        $this->cleanCommandMock->expects($this->once())->method('execute');
        $this->downloadNativeModuleFilesCommandMock->expects($this->once())->method('execute');
        $this->downloadNewPrestaShopReleasesCommandMock->expects($this->once())->method('execute');
        $this->updateModuleConfigFilesCommandMock->expects($this->once())->method('execute');
        $this->generateJsonCommandMock->expects($this->once())->method('execute');
        $this->uploadAssetsCommandMock->expects($this->never())->method('execute');
        $this->assertSame(Command::FAILURE, $this->command->execute($this->input, $this->output));
    }

    public function testUploadAssetsCommandFail(): void
    {
        $this->cleanCommandMock->method('execute')->willReturn(Command::SUCCESS);
        $this->downloadNativeModuleFilesCommandMock->method('execute')->willReturn(Command::SUCCESS);
        $this->downloadNewPrestaShopReleasesCommandMock->method('execute')->willReturn(Command::SUCCESS);
        $this->updateModuleConfigFilesCommandMock->method('execute')->willReturn(Command::SUCCESS);
        $this->generateJsonCommandMock->method('execute')->willReturn(Command::SUCCESS);
        $this->uploadAssetsCommandMock->method('execute')->willReturn(Command::FAILURE);

        $this->cleanCommandMock->expects($this->once())->method('execute');
        $this->downloadNativeModuleFilesCommandMock->expects($this->once())->method('execute');
        $this->downloadNewPrestaShopReleasesCommandMock->expects($this->once())->method('execute');
        $this->updateModuleConfigFilesCommandMock->expects($this->once())->method('execute');
        $this->generateJsonCommandMock->expects($this->once())->method('execute');
        $this->uploadAssetsCommandMock->expects($this->once())->method('execute');
        $this->assertSame(Command::FAILURE, $this->command->execute($this->input, $this->output));
    }
}
