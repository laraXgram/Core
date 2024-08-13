<?php

namespace LaraGram\Database\Seeders;


use LaraGram\Console\Command;
use LaraGram\Support\Facades\Console;

class GenerateSeeder extends Command
{
    protected $signature = 'make:seeder';
    protected $description = 'Create new database seeder';

    public function handle(): void
    {
        if ($this->getOption('h') == 'h') Console::output()->message($this->description, true);

        if ($this->getArgument(0) == null){
            Console::output()->failed("Seeder name not set!", true);
        }

        $stub = file_get_contents($this->getStub('/stubs/seeder.stub'));
        $name = str_replace('Seeder', '', ucfirst($this->getArgument(0)));

        $file_structure = str_replace('%name%', $name, $stub);

        $seeder_path = app('path.database') . DIRECTORY_SEPARATOR . 'Seeders';
        if (!file_exists($seeder_path)){
            mkdir($seeder_path, recursive: true);
        }

        if (file_exists($seeder_path . DIRECTORY_SEPARATOR . $name . 'Seeder.php')){
            Console::output()->warning("Seed [ $name ] already exist!", exit: true);
        }

        file_put_contents($seeder_path . DIRECTORY_SEPARATOR . $name . 'Seeder.php', $file_structure);

        Console::output()->success("Seed [ $name ] created successfully!");
    }

    protected function getStub($stub)
    {
        return file_exists($customPath = app()->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__.$stub;
    }
}