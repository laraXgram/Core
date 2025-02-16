<?php

namespace LaraGram\Foundation\Console;

use LaraGram\Console\GeneratorCommand;
use LaraGram\Console\Attribute\AsCommand;
use LaraGram\Console\Input\InputInterface;
use LaraGram\Console\Input\InputOption;
use LaraGram\Console\Output\OutputInterface;

use function LaraGram\Console\Prompts\select;

#[AsCommand(name: 'make:enum')]
class EnumMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:enum';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new enum';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Enum';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        if ($this->option('string') || $this->option('int')) {
            return $this->resolveStubPath('/stubs/enum.backed.stub');
        }

        return $this->resolveStubPath('/stubs/enum.stub');
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
        return match (true) {
            is_dir($this->laragram->appPath('Enums')) => $rootNamespace.'\\Enums',
            is_dir($this->laragram->appPath('Enumerations')) => $rootNamespace.'\\Enumerations',
            default => $rootNamespace,
        };
    }

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
        if ($this->option('string') || $this->option('int')) {
            return str_replace(
                ['{{ type }}'],
                $this->option('string') ? 'string' : 'int',
                parent::buildClass($name)
            );
        }

        return parent::buildClass($name);
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
        if ($this->didReceiveOptions($input)) {
            return;
        }

        $type = select('Which type of enum would you like?', [
            'pure' => 'Pure enum',
            'string' => 'Backed enum (String)',
            'int' => 'Backed enum (Integer)',
        ]);

        if ($type !== 'pure') {
            $input->setOption($type, true);
        }
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['string', 's', InputOption::VALUE_NONE, 'Generate a string backed enum.'],
            ['int', 'i', InputOption::VALUE_NONE, 'Generate an integer backed enum.'],
            ['force', 'f', InputOption::VALUE_NONE, 'Create the enum even if the enum already exists'],
        ];
    }
}
