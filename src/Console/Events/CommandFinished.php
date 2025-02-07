<?php

namespace LaraGram\Console\Events;

use LaraGram\Console\Input\InputInterface;
use LaraGram\Console\Output\OutputInterface;

class CommandFinished
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
     * The command exit code.
     *
     * @var int
     */
    public $exitCode;

    /**
     * Create a new event instance.
     *
     * @param  string  $command
     * @param  \LaraGram\Console\Input\InputInterface  $input
     * @param  \LaraGram\Console\Output\OutputInterface  $output
     * @param  int  $exitCode
     * @return void
     */
    public function __construct($command, InputInterface $input, OutputInterface $output, $exitCode)
    {
        $this->input = $input;
        $this->output = $output;
        $this->command = $command;
        $this->exitCode = $exitCode;
    }
}
