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
    protected static $defaultName = 'upload';

    private Bucket $bucket;

    private string $jsonDir;

    public function __construct(Bucket $bucket, string $jsonDir)
    {
        parent::__construct();
        $this->bucket = $bucket;
        $this->jsonDir = $jsonDir;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
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

            return self::FAILURE;
        }

        foreach ($jsonFiles as $jsonFile) {
            $filename = substr($jsonFile->getPathname(), strlen($this->jsonDir) + 1);
            $output->writeln(sprintf('<info>Upload file %s</info>', $filename));
            $this->bucket->upload($jsonFile->getContents(), ['name' => $filename]);
        }

        return self::SUCCESS;
    }
}
