<?php

namespace App\Util;

use RuntimeException;

class ReleaseNoteUtils
{
    /**
     * @var array<string, string>|null
     */
    private ?array $releaseNoteData = null;

    /**
     * Load json release and assign it to class variable.
     *
     * @return void
     */
    private function loadReleaseNoteJson(): void
    {
        $path = __DIR__ . '/../../resources/json/releaseNotes.json';

        if (!file_exists($path)) {
            throw new RuntimeException("JSON file not found at : $path");
        }

        $jsonContent = file_get_contents($path);
        if ($jsonContent === false) {
            throw new RuntimeException("Failed to read JSON file at : $path");
        }

        /** @var array<string, string> $data */
        $data = json_decode($jsonContent, true);

        if (!is_array($data)) {
            throw new RuntimeException("Invalid JSON structure in file: $path");
        }

        $this->releaseNoteData = $data;
    }

    /**
     * @param string $version
     *
     * @return string|null
     */
    public function getReleaseNote(string $version): ?string
    {
        if ($this->releaseNoteData === null) {
            $this->loadReleaseNoteJson();
        }

        return $this->releaseNoteData[$version] ?? null;
    }
}
