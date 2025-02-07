<?php

namespace LaraGram\Console\Events;

use LaraGram\Console\Command\Command;
use LaraGram\Console\Input\InputInterface;
use LaraGram\Console\Output\OutputInterface;

final class ConsoleTerminateEvent extends ConsoleEvent
{
    public function __construct(
        Command $command,
        InputInterface $input,
        OutputInterface $output,
        private int $exitCode,
        private readonly ?int $interruptingSignal = null,
    ) {
        parent::__construct($command, $input, $output);
    }

    public function setExitCode(int $exitCode): void
    {
        $this->exitCode = $exitCode;
    }

    public function getExitCode(): int
    {
        return $this->exitCode;
    }

    public function getInterruptingSignal(): ?int
    {
        return $this->interruptingSignal;
    }
}
