<?php

namespace LaraGram\Foundation\Console;

use LaraGram\Console\Concerns\CreatesMatchingTest;
use LaraGram\Console\GeneratorCommand;
use LaraGram\Support\Str;
use LaraGram\Console\Attribute\AsCommand;
use LaraGram\Console\Input\InputInterface;
use LaraGram\Console\Input\InputOption;
use LaraGram\Console\Output\OutputInterface;

use function LaraGram\Console\Prompts\suggest;

#[AsCommand(name: 'make:listener')]
class ListenerMakeCommand extends GeneratorCommand
{
    use CreatesMatchingTest;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:listener';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new event listener class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Listener';

    /**
     * Build the class with the given name.
     *
     * @param  string  $name
     * @return string
     */
    protected function buildClass($name)
    {
        $event = $this->option('event') ?? '';

        if (! Str::startsWith($event, [
            $this->laragram->getNamespace(),
            'LaraGram',
            '\\',
        ])) {
            $event = $this->laragram->getNamespace().'Events\\'.str_replace('/', '\\', $event);
        }

        $stub = str_replace(
            ['DummyEvent', '{{ event }}'], class_basename($event), parent::buildClass($name)
        );

        return str_replace(
            ['DummyFullEvent', '{{ eventNamespace }}'], trim($event, '\\'), $stub
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
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        if ($this->option('queued')) {
            return $this->option('event')
                ? $this->resolveStubPath('/stubs/listener.typed.queued.stub')
                : $this->resolveStubPath('/stubs/listener.queued.stub');
        }

        return $this->option('event')
            ? $this->resolveStubPath('/stubs/listener.typed.stub')
            : $this->resolveStubPath('/stubs/listener.stub');
    }

    /**
     * Determine if the class already exists.
     *
     * @param  string  $rawName
     * @return bool
     */
    protected function alreadyExists($rawName)
    {
        return class_exists($this->qualifyClass($rawName));
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Listeners';
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['event', 'e', InputOption::VALUE_OPTIONAL, 'The event class being listened for'],
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the listener already exists'],
            ['queued', null, InputOption::VALUE_NONE, 'Indicates the event listener should be queued'],
        ];
    }

    /**
     * Interact further with the user if they were prompted for missing arguments.
     *
     * @param  \LaraGram\Console\Input\InputInterface  $input
     * @param  \LaraGram\Console\Output\OutputInterface  $output
     * @return void
     */
    protected function afterPromptingForMissingArguments(InputInterface $input, OutputInterface $output)
    {
        if ($this->isReservedName($this->getNameInput()) || $this->didReceiveOptions($input)) {
            return;
        }

        $event = suggest(
            'What event should be listened for? (Optional)',
            $this->possibleEvents(),
        );

        if ($event) {
            $input->setOption('event', $event);
        }
    }
}
