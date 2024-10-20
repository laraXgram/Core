<?php

namespace LaraGram\Foundation\Console;

use LaraGram\Console\Command;
use LaraGram\Support\Facades\Console;

class GenerateModel extends Command
{
    protected $signature = 'make:model';
    protected $description = 'Create new database model';

    public function handle(): void
    {
        if ($this->getOption('h') == 'h') Console::output()->message($this->description, true);

        if ($this->getArgument(0) == null){
            Console::output()->failed("Model name not set!", true);
        }

        $stub = file_get_contents($this->getStub('/stubs/model.stub'));
        $name = ucfirst($this->getArgument(0));

        $file_structure = str_replace('%name%', $name, $stub);

        $model_path = app('path.app') . DIRECTORY_SEPARATOR . "Models";
        if (!file_exists($model_path)){
            mkdir($model_path, recursive: true);
        }

        if (file_exists($model_path . DIRECTORY_SEPARATOR . $name . '.php')){
            Console::output()->warning("Model [ $name ] already exist!", exit: true);
        }

        file_put_contents($model_path . DIRECTORY_SEPARATOR . $name . '.php', $file_structure);

        Console::output()->success("Model [ $name ] created successfully!");
    }

    protected function getStub($stub)
    {
        return file_exists($customPath = app()->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__.$stub;
    }
}