<?php

declare(strict_types=1);

namespace Tests\Command;

use App\Command\UpdateModuleConfigFilesCommand;
use App\Util\ModuleUtils;
use App\Util\PublicDownloadUrlProvider;
use Github\Api\Repository\Contents;
use Github\Client as GithubClient;
use Google\Cloud\Storage\Bucket;
use GuzzleHttp\Client;
use PHPUnit\Framework\MockObject\MockObject;
use Psssst\ModuleParser;
use Symfony\Component\Yaml\Yaml;

class UpdateModuleConfigFilesCommandTest extends AbstractCommandTestCase
{
    private const MIN_PRESTASHOP_VERSION = '8.0.0';

    private UpdateModuleConfigFilesCommand $command;

    /** @var GithubClient&MockObject */
    private GithubClient $githubClient;

    public function setUp(): void
    {
        parent::setUp();

        $this->githubClient = $this->createGithubClientMock();

        $moduleUtils = new ModuleUtils(
            new ModuleParser(),
            $this->createMock(Client::class),
            $this->githubClient,
            $this->createMock(Bucket::class),
            new PublicDownloadUrlProvider(''),
            'prestashop/native-modules',
            self::MIN_PRESTASHOP_VERSION,
            __DIR__ . '/../ressources/modules',
        );

        $this->command = new UpdateModuleConfigFilesCommand(
            $moduleUtils,
            $this->githubClient,
            'prestashop/native-modules'
        );
    }

    public function testUpdateConfigFiles(): void
    {
        /** @var Contents&MockObject $contents */
        $contents = $this->githubClient->repo()->contents();
        $newConfig = [
            'v1.2.0' => [
                'min' => '1.7',
                'max' => null,
            ],
            'v1.2.1' => [
                'min' => '1.7',
                'max' => null,
            ],
            'v1.3.0' => [
                'min' => '1.7',
                'max' => null,
            ],
        ];
        $contents->expects($this->once())->method('update')->with(
            'prestashop',
            'native-modules',
            'psgdpr.yml',
            Yaml::dump($newConfig)
        );

        $this->command->execute($this->input, $this->output);
    }
}
