<?php

namespace LaraGram\Foundation\Console;

use LaraGram\Console\GeneratorCommand;
use LaraGram\Console\Attribute\AsCommand;
use LaraGram\Console\Input\InputOption;

#[AsCommand(name: 'make:conversation')]
class ConversationMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:conversation';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new conversation class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Conversation';

    /**
     * Build the class with the given name.
     *
     * Conversation files return an anonymous class, so the
     * stub is used verbatim without namespace/class replacement.
     *
     * @param  string  $name
     * @return string
     */
    protected function buildClass($name)
    {
        return $this->files->get($this->getStub());
    }

    /**
     * Determine if the conversation already exists at its file path.
     *
     * @param  string  $rawName
     * @return bool
     */
    protected function alreadyExists($rawName)
    {
        return $this->files->exists($this->getPath($this->qualifyClass($rawName)));
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return $this->resolveStubPath('/stubs/conversation.stub');
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
        return $rootNamespace.'\Conversations';
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'Create the conversation even if it already exists'],
        ];
    }
}
