<?php

namespace LaraGram\Foundation\Console;

use LaraGram\Console\Command;
use LaraGram\Support\ServiceProvider;
use LaraGram\Console\Attribute\AsCommand;
use LaraGram\Console\Input\InputOption;

#[AsCommand(name: 'optimize')]
class OptimizeCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'optimize';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cache framework bootstrap, configuration, and metadata to increase performance';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->components->info('Caching framework bootstrap, configuration, and metadata.');

        $exceptions = array_unique(array_filter(array_map('trim', explode(',', $this->option('except') ?? ''))));
        $exceptions = array_flip($exceptions);

        $tasks = array_filter($this->getOptimizeTasks(), function ($command, $key) use ($exceptions) {
            return !isset($exceptions[$command]) && !isset($exceptions[$key]);
        }, ARRAY_FILTER_USE_BOTH);

        foreach ($tasks as $description => $command) {
            $this->components->task($description, fn () => $this->callSilently($command) == 0);
        }

        $this->newLine();
    }

    /**
     * Get the commands that should be run to optimize the framework.
     *
     * @return array
     */
    protected function getOptimizeTasks()
    {
        return [
            'config' => 'config:cache',
            'events' => 'event:cache',
            ...ServiceProvider::$optimizeCommands,
        ];
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['except', 'e', InputOption::VALUE_OPTIONAL, 'Do not run the commands matching the key or name'],
        ];
    }
}
