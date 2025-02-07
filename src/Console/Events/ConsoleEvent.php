<?php

namespace LaraGram\Console\Events;

use LaraGram\Console\Command\Command;
use LaraGram\Console\Input\InputInterface;
use LaraGram\Console\Output\OutputInterface;
use LaraGram\Contracts\Events\Dispatcher;

class ConsoleEvent extends Event
{
    public function __construct(
        protected ?Command $command,
        private InputInterface $input,
        private OutputInterface $output,
    ) {
    }

    /**
     * Gets the command that is executed.
     */
    public function getCommand(): ?Command
    {
        return $this->command;
    }

    /**
     * Gets the input instance.
     */
    public function getInput(): InputInterface
    {
        return $this->input;
    }

    /**
     * Gets the output instance.
     */
    public function getOutput(): OutputInterface
    {
        return $this->output;
    }
}
