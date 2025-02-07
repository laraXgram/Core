<?php

namespace LaraGram\Foundation\Console;

use LaraGram\Console\Command;
use LaraGram\Support\Facades\Console;

class GenerateJsonMigration extends Command
{
    protected $signature = 'make:json-migration';
    protected $description = 'Create new json database migration';

    public function handle(): void
    {
        if ($this->getOption('h') == 'h') Console::output()->message($this->description, true);

        if ($this->getArgument(0) == null){
            Console::output()->failed("Migration name not set!", true);
        }

        $stub = file_get_contents($this->getStub('/stubs/json-migration.stub'));
        $filename = time() . '_' . $this->getArgument(0);
        $type = array_key_first($this->options);
        $name = $this->getOption($type);

        $file_structure = str_replace('%name%', $name, $stub);
        $file_structure = str_replace('%type%', $type, $file_structure);

        $path = app('path.database') . '/json/migrations/';

        if (!file_exists($path)){
            mkdir($path, recursive: true);
        }

        file_put_contents($path . $filename . '.php', $file_structure);

        Console::output()->success("Migration [ $filename ] created successfully!");
    }

    protected function getStub($stub)
    {
        return file_exists($customPath = app()->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__.$stub;
    }
}