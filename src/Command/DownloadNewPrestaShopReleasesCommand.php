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

    private PrestaShopUtils $prestaShopOsUtils;
    private PrestaShopUtils $prestaShopClassicUtils;

    public function __construct(PrestaShopUtils $prestaShopOpenSourceUtils, PrestaShopUtils $prestaShopClassicUtils)
    {
        parent::__construct();
        $this->prestaShopOsUtils = $prestaShopOpenSourceUtils;
        $this->prestaShopClassicUtils = $prestaShopClassicUtils;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $prestaShopOsVersions = $this->prestaShopOsUtils->getVersions();
        $output->writeln(sprintf('<info>%d PrestaShop Open source releases found</info>', count($prestaShopOsVersions)));

        $prestaShopOsVersions = $this->removeAlreadyAvailableVersions($prestaShopOsVersions, $this->prestaShopOsUtils);

        foreach ($prestaShopOsVersions as $prestaShopVersion) {
            $output->writeln(sprintf('<info>Downloading PrestaShop %s</info>', $prestaShopVersion->getVersion()));
            $this->prestaShopOsUtils->download($prestaShopVersion);
        }

        $prestaShopClassicVersions = $this->prestaShopClassicUtils->getVersions();
        $output->writeln(sprintf('<info>%d PrestaShop Classic releases found</info>', count($prestaShopClassicVersions)));

        $prestaShopClassicVersions = $this->removeAlreadyAvailableVersions($prestaShopClassicVersions, $this->prestaShopClassicUtils);

        foreach ($prestaShopClassicVersions as $prestaShopVersion) {
            $output->writeln(sprintf('<info>Downloading PrestaShop %s</info>', $prestaShopVersion->getVersion()));
            $this->prestaShopClassicUtils->download($prestaShopVersion);
        }

        return static::SUCCESS;
    }

    /**
     * @param PrestaShop[] $prestaShop
     *
     * @return PrestaShop[]
     */
    private function removeAlreadyAvailableVersions(array $prestaShop, PrestaShopUtils $prestaShopUtils): array
    {
        $prestaShopVersions = [];
        $alreadyAvailablePrestaShopVersions = array_map(
            fn ($item) => $item->getVersion(), $prestaShopUtils->getVersionsFromBucket()
        );

        foreach ($prestaShop as $ps) {
            if (!in_array($ps->getVersion(), $alreadyAvailablePrestaShopVersions)) {
                $prestaShopVersions[] = $ps;
            }
        }

        return $prestaShopVersions;
    }
}
