<?php

declare(strict_types=1);

namespace App\Command;

use App\Model\PrestaShop;
use App\Util\PrestaShopUtils;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DownloadNewPrestaShopReleasesCommand extends Command
{
    protected static $defaultName = 'downloadNewPrestaShopReleases';

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

        $prestaShopVersions = $this->removeAlreadyAvailableVersions($prestaShopVersions);

        foreach ($prestaShopVersions as $prestaShopVersion) {
            $output->writeln(sprintf('<info>Downloading PrestaShop %s</info>', $prestaShopVersion->getVersion()));
            $this->prestaShopUtils->download($prestaShopVersion);
        }

        return static::SUCCESS;
    }

    /**
     * @param PrestaShop[] $prestaShop
     * @return PrestaShop[]
     */
    private function removeAlreadyAvailableVersions(array $prestaShop): array
    {
        $prestaShopVersions = [];
        $alreadyAvailablePrestaShopVersions = array_map(
            fn ($item) => $item->getVersion(), $this->prestaShopUtils->getVersionsFromBucket()
        );

        foreach ($prestaShop as $ps) {
            if (!in_array($ps->getVersion(), $alreadyAvailablePrestaShopVersions)) {
                $prestaShopVersions[] = $ps;
            }
        }

        return $prestaShopVersions;
    }
}
