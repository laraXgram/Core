<?php

namespace LaraGram\Foundation\Objects\Facade;

use LaraGram\Console\Command;
use LaraGram\Support\Facades\Console;

class GenerateFacade extends Command
{
    protected $signature = 'make:facade';
    protected $description = 'Create new Facade class';

    public function handle()
    {
        if ($this->getOption('h') == 'h') $this->output->message($this->description, true);

        if ($this->getArgument(0) == null){
            Console::output()->failed("Facade name not set!", true);
        }

        $stub = file_get_contents($this->getStub('/stubs/facade.stub'));
        $name = str_replace('Facade', '', ucfirst($this->getArgument(0)));
        $path = app('path.app') . DIRECTORY_SEPARATOR . 'Facades';

        $file_structure = str_replace('%name%', $name, $stub);
        $file_structure = str_replace('%accessor%', lcfirst($name), $file_structure);

        if (!file_exists($path)){
            mkdir($path, recursive: true);
        }

        if (file_exists($path . DIRECTORY_SEPARATOR . $name . '.php')){
            $this->output->warning("Facade [ $name ] already exist!", exit: true);
        }

        file_put_contents($path . DIRECTORY_SEPARATOR . $name . '.php', $file_structure);

        $this->output->success("Facade [ $name ] created successfully!");
    }

    protected function getStub($stub)
    {
        return file_exists($customPath = app()->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__.$stub;
    }
}