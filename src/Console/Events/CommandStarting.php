<?php

namespace LaraGram\Console\Events;

use LaraGram\Console\Input\InputInterface;
use LaraGram\Console\Output\OutputInterface;

class CommandStarting
{
    /**
     * The command name.
     *
     * @var string
     */
    public $command;

    /**
     * The console input implementation.
     *
     * @var \LaraGram\Console\Input\InputInterface|null
     */
    public $input;

    /**
     * The command output implementation.
     *
     * @var \LaraGram\Console\Output\OutputInterface|null
     */
    public $output;

    /**
     * Create a new event instance.
     *
     * @param  string  $command
     * @param  \LaraGram\Console\Input\InputInterface  $input
     * @param  \LaraGram\Console\Output\OutputInterface  $output
     * @return void
     */
    public function __construct($command, InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
        $this->command = $command;
    }
}
