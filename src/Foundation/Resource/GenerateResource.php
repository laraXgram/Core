<?php

namespace LaraGram\Foundation\Resource;

use LaraGram\Console\Command;

class GenerateResource extends Command
{
    protected $signature = 'make:resource';
    protected $description = 'Create new resource file';

    public function handle()
    {
        $stub = file_get_contents($this->getStub('/stubs/resource.stub'));
        $name = lcfirst($this->getArgument(0));

        if (!file_exists(app('path.resource'))){
            mkdir(app('path.resource'), recursive: true);
        }

        if (file_exists(app('path.resource') . DIRECTORY_SEPARATOR . $name . '.php')){
            $this->output->warning("Resource [ $name ] already exist!", exit: true);
        }

        file_put_contents(app('path.resource') . DIRECTORY_SEPARATOR . $name . '.php', $stub);

        $this->output->success("Resource [ $name ] created successfully!");
    }

    protected function getStub($stub)
    {
        return file_exists($customPath = app()->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__.$stub;
    }
}