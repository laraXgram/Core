<?php

namespace LaraGram\Listening\Console;

use LaraGram\Console\Attribute\AsCommand;
use LaraGram\Console\GeneratorCommand;
use LaraGram\Console\Input\InputInterface;
use LaraGram\Console\Input\InputOption;
use LaraGram\Console\Output\OutputInterface;
use LaraGram\Routing\Console\ControllerMakeCommand as WebControllerMakeCommand;
use function LaraGram\Console\Prompts\select;
use function LaraGram\Console\Prompts\suggest;

#[AsCommand(name: 'make:controller')]
class ControllerMakeCommand extends WebControllerMakeCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:controller';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new bot controller class (use --web for an HTTP controller)';

    /**
     * The options that only apply to HTTP controllers.
     *
     * @var array<int, string>
     */
    protected $webOptions = ['web', 'api', 'type', 'model', 'parent', 'resource', 'requests', 'singleton', 'creatable'];

    /**
     * Execute the console command.
     *
     * @return bool|null
     */
    public function handle()
    {
        if ($this->generatesWebController()) {
            $this->ensureWebBaseControllerExists();
        }

        return parent::handle();
    }

    /**
     * Determine if an HTTP controller should be generated.
     *
     * @return bool
     */
    protected function generatesWebController()
    {
        foreach ($this->webOptions as $option) {
            if ($this->input->hasOption($option) && $this->option($option)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Create the application's base HTTP controller when it does not exist yet.
     *
     * @return void
     */
    protected function ensureWebBaseControllerExists()
    {
        $rootNamespace = $this->rootNamespace();
        $path = $this->getPath("{$rootNamespace}Http\Controllers\Controller");

        if ($this->files->exists($path)) {
            return;
        }

        $this->makeDirectory($path);

        $this->files->put($path, str_replace(
            '{{ namespace }}', $rootNamespace.'Http\Controllers', $this->files->get(__DIR__.'/stubs/controller.base.stub')
        ));
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        if ($this->generatesWebController()) {
            return parent::getStub();
        }

        return $this->resolveStubPath($this->option('invokable')
            ? '/stubs/controller.invokable.stub'
            : '/stubs/controller.plain.stub');
    }

    /**
     * Resolve the fully-qualified path to the stub.
     *
     * @param  string  $stub
     * @return string
     */
    protected function resolveStubPath($stub)
    {
        if ($this->generatesWebController()) {
            return parent::resolveStubPath($stub);
        }

        $customStub = 'stubs/bot.'.basename($stub);

        return file_exists($customPath = $this->laragram->basePath($customStub))
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
        return $this->generatesWebController()
            ? parent::getDefaultNamespace($rootNamespace)
            : $rootNamespace.'\Controllers';
    }

    /**
     * Build the class with the given name.
     *
     * Remove the base controller import if we are already in the base namespace.
     *
     * @param  string  $name
     * @return string
     */
    protected function buildClass($name)
    {
        if ($this->generatesWebController()) {
            return parent::buildClass($name);
        }

        $rootNamespace = $this->rootNamespace();
        $controllerNamespace = $this->getNamespace($name);

        $replace = [];

        if (file_exists($this->getPath("{$rootNamespace}Controllers\Controller"))) {
            $replace["use {$controllerNamespace}\Controller;\n"] = '';
        } else {
            $replace[' extends Controller'] = '';
            $replace["use {$rootNamespace}Controllers\Controller;\n"] = '';
        }

        return str_replace(
            array_keys($replace), array_values($replace), GeneratorCommand::buildClass($name)
        );
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return array_merge([
            ['web', null, InputOption::VALUE_NONE, 'Generate an HTTP controller in app/Http/Controllers'],
        ], parent::getOptions());
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

        $type = select('Which type of controller would you like?', [
            'bot' => 'Bot',
            'bot-invokable' => 'Bot (invokable)',
            'web' => 'Web',
            'resource' => 'Web resource',
            'singleton' => 'Web singleton',
            'api' => 'Web API',
            'web-invokable' => 'Web (invokable)',
        ]);

        match ($type) {
            'bot' => null,
            'bot-invokable' => $input->setOption('invokable', true),
            'web-invokable' => [$input->setOption('web', true), $input->setOption('invokable', true)],
            default => $input->setOption($type, true),
        };

        if (in_array($type, ['api', 'resource', 'singleton'])) {
            $model = suggest(
                "What model is this $type controller for? (Optional)",
                $this->findAvailableModels()
            );

            if ($model) {
                $input->setOption('model', $model);
            }
        }
    }
}
