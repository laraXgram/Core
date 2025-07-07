<?php

namespace LaraGram\Foundation\Console;

use LaraGram\Console\Concerns\CreatesMatchingTest;
use LaraGram\Console\GeneratorCommand;
use LaraGram\Foundation\Inspiring;
use LaraGram\Console\Attribute\AsCommand;
use LaraGram\Console\Input\InputOption;

#[AsCommand(name: 'make:template')]
class TemplateMakeCommand extends GeneratorCommand
{
    use CreatesMatchingTest;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new template';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'make:template';

    /**
     * The type of file being generated.
     *
     * @var string
     */
    protected $type = 'Template';

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
     * Get the destination template path.
     *
     * @param  string  $name
     * @return string
     */
    protected function getPath($name)
    {
        return $this->templatePath(
            $this->getNameInput().'.'.$this->option('extension'),
        );
    }

    /**
     * Get the desired template name from the input.
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
            '/stubs/template.stub',
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
            ['extension', null, InputOption::VALUE_OPTIONAL, 'The extension of the generated template', 't8.php'],
            ['force', 'f', InputOption::VALUE_NONE, 'Create the template even if the template already exists'],
        ];
    }
}
