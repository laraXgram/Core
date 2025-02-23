<?php

namespace LaraGram\Contracts\Console;

interface Application
{
    /**
     * Run an Commander console command by name.
     *
     * @param  string  $command
     * @param  array  $parameters
     * @param  \LaraGram\Console\Output\OutputInterface|null  $outputBuffer
     * @return int
     */
    public function call($command, array $parameters = [], $outputBuffer = null);

    /**
     * Get the output from the last command.
     *
     * @return string
     */
    public function output();
}
