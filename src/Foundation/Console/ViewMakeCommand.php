<?php

namespace LaraGram\Foundation\Console;

use LaraGram\Console\GeneratorCommand;
use LaraGram\Foundation\Inspiring;
use LaraGram\Console\Attribute\AsCommand;
use LaraGram\Console\Input\InputOption;

#[AsCommand(name: 'make:view')]
class ViewMakeCommand extends GeneratorCommand
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new view';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'make:view';

    /**
     * The type of file being generated.
     *
     * @var string
     */
    protected $type = 'View';

    /**
     * Build the class with the given name.
     *
     * @param  string  $name
     * @return string
     *
     * @throws \LaraGram\Contracts\Filesystem\FileNotFoundException
     */
    protected function buildClass($name)
    {
        $contents = parent::buildClass($name);

        return str_replace(
            '{{ quote }}',
            Inspiring::quotes()->random(),
            $contents,
        );
    }

    /**
     * Get the destination view path.
     *
     * @param  string  $name
     * @return string
     */
    protected function getPath($name)
    {
        return $this->viewPath(
            $this->getNameInput().'.'.$this->option('extension'),
        );
    }

    /**
     * Get the desired view name from the input.
     *
     * @return string
     */
    protected function getNameInput()
    {
        $name = trim($this->argument('name'));

        $name = str_replace(['\\', '.'], '/', $name);

        return $name;
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return $this->resolveStubPath(
            '/stubs/view.stub',
        );
    }

    /**
     * Resolve the fully-qualified path to the stub.
     *
     * @param  string  $stub
     * @return string
     */
    protected function resolveStubPath($stub)
    {
        return file_exists($customPath = $this->laragram->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__.$stub;
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['extension', null, InputOption::VALUE_OPTIONAL, 'The extension of the generated view', 'blade.php'],
            ['force', 'f', InputOption::VALUE_NONE, 'Create the view even if the view already exists'],
        ];
    }
}
