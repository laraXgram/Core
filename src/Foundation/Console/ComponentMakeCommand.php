<?php

namespace LaraGram\Foundation\Console;

use LaraGram\Console\GeneratorCommand;
use LaraGram\Foundation\Inspiring;
use LaraGram\Support\Collection;
use LaraGram\Support\Str;
use LaraGram\Console\Attribute\AsCommand;
use LaraGram\Console\Input\InputOption;

#[AsCommand(name: 'make:component')]
class ComponentMakeCommand extends GeneratorCommand
{
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
    protected $description = 'Create a new view/template component class';

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
        if ($this->option('view')) {
            return $this->writeView();
        }

        if ($this->option('template')) {
            return $this->writeTemplate();
        }

        if (parent::handle() === false && ! $this->option('force')) {
            return;
        }

        if (! $this->option('inline')) {
            if ($this->option('view')) {
                $this->writeView();
            } else {
                $this->writeTemplate();
            }
        }
    }

    /**
     * Write the view for the component.
     *
     * @return void
     */
    protected function writeView()
    {
        $separator = '/';

        if (windows_os()) {
            $separator = '\\';
        }

        $path = $this->viewPath(
            str_replace('.', $separator, $this->getView()).'.blade.php'
        );

        if (! $this->files->isDirectory(dirname($path))) {
            $this->files->makeDirectory(dirname($path), 0777, true, true);
        }

        if ($this->files->exists($path) && ! $this->option('force')) {
            $this->components->error('View already exists.');

            return;
        }

        file_put_contents(
            $path,
            '<div>
    <!-- '.Inspiring::quotes()->random().' -->
</div>'
        );

        $this->components->info(sprintf('%s [%s] created successfully.', 'View', $path));
    }

    /**
     * Write the template for the component.
     *
     * @return void
     */
    protected function writeTemplate()
    {
        $separator = '/';

        if (windows_os()) {
            $separator = '\\';
        }

        $path = $this->templatePath(
            str_replace('.', $separator, $this->getTemplate()).'.t8.php'
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
            if ($this->option('view')) {
                return str_replace(
                    ['DummyView', '{{ view }}'],
                    "<<<'blade'\n<div>\n    <!-- ".Inspiring::quotes()->random()." -->\n</div>\nblade",
                    parent::buildClass($name)
                );
            }

            return str_replace(
                ['DummyTemplate', '{{ template }}'],
                "<<<'temple'\n@text()\n".Inspiring::quotes()->random()."\n@endText()\ntemple",
                parent::buildClass($name)
            );
        }

        if ($this->option('view')) {
            return str_replace(
                ['DummyView', '{{ view }}'],
                'view(\''.$this->getView().'\')',
                parent::buildClass($name)
            );
        }

        return str_replace(
            ['DummyTemplate', '{{ template }}'],
            'template(\''.$this->getTemplate().'\')',
            parent::buildClass($name)
        );
    }

    /**
     * Get the view name relative to the view path.
     *
     * @return string view
     */
    protected function getView()
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
     * Get the template name relative to the template path.
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
        if ($this->option('view')) {
            return $this->resolveStubPath('/stubs/view-component.stub');
        }

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
        if ($this->option('view')) {
            return $rootNamespace.'\View\Components';
        }

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
            ['inline', 'i', InputOption::VALUE_NONE, 'Create a component that renders an inline view'],
            ['view', 'v', InputOption::VALUE_NONE, 'Create an anonymous component with only a view'],
            ['template', 't', InputOption::VALUE_NONE, 'Create an anonymous component with only a template'],
            ['path', null, InputOption::VALUE_REQUIRED, 'The location where the component view should be created'],
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the component already exists'],
        ];
    }
}
