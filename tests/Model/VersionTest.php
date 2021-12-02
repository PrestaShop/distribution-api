<?php

declare(strict_types=1);

namespace Test\Model;

use App\Model\Version;
use PHPUnit\Framework\TestCase;

class VersionTest extends TestCase
{
    /**
     * @dataProvider versionProvider
     */
    public function testVersions(string $rawVersion, string $expected): void
    {
        $version = new Version($rawVersion);
        $this->assertSame($expected, $version->getVersion());
    }

    public function versionProvider(): iterable
    {
        yield ['v1.8.0', '1.8.0'];
        yield ['v1.8.0-beta', '1.8.0-beta'];
        yield ['1.8.0', '1.8.0'];
        yield ['1.8.0-alpha', '1.8.0-alpha'];
    }
}
