<?php

declare(strict_types=1);

namespace Tests\Command;

use App\Command\UploadAssetsCommand;
use Google\Cloud\Storage\Bucket;
use PHPUnit\Framework\MockObject\MockObject;

class UploadAssetsCommandTest extends AbstractCommandTestCase
{
    private UploadAssetsCommand $command;
    /** @var Bucket&MockObject */
    private Bucket $bucketMock;

    public function setUp(): void
    {
        parent::setUp();
        $this->bucketMock = $this->createMock(Bucket::class);
        $this->command = new UploadAssetsCommand(
            $this->bucketMock,
            __DIR__ . '/../ressources/json'
        );
    }

    public function testUpload(): void
    {
        $parameters = [
            [$this->anything(), ['name' => 'modules/1.6.1.24.json']],
            [$this->anything(), ['name' => 'modules/1.6.1.4.json']],
            [$this->anything(), ['name' => 'modules/1.7.0.0.json']],
            [$this->anything(), ['name' => 'modules/1.7.7.8.json']],
            [$this->anything(), ['name' => 'modules/1.7.8.0-beta.1.json']],
            [$this->anything(), ['name' => 'modules/1.7.8.0-rc.1.json']],
            [$this->anything(), ['name' => 'modules/1.7.8.1.json']],
            [$this->anything(), ['name' => 'prestashop.json']],
            [$this->anything(), ['name' => 'prestashop/beta.json']],
            [$this->anything(), ['name' => 'prestashop/rc.json']],
            [$this->anything(), ['name' => 'prestashop/stable.json']],
        ];

        $this->output->expects($this->exactly(count($parameters)))->method('writeln');
        $this->bucketMock->expects($this->any())->method('upload')->withConsecutive(...$parameters);

        $this->command->execute($this->input, $this->output);
    }
}
