<?php

namespace LaraGram\Contracts\Console;

interface Kernel
{
    /**
     * Bootstrap the application for Commander commands.
     *
     * @return void
     */
    public function bootstrap();

    /**
     * Handle an incoming console command.
     *
     * @param  \LaraGram\Console\Input\InputInterface  $input
     * @param  \LaraGram\Console\Output\OutputInterface|null  $output
     * @return int
     */
    public function handle($input, $output = null);

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
     * Get all of the commands registered with the console.
     *
     * @return array
     */
    public function all();

    /**
     * Get the output for the last run command.
     *
     * @return string
     */
    public function output();

    /**
     * Terminate the application.
     *
     * @param  \LaraGram\Console\Input\InputInterface  $input
     * @param  int  $status
     * @return void
     */
    public function terminate($input, $status);
}
