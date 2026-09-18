<?php

namespace LaraGram\Broadcasting\Console;

use LaraGram\Console\Attribute\AsCommand;
use LaraGram\Console\GeneratorCommand;

#[AsCommand(name: 'make:channel')]
class ChannelMakeCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:channel
                    {name : The name of the channel}
                    {--f|force : Create the class even if the channel already exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new WebSocket channel class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Channel';

    /**
     * Build the class with the given name.
     *
     * @param  string  $name
     * @return string
     */
    protected function buildClass($name)
    {
        return str_replace(
            ['DummyUser', '{{ userModel }}'],
            class_basename($this->userProviderModel()),
            parent::buildClass($name)
        );
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return file_exists($customPath = $this->laragram->basePath('stubs/channel.stub'))
            ? $customPath
            : __DIR__.'/stubs/channel.stub';
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Broadcasting';
    }
}
