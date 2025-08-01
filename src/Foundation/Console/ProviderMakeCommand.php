<?php

namespace LaraGram\Foundation\Console;

use LaraGram\Console\GeneratorCommand;
use LaraGram\Support\ServiceProvider;
use LaraGram\Console\Attribute\AsCommand;
use LaraGram\Console\Input\InputOption;

#[AsCommand(name: 'make:provider')]
class ProviderMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:provider';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new service provider class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Provider';

    /**
     * Execute the console command.
     *
     * @return bool|null
     *
     * @throws \LaraGram\Contracts\Filesystem\FileNotFoundException
     */
    public function handle()
    {
        $result = parent::handle();

        if ($result === false) {
            return $result;
        }

        ServiceProvider::addProviderToBootstrapFile(
            $this->qualifyClass($this->getNameInput()),
            $this->laragram->getBootstrapProvidersPath(),
        );

        return $result;
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return $this->resolveStubPath('/stubs/provider.stub');
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
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Providers';
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the provider already exists'],
        ];
    }
}
