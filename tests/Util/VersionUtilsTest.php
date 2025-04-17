<?php

declare(strict_types=1);

namespace Tests\Util;

use App\Model\PrestaShop;
use App\Util\VersionUtils;
use InvalidArgumentException;
use Tests\AbstractMockedGithubClientTestCase;

class VersionUtilsTest extends AbstractMockedGithubClientTestCase
{
    public function testGetHighestStableVersionFromList()
    {
        $this->assertEquals(
            new PrestaShop('9.0.3'),
            (new VersionUtils())->getHighestStableVersionFromList([
                new PrestaShop('8.1.4'),
                new PrestaShop('8.1.3'),
                new PrestaShop('9.0.0'),
                new PrestaShop('9.0.3'),
                new PrestaShop('9.0.3'),
                new PrestaShop('1.7.8.10'),
            ]));
        $this->assertEquals(
            new PrestaShop('8.1.4'),
            (new VersionUtils())->getHighestStableVersionFromList([
                new PrestaShop('8.1.4'),
                new PrestaShop('8.1.3'),
                new PrestaShop('9.0.0-beta'),
                new PrestaShop('1.7.8.10'),
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
                new PrestaShop('8.1.4'),
                new PrestaShop('8.1.3'),
                new PrestaShop('9.0.0'),
                new PrestaShop('9.0.3'),
                new PrestaShop('9.0.3'),
                new PrestaShop('1.7.8.10'),
            ]));
        $this->assertEquals(
            new PrestaShop('1.7.8.10'),
            (new VersionUtils())->getHighestStablePreviousVersionFromList([
                new PrestaShop('8.1.4'),
                new PrestaShop('8.1.3'),
                new PrestaShop('9.0.0-beta'),
                new PrestaShop('1.7.8.10'),
            ]));
    }

    public function testGetHighestStablePreviousVersionFromListWithEmptyList()
    {
        $this->assertEquals(
            null,
            (new VersionUtils())->getHighestStablePreviousVersionFromList([
            ]));
    }

    /**
     * @dataProvider versionProvider
     */
    public function testFormatVersionToSemver(string $input, string $expected): void
    {
        $this->assertSame($expected, (new VersionUtils())->formatVersionToSemver($input));
    }

    public static function versionProvider(): array
    {
        return [
            ['1', '1.0.0'],
            ['1.2', '1.2.0'],
            ['1.2.3', '1.2.3'],
            ['1.2.3.4', '1.2.3'],
            ['1.2.3.4.5', '1.2.3'],
            [' 1.2.3 ', '1.2.3'],
            ['1.2.3-beta', '1.2.3'],
            ['1.2.3+build123', '1.2.3'],
            ['1.2.3-beta+build123', '1.2.3'],
            ['1.2-beta', '1.2.0'],
            ['1.2+meta', '1.2.0'],
            ['0', '0.0.0'],
            ['0.0', '0.0.0'],
            ['0.0.0', '0.0.0'],
        ];
    }

    public function testParseVersionStandardVersion()
    {
        $result = VersionUtils::parseVersion('9.0.0-0.1');
        $this->assertEquals('9.0.0', $result['base']);
        $this->assertEquals('0.1', $result['distribution']);
    }

    public function testParseVersionStandardVersionWithClassicBefore()
    {
        $result = VersionUtils::parseVersion('classic-9.0.0-0.1');
        $this->assertEquals('9.0.0', $result['base']);
        $this->assertEquals('0.1', $result['distribution']);
    }

    public function testParseVersionStandardVersionWithClassicAfter()
    {
        $result = VersionUtils::parseVersion('9.0.0-0.1-classic');
        $this->assertEquals('9.0.0', $result['base']);
        $this->assertEquals('0.1', $result['distribution']);
    }

    public function testParseVersionAnotherStandardVersion()
    {
        $result = VersionUtils::parseVersion('8.2.1-1.5');
        $this->assertEquals('8.2.1', $result['base']);
        $this->assertEquals('1.5', $result['distribution']);
    }

    public function testParseVersionBetaVersion()
    {
        $result = VersionUtils::parseVersion('9.0.0-beta.1-1.0');
        $this->assertEquals('9.0.0-beta.1', $result['base']);
        $this->assertEquals('1.0', $result['distribution']);
    }

    public function testParseVersionRcVersion()
    {
        $result = VersionUtils::parseVersion('9.0.0-rc.1-1.0');
        $this->assertEquals('9.0.0-rc.1', $result['base']);
        $this->assertEquals('1.0', $result['distribution']);
    }

    public function testParseVersionInvalidFormat()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to parse version "invalid-version-string".');
        VersionUtils::parseVersion('invalid-version-string');
    }

    public function testParseVersionThrowsExceptionOnMissingDistribution(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Incomplete version string "9.0.0-".');
        VersionUtils::parseVersion('9.0.0-');
    }
}
