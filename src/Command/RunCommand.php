<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RunCommand extends Command
{
    protected static $defaultName = 'run';

    private CleanCommand $cleanCommand;
    private DownloadNativeModuleFilesCommand $downloadNativeModuleFilesCommand;
    private DownloadNewPrestaShopReleasesCommand $downloadNewPrestaShopReleasesCommand;
    private UpdateModuleConfigFilesCommand $updateModuleConfigFilesCommand;
    private GenerateJsonCommand $generateJsonCommand;
    private UploadAssetsCommand $uploadAssetsCommand;

    public function __construct(
        CleanCommand $cleanCommand,
        DownloadNativeModuleFilesCommand $downloadNativeModuleFilesCommand,
        DownloadNewPrestaShopReleasesCommand $downloadPrestaShopInstallVersionsCommand,
        UpdateModuleConfigFilesCommand $updateModuleConfigFilesCommand,
        GenerateJsonCommand $generateJsonCommand,
        UploadAssetsCommand $uploadAssetsCommand,
    ) {
        parent::__construct();
        $this->cleanCommand = $cleanCommand;
        $this->downloadNativeModuleFilesCommand = $downloadNativeModuleFilesCommand;
        $this->downloadNewPrestaShopReleasesCommand = $downloadPrestaShopInstallVersionsCommand;
        $this->updateModuleConfigFilesCommand = $updateModuleConfigFilesCommand;
        $this->generateJsonCommand = $generateJsonCommand;
        $this->uploadAssetsCommand = $uploadAssetsCommand;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $cleanCommandInput = new ArrayInput(['directory' => 'all'], $this->cleanCommand->getDefinition());
        if (
            $this->cleanCommand->execute($cleanCommandInput, $output) === self::SUCCESS
            && $this->downloadNativeModuleFilesCommand->execute($input, $output) === self::SUCCESS
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
