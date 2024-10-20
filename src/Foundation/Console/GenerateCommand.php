<?php

namespace LaraGram\Foundation\Console;

use LaraGram\Console\Command;
use LaraGram\Support\Facades\Console;

class GenerateCommand extends Command
{
    protected $signature = 'make:command';
    protected $description = 'Create new terminal command';

    public function handle(): void
    {
        if ($this->getOption('h') == 'h') Console::output()->message($this->description, true);

        if ($this->getArgument(0) == null){
            Console::output()->failed("Command name not set!", true);
        }

        $stub = file_get_contents($this->getStub('/stubs/command.stub'));
        $name = str_replace('Command', '', ucfirst($this->getArgument(0)));


        $file_structure = str_replace('%name%', $name . 'Command', $stub);
        $file_structure = str_replace('%signature%', strtolower($name), $file_structure);

        $command_path = app('path.app') . DIRECTORY_SEPARATOR . 'Commands';
        if (!file_exists($command_path)){
            mkdir($command_path);
        }

        if (file_exists($command_path . DIRECTORY_SEPARATOR . $name . 'Command.php')){
            Console::output()->warning("Command [ $name ] already exist!", exit: true);
        }

        file_put_contents($command_path . DIRECTORY_SEPARATOR . $name . 'Command.php', $file_structure);

        Console::output()->success("Command [ $name ] created successfully!");
    }

    protected function getStub($stub)
    {
        return file_exists($customPath = app()->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__.$stub;
    }
}