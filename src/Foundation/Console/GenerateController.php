<?php

namespace LaraGram\Foundation\Console;

use LaraGram\Console\Command;
use LaraGram\Support\Facades\Console;

class GenerateController extends Command
{
    protected $signature = 'make:controller';
    protected $description = 'Create new Controller';

    public function handle()
    {
        if ($this->getOption('h') == 'h') Console::output()->message($this->description, true);
        if ($this->getArgument(0) == null) Console::output()->failed("Controller name not set!", true);

        $stub = file_get_contents($this->getStub('/stubs/controller.stub'));
        $name = str_replace("Controller", '', ucfirst($this->getArgument(0)));
        $name = str_replace('/', DIRECTORY_SEPARATOR, $name);
        $name = str_replace('\\', DIRECTORY_SEPARATOR, $name);

        $path = app('path.app') . DIRECTORY_SEPARATOR . 'Controllers';
        $filePath = $path;

        if (str_contains($name, DIRECTORY_SEPARATOR)) {
            $lastSlashPosition = strrpos($name, DIRECTORY_SEPARATOR);
            if ($lastSlashPosition !== false) {
                $subPath = substr($name, 0, $lastSlashPosition);
                $filePath = $path . DIRECTORY_SEPARATOR . $subPath;
                $namespace = '\\' . str_replace(DIRECTORY_SEPARATOR, '\\', $subPath);
            }
            $file_structure = str_replace('%namespace%', $namespace, $stub);
            $name = substr($name, $lastSlashPosition + 1);
        } else {
            $file_structure = str_replace('%namespace%', '', $stub);
        }

        $file_structure = str_replace('%name%', $name . "Controller", $file_structure);

        if (!file_exists($filePath)) {
            mkdir($filePath, 0755, true);
        }

        $controllerFilePath = $filePath . DIRECTORY_SEPARATOR . $name . 'Controller.php';

        if (file_exists($controllerFilePath)) {
            Console::output()->warning("Controller [ $name ] already exists!", exit: true);
        }

        file_put_contents($controllerFilePath, $file_structure);

        Console::output()->success("Controller [ $name ] created successfully!");
    }

    protected function getStub($stub)
    {
        return file_exists($customPath = app()->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__ . $stub;
    }
}