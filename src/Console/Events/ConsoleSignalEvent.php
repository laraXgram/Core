<?php

namespace LaraGram\Console\Events;

use LaraGram\Console\Command\Command;
use LaraGram\Console\Input\InputInterface;
use LaraGram\Console\Output\OutputInterface;
final class ConsoleSignalEvent extends ConsoleEvent
{
    public function __construct(
        Command $command,
        InputInterface $input,
        OutputInterface $output,
        private int $handlingSignal,
        private int|false $exitCode = 0,
    ) {
        parent::__construct($command, $input, $output);
    }

    public function getHandlingSignal(): int
    {
        return $this->handlingSignal;
    }

    public function setExitCode(int $exitCode): void
    {
        if ($exitCode < 0 || $exitCode > 255) {
            throw new \InvalidArgumentException('Exit code must be between 0 and 255.');
        }

        $this->exitCode = $exitCode;
    }

    public function abortExit(): void
    {
        $this->exitCode = false;
    }

    public function getExitCode(): int|false
    {
        return $this->exitCode;
    }
}
