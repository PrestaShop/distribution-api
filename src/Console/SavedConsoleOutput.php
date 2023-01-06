<?php

declare(strict_types=1);

namespace App\Console;

use Symfony\Component\Console\Output\ConsoleOutput;

class SavedConsoleOutput extends ConsoleOutput
{
    private string $messages = '';

    protected function doWrite(string $message, bool $newline): void
    {
        $this->messages .= $message;
        if ($newline) {
            $this->messages .= PHP_EOL;
        }

        parent::doWrite($message, $newline);
    }

    public function getMessages(): string
    {
        return $this->messages;
    }
}
