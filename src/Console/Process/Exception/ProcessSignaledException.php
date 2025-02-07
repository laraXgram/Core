<?php

namespace LaraGram\Console\Process\Exception;

use LaraGram\Console\Process\Process;

final class ProcessSignaledException extends RuntimeException
{
    public function __construct(
        private Process $process,
    ) {
        parent::__construct(\sprintf('The process has been signaled with signal "%s".', $process->getTermSignal()));
    }

    public function getProcess(): Process
    {
        return $this->process;
    }

    public function getSignal(): int
    {
        return $this->getProcess()->getTermSignal();
    }
}
