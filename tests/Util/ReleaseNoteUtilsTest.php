<?php

namespace Tests\Util;

use App\Util\ReleaseNoteUtils;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ReleaseNoteUtilsTest extends TestCase
{
    private string $jsonPath;
    private string $originalPath;

    protected function setUp(): void
    {
        $this->jsonPath = __DIR__ . '/../../resources/json/releaseNotes.json';

        if (file_exists($this->jsonPath)) {
            $this->originalPath = $this->jsonPath . '.bak';
            rename($this->jsonPath, $this->originalPath);
        }
    }

    protected function tearDown(): void
    {
        if (file_exists($this->jsonPath)) {
            unlink($this->jsonPath);
        }

        if (isset($this->originalPath) && file_exists($this->originalPath)) {
            rename($this->originalPath, $this->jsonPath);
        }
    }

    public function testGetReleaseNoteReturnsCorrectNote(): void
    {
        $data = [
            '1.0.0' => 'Initial release',
            '1.1.0' => 'Added new features',
        ];

        file_put_contents($this->jsonPath, json_encode($data));

        $util = new ReleaseNoteUtils();

        $this->assertEquals('Added new features', $util->getReleaseNote('1.1.0'));
    }

    public function testGetReleaseNoteReturnsNullIfVersionNotFound(): void
    {
        $data = [
            '1.0.0' => 'Initial release',
        ];

        file_put_contents($this->jsonPath, json_encode($data));

        $util = new ReleaseNoteUtils();

        $this->assertNull($util->getReleaseNote('2.0.0'));
    }

    public function testGetReleaseNoteThrowsExceptionIfFileMissing(): void
    {
        $util = new ReleaseNoteUtils();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('JSON file not found');

        $util->getReleaseNote('1.0.0');
    }
}
