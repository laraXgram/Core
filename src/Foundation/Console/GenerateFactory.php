<?php

namespace LaraGram\Foundation\Console;

use LaraGram\Console\Command;
use LaraGram\Support\Facades\Console;

class GenerateFactory extends Command
{
    protected $signature = 'make:factory';
    protected $description = 'Create new database factory';

    public function handle(): void
    {
        if ($this->getOption('h') == 'h') Console::output()->message($this->description, true);

        if ($this->getArgument(0) == null){
            Console::output()->failed("Factory name not set!", true);
        }

        $stub = file_get_contents($this->getStub('/stubs/factory.stub'));
        $name = str_replace('Factory', '', ucfirst($this->getArgument(0)));

        $file_structure = str_replace('%name%', $name, $stub);

        $factory_path = app('path.database') . DIRECTORY_SEPARATOR . "factories";
        if (!file_exists($factory_path)){
            mkdir($factory_path, recursive: true);
        }

        if (file_exists($factory_path . DIRECTORY_SEPARATOR . $name . 'Factory.php')){
            Console::output()->warning("Factory [ $name ] already exist!", exit: true);
        }

        file_put_contents($factory_path . DIRECTORY_SEPARATOR . $name . 'Factory.php', $file_structure);

        Console::output()->success("Factory [ $name ] created successfully!");
    }

    protected function getStub($stub)
    {
        return file_exists($customPath = app()->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__.$stub;
    }
}