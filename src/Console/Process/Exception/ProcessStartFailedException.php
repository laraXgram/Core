<?php

namespace LaraGram\Console\Process\Exception;

use LaraGram\Console\Process\Process;

class ProcessStartFailedException extends ProcessFailedException
{
    public function __construct(
        private Process $process,
        ?string $message,
    ) {
        if ($process->isStarted()) {
            throw new InvalidArgumentException('Expected a process that failed during startup, but the given process was started successfully.');
        }

        $error = \sprintf('The command "%s" failed.'."\n\nWorking directory: %s\n\nError: %s",
            $process->getCommandLine(),
            $process->getWorkingDirectory(),
            $message ?? 'unknown'
        );

        // Skip parent constructor
        RuntimeException::__construct($error);
    }

    public function getProcess(): Process
    {
        return $this->process;
    }
}
