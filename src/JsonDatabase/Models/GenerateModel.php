<?php

namespace LaraGram\JsonDatabase\Models;

use LaraGram\Console\Command;
use LaraGram\Support\Facades\Console;

class GenerateModel extends Command
{
    protected $signature = 'make:json-model';
    protected $description = 'Create new database model';

    public function handle(): void
    {
        if ($this->getOption('h') == 'h') $this->output->message($this->description, true);

        if ($this->getArgument(0) == null){
            Console::output()->failed("Model name not set!", true);
        }

        $stub = file_get_contents($this->getStub('/stubs/model.stub'));
        $name = ucfirst($this->getArgument(0));

        $file_structure = str_replace('%name%', $name, $stub);

        $path = app('path.app') . '/Models/Json/';

        if (!file_exists($path)){
            mkdir($path, recursive: true);
        }

        if (file_exists($path . DIRECTORY_SEPARATOR . $name . '.php')){
            $this->output->warning("Model [ $name ] already exist!", exit: true);
        }

        file_put_contents($path . DIRECTORY_SEPARATOR . $name . '.php', $file_structure);

        $this->output->success("Model [ $name ] created successfully!");
    }

    protected function getStub($stub)
    {
        return file_exists($customPath = app()->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__.$stub;
    }
}