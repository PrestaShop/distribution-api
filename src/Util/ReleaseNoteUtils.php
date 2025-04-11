<?php

namespace App\Util;

use RuntimeException;

class ReleaseNoteUtils
{
    public static function getReleaseNote(string $version): ?string
    {
        $path = __DIR__ . '/../../resources/json/releaseNotes.json';

        if (!file_exists($path)) {
            throw new RuntimeException("JSON file not found at : $path");
        }

        $jsonContent = file_get_contents($path);

        $data = json_decode($jsonContent, true);

        return $data[$version] ?? null;
    }
}
