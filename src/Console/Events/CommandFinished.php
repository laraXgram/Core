<?php

namespace LaraGram\Console\Events;

use LaraGram\Console\Input\InputInterface;
use LaraGram\Console\Output\OutputInterface;

class CommandFinished
{
    /**
     * Create a new event instance.
     *
     * @param  string  $command  The command name.
     * @param  \LaraGram\Console\Input\InputInterface  $input  The console input implementation.
     * @param  \LaraGram\Console\Output\OutputInterface  $output  The command output implementation.
     * @param  int  $exitCode  The command exit code.
     */
    public function __construct(
        public string $command,
        public InputInterface $input,
        public OutputInterface $output,
        public int $exitCode,
    ) {
    }
}
