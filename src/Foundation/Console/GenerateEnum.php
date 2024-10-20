<?php

namespace LaraGram\Foundation\Console;

use LaraGram\Console\Command;
use LaraGram\Support\Facades\Console;

class GenerateEnum extends Command
{
    protected $signature = 'make:enum';
    protected $description = 'Create new Enum';

    public function handle()
    {
        if ($this->getOption('h') == 'h') Console::output()->message($this->description, true);

        if ($this->getArgument(0) == null){
            Console::output()->failed("Enum name not set!", true);
        }

        $stub = file_get_contents($this->getStub('/stubs/enum.stub'));
        $name = ucfirst($this->getArgument(0));
        $path = app('path.app') . DIRECTORY_SEPARATOR . 'Enums';

        $file_structure = str_replace('%name%', $name, $stub);

        if (!file_exists($path)){
            mkdir($path, recursive: true);
        }

        if (file_exists($path . DIRECTORY_SEPARATOR . $name . '.php')){
            Console::output()->warning("Enum [ $name ] already exist!", exit: true);
        }

        file_put_contents($path . DIRECTORY_SEPARATOR . $name . '.php', $file_structure);

        Console::output()->success("Enum [ $name ] created successfully!");
    }

    protected function getStub($stub)
    {
        return file_exists($customPath = app()->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__.$stub;
    }
}