<?php

namespace LaraGram\Console\Event;

use LaraGram\Console\Command\Command;
use LaraGram\Console\Input\InputInterface;
use LaraGram\Console\Output\OutputInterface;

final class ConsoleErrorEvent extends ConsoleEvent
{
    private int $exitCode;

    public function __construct(
        InputInterface $input,
        OutputInterface $output,
        private \Throwable $error,
        ?Command $command = null,
    ) {
        parent::__construct($command, $input, $output);
    }

    public function getError(): \Throwable
    {
        return $this->error;
    }

    public function setError(\Throwable $error): void
    {
        $this->error = $error;
    }

    public function setExitCode(int $exitCode): void
    {
        $this->exitCode = $exitCode;

        $r = new \ReflectionProperty($this->error, 'code');
        $r->setValue($this->error, $this->exitCode);
    }

    public function getExitCode(): int
    {
        return $this->exitCode ?? (\is_int($this->error->getCode()) && 0 !== $this->error->getCode() ? $this->error->getCode() : 1);
    }
}
