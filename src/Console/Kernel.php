<?php

namespace LaraGram\Console;

class Kernel
{
    protected array $commands = [];

    public function addCommand(Command $command): void
    {
        $this->commands[] = $command;
    }

    public function run(): void
    {
        global $argv;
        if (count($argv) < 2) {
            return;
        }

        $commandName = $argv[1];

        foreach ($this->commands as $command) {
            if ($commandName == $command->getSignature()) {
                $this->executeCommand($command, array_slice($argv, 2));
                return;
            }
        }

        (new Output())->warning("Command not found: $commandName", exit: true);
    }

    protected function executeCommand(Command $command, $args): void
    {
        $arguments = [];
        $options = [];

        foreach ($args as $arg) {
            if (str_starts_with($arg, '--')) {
                $arg = explode('=', substr($arg, 2), 2);
                $options[$arg[0]] = $arg[1] ?? $arg[0];
            } elseif (str_starts_with($arg, '-')) {
                $arg = explode('=', substr($arg, 1), 2);
                $options[$arg[0]] = $arg[1] ?? $arg[0];
            } else {
                $arguments[] = $arg;
            }
        }

        foreach ($arguments as $index => $argument) {
            $command->setArgument($index, $argument);
        }

        foreach ($options as $name => $value) {
            $command->setOption($name, $value);
        }

        $command->handle();
    }
}