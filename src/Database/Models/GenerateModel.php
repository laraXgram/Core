<?php

namespace LaraGram\Database\Models;

use LaraGram\Console\Command;

class GenerateModel extends Command
{
    protected $signature = 'make:model';
    protected $description = 'Create new database model';

    public function handle(): void
    {
        if (in_array('-h', $this->arguments)){
            $this->output->message($this->description, true);
        }

        $stub = file_get_contents($this->getStub('/stubs/model.stub'));
        $name = ucfirst($this->getArgument(0));

        $file_structure = str_replace('%name%', $name, $stub);

        if (!file_exists(app('path.model'))){
            mkdir(app('path.model'), recursive: true);
        }

        if (file_exists(app('path.model') . DIRECTORY_SEPARATOR . $name . '.php')){
            $this->output->warning("Model [ $name ] already exist!", exit: true);
        }

        file_put_contents(app('path.model') . DIRECTORY_SEPARATOR . $name . '.php', $file_structure);

        $this->output->success("Model [ $name ] created successfully!");
    }

    protected function getStub($stub)
    {
        return file_exists($customPath = app()->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__.$stub;
    }
}