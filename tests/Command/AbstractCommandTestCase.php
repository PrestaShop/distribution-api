<?php

declare(strict_types=1);

namespace Tests\Command;

use Github\Api\Repo;
use Github\Api\Repository\Contents;
use Github\Client;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AbstractCommandTestCase extends TestCase
{
    protected InputInterface $input;
    protected OutputInterface $output;

    public function setUp(): void
    {
        $this->input = $this->createMock(InputInterface::class);
        $this->output = $this->createMock(OutputInterface::class);
    }

    protected function createGithubClientMock(): Client
    {
        $content = $this->createMock(Contents::class);
        $content->method('show')->willReturnCallback(function ($username, $repo, $filename): array {
            return [
                'sha' => sha1('test'),
                'content' => base64_encode(file_get_contents(__DIR__ . '/../ressources/stubs/' . $filename)),
            ];
        });

        $repo = $this->createMock(Repo::class);
        $repo->method('contents')->willReturn($content);

        $githubClient = $this->createMock(Client::class);
        $githubClient->method('__call')->with('repo')->willReturn($repo);

        return $githubClient;
    }
}
