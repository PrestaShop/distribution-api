<?php

declare(strict_types=1);

namespace Tests\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tests\AbstractMockedGithubClientTestCase;

class AbstractCommandTestCase extends AbstractMockedGithubClientTestCase
{
    protected InputInterface $input;
    protected OutputInterface $output;

    public function setUp(): void
    {
        $this->input = $this->createMock(InputInterface::class);
        $this->output = $this->createMock(OutputInterface::class);
    }
}
