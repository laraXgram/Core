<?php

namespace LaraGram\Console\Concerns;

use LaraGram\Support\Collection;
use LaraGram\Console\Input\ArrayInput;
use LaraGram\Console\Output\NullOutput;
use LaraGram\Console\Output\OutputInterface;

trait CallsCommands
{
    /**
     * Resolve the console command instance for the given command.
     *
     * @param  \LaraGram\Console\Command\Command|string  $command
     * @return \LaraGram\Console\Command\Command
     */
    abstract protected function resolveCommand($command);

    /**
     * Call another console command.
     *
     * @param  \LaraGram\Console\Command\Command|string  $command
     * @param  array  $arguments
     * @return int
     */
    public function call($command, array $arguments = [])
    {
        return $this->runCommand($command, $arguments, $this->output);
    }

    /**
     * Call another console command without output.
     *
     * @param  \LaraGram\Console\Command\Command|string  $command
     * @param  array  $arguments
     * @return int
     */
    public function callSilent($command, array $arguments = [])
    {
        return $this->runCommand($command, $arguments, new NullOutput);
    }

    /**
     * Call another console command without output.
     *
     * @param  \LaraGram\Console\Command\Command|string  $command
     * @param  array  $arguments
     * @return int
     */
    public function callSilently($command, array $arguments = [])
    {
        return $this->callSilent($command, $arguments);
    }

    /**
     * Run the given console command.
     *
     * @param  \LaraGram\Console\Command\Command|string  $command
     * @param  array  $arguments
     * @param  \LaraGram\Console\Output\OutputInterface  $output
     * @return int
     */
    protected function runCommand($command, array $arguments, OutputInterface $output)
    {
        $arguments['command'] = $command;

        $result = $this->resolveCommand($command)->run(
            $this->createInputFromArguments($arguments), $output
        );

        $this->restorePrompts();

        return $result;
    }

    /**
     * Create an input instance from the given arguments.
     *
     * @param  array  $arguments
     * @return \LaraGram\Console\Input\ArrayInput
     */
    protected function createInputFromArguments(array $arguments)
    {
        return tap(new ArrayInput(array_merge($this->context(), $arguments)), function ($input) {
            if ($input->getParameterOption('--no-interaction')) {
                $input->setInteractive(false);
            }
        });
    }

    /**
     * Get all of the context passed to the command.
     *
     * @return array{'--ansi'?: bool, '--no-ansi'?: bool, '--no-interaction'?: bool, '--quiet'?: bool, '--verbose'?: bool}
     */
    protected function context()
    {
        return (new Collection($this->option()))
            ->only([
                'ansi',
                'no-ansi',
                'no-interaction',
                'quiet',
                'verbose',
            ])
            ->filter()
            ->mapWithKeys(fn ($value, $key) => ["--{$key}" => $value])
            ->all();
    }
}
