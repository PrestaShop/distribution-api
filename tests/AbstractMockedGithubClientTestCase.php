<?php

declare(strict_types=1);

namespace Tests;

use App\Model\PrestaShop;
use Github\Api\Repo;
use Github\Api\Repository\Contents;
use Github\Api\Repository\Releases;
use Github\Client;
use PHPUnit\Framework\TestCase;

abstract class AbstractMockedGithubClientTestCase extends TestCase
{
    protected function createGithubClientMock(string $ditribution): Client
    {
        $content = $this->createMock(Contents::class);
        $content->method('show')->willReturnCallback(function ($username, $repo, $filename): array {
            return [
                'sha' => sha1('test'),
                'content' => base64_encode(file_get_contents(__DIR__ . '/ressources/stubs/' . $filename)),
            ];
        });

        $release = $this->createMock(Releases::class);
        $fileName = $ditribution === PrestaShop::DISTRIBUTION_OPEN_SOURCE ? 'prestashop' : 'prestashop-classic';
        $release->method('all')->willReturnOnConsecutiveCalls(json_decode(file_get_contents(__DIR__ . '/ressources/stubs/' . $fileName . '.json'), true), [], []);

        $repo = $this->createMock(Repo::class);
        $repo->method('contents')->willReturn($content);
        $repo->method('releases')->willReturn($release);

        $githubClient = $this->createMock(Client::class);
        $githubClient->method('__call')->with('repo')->willReturn($repo);

        return $githubClient;
    }
}
