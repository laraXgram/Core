<?php

namespace LaraGram\Foundation\Console;

use LaraGram\Console\Command;
use LaraGram\Support\Facades\Console;

class GenerateProvider extends Command
{
    protected $signature = 'make:provider';
    protected $description = 'Create new service provider';

    public function handle()
    {
        if ($this->getOption('h') == 'h') Console::output()->message($this->description, true);

        if ($this->getArgument(0) == null){
            Console::output()->failed("Provider name not set!", true);
        }

        $stub = file_get_contents($this->getStub('/stubs/provider.stub'));
        $name = str_replace('ServiceProvider', '', ucfirst($this->getArgument(0)));

        $file_structure = str_replace('%name%', $name . 'ServiceProvider', $stub);

        $provider_path = app('path.app') . DIRECTORY_SEPARATOR . 'Providers';
        if (!file_exists($provider_path)){
            mkdir($provider_path, recursive: true);
        }

        if (file_exists($provider_path . DIRECTORY_SEPARATOR . $name . 'ServiceProvider.php')){
            Console::output()->warning("Service Provider [ $name ] already exist!", exit: true);
        }

        file_put_contents($provider_path . DIRECTORY_SEPARATOR . $name . 'ServiceProvider.php', $file_structure);

        Console::output()->success("Service Provider [ $name ] created successfully!");
    }

    protected function getStub($stub)
    {
        return file_exists($customPath = app()->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__.$stub;
    }
}