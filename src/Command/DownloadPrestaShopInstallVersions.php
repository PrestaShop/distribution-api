<?php

declare(strict_types=1);

namespace App\Command;

use App\Util\PrestaShopUtils;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DownloadPrestaShopInstallVersions extends Command
{
    protected static $defaultName = 'downloadPrestaShopInstallVersions';

    private PrestaShopUtils $prestaShopUtils;

    public function __construct(PrestaShopUtils $prestaShopUtils)
    {
        parent::__construct();
        $this->prestaShopUtils = $prestaShopUtils;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $prestaShopVersions = $this->prestaShopUtils->getVersions();
        $output->writeln(sprintf('<info>%d PrestaShop releases found</info>', count($prestaShopVersions)));

        foreach ($prestaShopVersions as $prestaShopVersion) {
            $output->writeln(sprintf('<info>Downloading PrestaShop %s</info>', $prestaShopVersion->getVersion()));
            $this->prestaShopUtils->download($prestaShopVersion);
        }

        return static::SUCCESS;
    }
}
