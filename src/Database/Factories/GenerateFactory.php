<?php

namespace LaraGram\Database\Factories;

use LaraGram\Console\Command;

class GenerateFactory extends Command
{
    protected $signature = 'make:factory';
    protected $description = 'Create new database factory';

    public function handle(): void
    {
        if (in_array('-h', $this->arguments)){
            $this->output->message($this->description, true);
        }

        $stub = file_get_contents($this->getStub('/stubs/factory.stub'));
        $name = str_replace('Factory', '', ucfirst($this->getArgument(0)));

        $file_structure = str_replace('%name%', $name, $stub);

        if (!file_exists(app('path.factory'))){
            mkdir(app('path.factory'), recursive: true);
        }

        if (file_exists(app('path.factory') . DIRECTORY_SEPARATOR . $name . 'Factory.php')){
            $this->output->warning("Factory [ $name ] already exist!", exit: true);
        }

        file_put_contents(app('path.factory') . DIRECTORY_SEPARATOR . $name . 'Factory.php', $file_structure);

        $this->output->success("Factory [ $name ] created successfully!");
    }

    protected function getStub($stub)
    {
        return file_exists($customPath = app()->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__.$stub;
    }
}