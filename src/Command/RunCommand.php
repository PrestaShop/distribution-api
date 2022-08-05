<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RunCommand extends Command
{
    protected static $defaultName = 'run';

    private DownloadNativeModuleMainClassesCommand $downloadNativeModuleMainClassesCommand;
    private DownloadNewPrestaShopReleasesCommand $downloadNewPrestaShopReleasesCommand;
    private UpdateModuleConfigFilesCommand $updateModuleConfigFilesCommand;
    private GenerateJsonCommand $generateJsonCommand;
    private UploadAssetsCommand $uploadAssetsCommand;

    public function __construct(
        DownloadNativeModuleMainClassesCommand $downloadNativeModuleMainClassesCommand,
        DownloadNewPrestaShopReleasesCommand $downloadPrestaShopInstallVersionsCommand,
        UpdateModuleConfigFilesCommand $updateModuleConfigFilesCommand,
        GenerateJsonCommand $generateJsonCommand,
        UploadAssetsCommand $uploadAssetsCommand
    ) {
        parent::__construct();
        $this->downloadNativeModuleMainClassesCommand = $downloadNativeModuleMainClassesCommand;
        $this->downloadNewPrestaShopReleasesCommand = $downloadPrestaShopInstallVersionsCommand;
        $this->updateModuleConfigFilesCommand = $updateModuleConfigFilesCommand;
        $this->generateJsonCommand = $generateJsonCommand;
        $this->uploadAssetsCommand = $uploadAssetsCommand;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        if (
            $this->downloadNativeModuleMainClassesCommand->execute($input, $output) === self::SUCCESS
            && $this->downloadNewPrestaShopReleasesCommand->execute($input, $output) === self::SUCCESS
            && $this->updateModuleConfigFilesCommand->execute($input, $output) === self::SUCCESS
            && $this->generateJsonCommand->execute($input, $output) === self::SUCCESS
            && $this->uploadAssetsCommand->execute($input, $output) === self::SUCCESS
        ) {
            return self::SUCCESS;
        }

        return self::FAILURE;
    }
}
