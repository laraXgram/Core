<?php

namespace LaraGram\Console\Event;
final class ConsoleCommandEvent extends ConsoleEvent
{
    /**
     * The return code for skipped commands, this will also be passed into the terminate event.
     */
    public const RETURN_CODE_DISABLED = 113;

    /**
     * Indicates if the command should be run or skipped.
     */
    private bool $commandShouldRun = true;

    /**
     * Disables the command, so it won't be run.
     */
    public function disableCommand(): bool
    {
        return $this->commandShouldRun = false;
    }

    public function enableCommand(): bool
    {
        return $this->commandShouldRun = true;
    }

    /**
     * Returns true if the command is runnable, false otherwise.
     */
    public function commandShouldRun(): bool
    {
        return $this->commandShouldRun;
    }
}
