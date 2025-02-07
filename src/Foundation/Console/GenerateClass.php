<?php

namespace LaraGram\Foundation\Console;

use LaraGram\Console\Command;
use LaraGram\Support\Facades\Console;

class GenerateClass extends Command
{
    protected $signature = 'make:class';
    protected $description = 'Create new Class';

    public function handle()
    {
        if ($this->getOption('h') == 'h') Console::output()->message($this->description, true);

        if ($this->getArgument(0) == null){
            Console::output()->failed("Class name not set!", true);
        }

        $stub = file_get_contents($this->getStub('/stubs/class.stub'));
        $name = ucfirst($this->getArgument(0));
        $path = app('path.app') . DIRECTORY_SEPARATOR . 'Classes';

        $file_structure = str_replace('%name%', $name, $stub);

        if (!file_exists($path)){
            mkdir($path, recursive: true);
        }

        if (file_exists($path . DIRECTORY_SEPARATOR . $name . '.php')){
            Console::output()->warning("Class [ $name ] already exist!", exit: true);
        }

        file_put_contents($path . DIRECTORY_SEPARATOR . $name . '.php', $file_structure);

        Console::output()->success("Class [ $name ] created successfully!");
    }

    protected function getStub($stub)
    {
        return file_exists($customPath = app()->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__.$stub;
    }
}