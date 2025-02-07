<?php

namespace LaraGram\Foundation\Console;

use LaraGram\Console\Command;
use LaraGram\Support\Facades\Console;

class GenerateResource extends Command
{
    protected $signature = 'make:resource';
    protected $description = 'Create new resource file';

    public function handle()
    {
        if ($this->getOption('h') == 'h') Console::output()->message($this->description, true);

        if ($this->getArgument(0) == null){
            Console::output()->failed("Resource name not set!", true);
        }

        $stub = file_get_contents($this->getStub('/stubs/resource.stub'));
        $name = lcfirst($this->getArgument(0));

        $resource_path = app('path.app') . DIRECTORY_SEPARATOR . 'Resources';
        if (!file_exists($resource_path)){
            mkdir($resource_path, recursive: true);
        }

        if (file_exists($resource_path . DIRECTORY_SEPARATOR . $name . '.php')){
            Console::output()->warning("Resource [ $name ] already exist!", exit: true);
        }

        file_put_contents($resource_path . DIRECTORY_SEPARATOR . $name . '.php', $stub);

        Console::output()->success("Resource [ $name ] created successfully!");
    }

    protected function getStub($stub)
    {
        return file_exists($customPath = app()->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__.$stub;
    }
}