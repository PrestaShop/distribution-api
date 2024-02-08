<?php

declare(strict_types=1);

namespace Tests\Util;

use App\Model\PrestaShop;
use App\Util\VersionUtils;
use Tests\AbstractMockedGithubClientTestCase;

class VersionUtilsTest extends AbstractMockedGithubClientTestCase
{
    public function testGetHighestStableVersionFromList()
    {
        $this->assertEquals(
            new PrestaShop('9.0.3'),
            (new VersionUtils())->getHighestStableVersionFromList([
              new Prestashop('8.1.4'),
              new Prestashop('8.1.3'),
              new Prestashop('9.0.0'),
              new Prestashop('9.0.3'),
              new Prestashop('9.0.3'),
              new Prestashop('1.7.8.10'),
        ]));
        $this->assertEquals(
            new PrestaShop('8.1.4'),
            (new VersionUtils())->getHighestStableVersionFromList([
              new Prestashop('8.1.4'),
              new Prestashop('8.1.3'),
              new Prestashop('9.0.0-beta'),
              new Prestashop('1.7.8.10'),
        ]));
    }

    public function testGetHighestStableVersionFromListWithEmptyList()
    {
        $this->assertEquals(
            null,
            (new VersionUtils())->getHighestStableVersionFromList([
        ]));
    }

    public function testGetHighestStablePreviousVersionFromList()
    {
        $this->assertEquals(
            new PrestaShop('8.1.4'),
            (new VersionUtils())->getHighestStablePreviousVersionFromList([
              new Prestashop('8.1.4'),
              new Prestashop('8.1.3'),
              new Prestashop('9.0.0'),
              new Prestashop('9.0.3'),
              new Prestashop('9.0.3'),
              new Prestashop('1.7.8.10'),
        ]));
        $this->assertEquals(
            new PrestaShop('1.7.8.10'),
            (new VersionUtils())->getHighestStablePreviousVersionFromList([
              new Prestashop('8.1.4'),
              new Prestashop('8.1.3'),
              new Prestashop('9.0.0-beta'),
              new Prestashop('1.7.8.10'),
        ]));
    }

    public function testGetHighestStablePreviousVersionFromListWithEmptyList()
    {
        $this->assertEquals(
            null,
            (new VersionUtils())->getHighestStablePreviousVersionFromList([
        ]));
    }
}
