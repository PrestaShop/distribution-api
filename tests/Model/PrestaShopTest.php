<?php

declare(strict_types=1);

namespace Test\Model;

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
}
