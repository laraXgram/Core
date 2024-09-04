<?php

namespace LaraGram\Console;

use LaraGram\Support\Trait\Macroable;

class Console
{
    use Macroable;

    public function output(): Output
    {
        return app()->make('console.output');
    }

    public function run(string $command, $args = [])
    {
        if (!app()->has('kernel')){
            app()->registerKernel()->registerCommands();
        }

        $class = app('console.commands')[$command] ?? null;
        if ($class == null){
            return;
        }

        app()->make('kernel')->executeCommand(new $class, $args);
    }
}