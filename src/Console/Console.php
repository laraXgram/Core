<?php

namespace LaraGram\Console;

use LaraGram\Foundation\CoreCommand;
use LaraGram\Support\Traits\Macroable;

class Console
{
    use Macroable;

    public function output(): Output|false
    {
        return app()->make('console.output');
    }

    public function call(string $command, $args = [])
    {
        if (!app()->has('kernel.core_command')){
            $this->registerCommands();
        }

        $class = app('console.commands')[$command] ?? null;
        if ($class == null){
            return;
        }

        app()->make('kernel')->executeCommand(new $class, $args);
    }

    public function callSilent(string $command, $args = [])
    {
        if (!app()->has('kernel.core_command')){
            $this->registerCommands();
        }

        $class = app('console.commands')[$command] ?? null;
        if ($class == null){
            return;
        }

        app()->make('kernel')->executeCommand(new $class, $args, true);
    }

    public function starting(callable $callback): void
    {
        $callback(new CoreCommand());
    }

    protected function registerCommands()
    {
        app()->singleton(CoreCommand::class);
        app()->alias(CoreCommand::class, 'kernel.core_command');

        if (empty($commands)) {
            $commands = array_merge(app()->app['kernel.core_command']->getCoreCommands(), config('app.service_provider'));
        }

        $kernel = app()['kernel'];
        $commandClasses = [];

        foreach ($commands as $command) {
            if (class_exists($command)) {
                $commandClasses[] = $command;
            }
        }

        $kernel->addCommands($commandClasses);
        $kernel->run();
    }
}