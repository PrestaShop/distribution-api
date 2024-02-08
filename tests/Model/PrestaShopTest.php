<?php

declare(strict_types=1);

namespace Tests\Model;

use App\Model\PrestaShop;
use PHPUnit\Framework\TestCase;

class PrestaShopTest extends TestCase
{
    /**
     * @dataProvider stableProvider
     */
    public function testIsStable(string $version, bool $expected)
    {
        $prestaShop = new PrestaShop($version);
        $this->assertSame($expected, $prestaShop->isStable());
    }

    /**
     * @dataProvider versionNumberProvider
     */
    public function testVersionNumber(string $version, int $expectedMajor, int $expectedMinor, int $expectedPatch)
    {
        $prestaShop = new PrestaShop($version);
        $this->assertSame($expectedMajor, $prestaShop->getMajorVersionNumber());
        $this->assertSame($expectedMinor, $prestaShop->getMinorVersionNumber());
        $this->assertSame($expectedPatch, $prestaShop->getPatchVersionNumber());
    }

    /**
     * @dataProvider nextVersionsProvider
     */
    public function testNextVersion(string $version, string $nextMajor, string $nextMinor, string $nextPatch)
    {
        $prestaShop = new PrestaShop($version);
        $this->assertSame($nextMajor, $prestaShop->getNextMajorVersion());
        $this->assertSame($nextMinor, $prestaShop->getNextMinorVersion());
        $this->assertSame($nextPatch, $prestaShop->getNextPatchVersion());
    }

    /**
     * @dataProvider rcProvider
     */
    public function testIsRC(string $version, bool $expected)
    {
        $prestaShop = new PrestaShop($version);
        $this->assertSame($expected, $prestaShop->isRC());
    }

    /**
     * @dataProvider betaProvider
     */
    public function testIsBeta(string $version, bool $expected)
    {
        $prestaShop = new PrestaShop($version);
        $this->assertSame($expected, $prestaShop->isBeta());
    }

    public function stableProvider(): iterable
    {
        yield ['1.7.8.0', true];
        yield ['1.7.8.0-rc.1', false];
        yield ['1.7.8.0-rc.2', false];
        yield ['1.7.8.0-beta.1', false];
        yield ['1.7.8.0-beta.2', false];
        yield ['8.0.0', true];
        yield ['8.0.0-rc.1', false];
        yield ['8.0.0-rc.2', false];
        yield ['8.0.0-beta.1', false];
        yield ['8.0.0-beta.2', false];
    }

    public function rcProvider(): iterable
    {
        yield ['1.7.8.0', false];
        yield ['1.7.8.0-rc.1', true];
        yield ['1.7.8.0-rc.2', true];
        yield ['1.7.8.0-beta.1', false];
        yield ['1.7.8.0-beta.2', false];
        yield ['8.0.0', false];
        yield ['8.0.0-rc.1', true];
        yield ['8.0.0-rc.2', true];
        yield ['8.0.0-beta.1', false];
        yield ['8.0.0-beta.2', false];
    }

    public function betaProvider(): iterable
    {
        yield ['1.7.8.0', false];
        yield ['1.7.8.0-rc.1', false];
        yield ['1.7.8.0-rc.2', false];
        yield ['1.7.8.0-beta.1', true];
        yield ['1.7.8.0-beta.2', true];
        yield ['8.0.0', false];
        yield ['8.0.0-rc.1', false];
        yield ['8.0.0-rc.2', false];
        yield ['8.0.0-beta.1', true];
        yield ['8.0.0-beta.2', true];
    }

    public function versionNumberProvider(): iterable
    {
        yield ['1.7.8.0', 7, 8, 0];
        yield ['8.0.0', 8, 0, 0];
        yield ['8.0.0-rc.1', 8, 0, 0];
        yield ['8.0.0-beta.1', 8, 0, 0];
        yield ['8.1.4', 8, 1, 4];
    }

    public function nextVersionsProvider(): iterable
    {
        yield ['1.7.8.0', '8.0.0', '7.9.0', '7.8.1'];
        yield ['8.0.0', '9.0.0', '8.1.0', '8.0.1'];
        yield ['8.0.0-rc.1', '9.0.0', '8.1.0', '8.0.1'];
        yield ['8.0.0-beta.1', '9.0.0', '8.1.0', '8.0.1'];
        yield ['8.1.4', '9.0.0', '8.2.0', '8.1.5'];
        yield ['9.0.0', '10.0.0', '9.1.0', '9.0.1'];
    }
}
