<?php

namespace LaraGram\Foundation\Console;

use LaraGram\Console\Concerns\CreatesMatchingTest;
use LaraGram\Console\GeneratorCommand;
use LaraGram\Foundation\Inspiring;
use LaraGram\Support\Collection;
use LaraGram\Support\Str;
use LaraGram\Console\Attribute\AsCommand;
use LaraGram\Console\Input\InputOption;

#[AsCommand(name: 'make:component')]
class ComponentMakeCommand extends GeneratorCommand
{
    use CreatesMatchingTest;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:component';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new template component class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Component';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        if ($this->option('template')) {
            return $this->writeTemplate();
        }

        if (parent::handle() === false && ! $this->option('force')) {
            return false;
        }

        if (! $this->option('inline')) {
            $this->writeTemplate();
        }
    }

    /**
     * Write the view for the component.
     *
     * @return void
     */
    protected function writeTemplate()
    {
        $path = $this->templatePath(
            str_replace('.', '/', $this->getTemplate()).'.t8.php'
        );

        if (! $this->files->isDirectory(dirname($path))) {
            $this->files->makeDirectory(dirname($path), 0777, true, true);
        }

        if ($this->files->exists($path) && ! $this->option('force')) {
            $this->components->error('Template already exists.');

            return;
        }

        file_put_contents(
            $path,
            '@text()
'.Inspiring::quotes()->random().'
@endText()'
        );

        $this->components->info(sprintf('%s [%s] created successfully.', 'Template', $path));
    }

    /**
     * Build the class with the given name.
     *
     * @param  string  $name
     * @return string
     */
    protected function buildClass($name)
    {
        if ($this->option('inline')) {
            return str_replace(
                ['DummyTemplate', '{{ view }}'],
                "<<<'temple'\n@text()\n".Inspiring::quotes()->random()."\n@endText()\ntemple",
                parent::buildClass($name)
            );
        }

        return str_replace(
            ['DummyTemplate', '{{ view }}'],
            'template(\''.$this->getTemplate().'\')',
            parent::buildClass($name)
        );
    }

    /**
     * Get the view name relative to the view path.
     *
     * @return string view
     */
    protected function getTemplate()
    {
        $segments = explode('/', str_replace('\\', '/', $this->argument('name')));

        $name = array_pop($segments);

        $path = is_string($this->option('path'))
            ? explode('/', trim($this->option('path'), '/'))
            : [
                'components',
                ...$segments,
            ];

        $path[] = $name;

        return (new Collection($path))
            ->map(fn ($segment) => Str::kebab($segment))
            ->implode('.');
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return $this->resolveStubPath('/stubs/template-component.stub');
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
        return $rootNamespace.'\Template\Components';
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['inline', null, InputOption::VALUE_NONE, 'Create a component that renders an inline template'],
            ['template', null, InputOption::VALUE_NONE, 'Create an anonymous component with only a template'],
            ['path', null, InputOption::VALUE_REQUIRED, 'The location where the component template should be created'],
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the component already exists'],
        ];
    }
}
