<?php

namespace LaraGram\Listening\Console;

use LaraGram\Console\Attribute\AsCommand;
use LaraGram\Console\Input\InputOption;
use LaraGram\Routing\Console\MiddlewareMakeCommand as WebMiddlewareMakeCommand;

#[AsCommand(name: 'make:middleware')]
class MiddlewareMakeCommand extends WebMiddlewareMakeCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:middleware';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new bot middleware class (use --web for an HTTP middleware)';

    /**
     * Resolve the fully-qualified path to the stub.
     *
     * @param  string  $stub
     * @return string
     */
    protected function resolveStubPath($stub)
    {
        if ($this->option('web')) {
            return parent::resolveStubPath($stub);
        }

        return file_exists($customPath = $this->laragram->basePath('stubs/bot.'.basename($stub)))
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
        return $this->option('web')
            ? parent::getDefaultNamespace($rootNamespace)
            : $rootNamespace.'\Middleware';
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return array_merge([
            ['web', null, InputOption::VALUE_NONE, 'Generate an HTTP middleware in app/Http/Middleware'],
        ], parent::getOptions());
    }
}
