<?php

declare(strict_types=1);

namespace App\Command;

use Google\Cloud\Storage\Bucket;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class UploadAssetsCommand extends Command
{
    protected static $defaultName = 'uploadAssets';

    private const PRESTASHOP_ASSETS_PREFIX = 'assets/prestashop/';
    private const MODULE_ASSETS_PREFIX = 'assets/modules/';

    private Bucket $bucket;

    private string $jsonDir;
    private string $prestaShopDir;
    private string $moduleDir;

    public function __construct(Bucket $bucket, string $jsonDir, string $prestaShopDir, string $moduleDir)
    {
        parent::__construct();
        $this->bucket = $bucket;
        $this->jsonDir = $jsonDir;
        $this->prestaShopDir = $prestaShopDir;
        $this->moduleDir = $moduleDir;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->uploadPrestaShop($output);
        $this->uploadModules($output);
        $this->uploadJson($output);

        return self::SUCCESS;
    }

    private function uploadPrestaShop(OutputInterface $output): void
    {
        if (!file_exists($this->prestaShopDir)) {
            return;
        }

        $finder = new Finder();
        $finder->sortByName();
        $prestashopZips = $finder->in($this->prestaShopDir)->files()->name(['prestashop.zip', 'prestashop.xml']);

        $output->writeln(sprintf('<info>%s new PrestaShop xml/archive(s) to upload.</info>', $prestashopZips->count()));
        if ($prestashopZips->count() === 0) {
            $output->writeln(sprintf(
                '<question>Did you run the `%s` command?</question>',
                DownloadNewPrestaShopReleasesCommand::getDefaultName()
            ));
        }

        foreach ($prestashopZips as $prestashopZip) {
            $filename = self::PRESTASHOP_ASSETS_PREFIX . substr($prestashopZip->getPathname(), strlen($this->prestaShopDir) + 1);
            $output->writeln(sprintf('<info>Upload file %s</info>', $filename));
            $this->bucket->upload($prestashopZip->getContents(), ['name' => $filename]);
        }
    }

    private function uploadModules(OutputInterface $output): void
    {
        $finder = new Finder();
        $finder->sortByName();
        $moduleFiles = $finder->in($this->moduleDir)->files()->name(['*.zip', 'logo.png']);

        foreach ($moduleFiles as $moduleFile) {
            $filename = self::MODULE_ASSETS_PREFIX . substr($moduleFile->getPathname(), strlen($this->moduleDir) + 1);
            $output->writeln(sprintf('<info>Upload file %s</info>', $filename));
            $this->bucket->upload($moduleFile->getContents(), ['name' => $filename]);
        }
    }

    private function uploadJson(OutputInterface $output): void
    {
        $finder = new Finder();
        $finder->sortByName();
        $jsonFiles = $finder->in($this->jsonDir)->files();

        if ($jsonFiles->count() === 0) {
            $output->writeln('<error>No json files found!</error>');
            $output->writeln(sprintf(
                '<question>Did you run the `%s` command?</question>',
                GenerateJsonCommand::getDefaultName()
            ));
        }

        foreach ($jsonFiles as $jsonFile) {
            $filename = substr($jsonFile->getPathname(), strlen($this->jsonDir) + 1);
            $output->writeln(sprintf('<info>Upload file %s</info>', $filename));
            $this->bucket->upload($jsonFile->getContents(), ['name' => $filename]);
        }
    }
}
