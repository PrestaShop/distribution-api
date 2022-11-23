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
            __DIR__ . '/../ressources/json',
            __DIR__ . '/../ressources/prestashop',
            __DIR__ . '/../ressources/modules'
        );
    }

    public function testUpload(): void
    {
        $parameters = [
            [$this->anything(), ['name' => 'assets/prestashop/1.6.1.24/prestashop.zip']],
            [$this->anything(), ['name' => 'assets/prestashop/1.6.1.4/prestashop.zip']],
            [$this->anything(), ['name' => 'assets/prestashop/1.7.0.0/prestashop.zip']],
            [$this->anything(), ['name' => 'assets/prestashop/1.7.7.8/prestashop.zip']],
            [$this->anything(), ['name' => 'assets/prestashop/1.7.8.0-beta.1/prestashop.zip']],
            [$this->anything(), ['name' => 'assets/prestashop/1.7.8.0-rc.1/prestashop.zip']],
            [$this->anything(), ['name' => 'assets/prestashop/1.7.8.1/prestashop.zip']],
            [$this->anything(), ['name' => 'assets/modules/psgdpr/v1.3.0/logo.png']],
            [$this->anything(), ['name' => 'assets/modules/psgdpr/v1.3.0/psgdpr.zip']],
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

        $this->output->expects($this->exactly(count($parameters) + 1))->method('writeln');
        $this->bucketMock->expects($this->any())->method('upload')->withConsecutive(...$parameters);

        $this->command->execute($this->input, $this->output);
    }
}
