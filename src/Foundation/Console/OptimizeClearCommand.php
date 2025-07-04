<?php

namespace LaraGram\Foundation\Console;

use LaraGram\Console\Command;
use LaraGram\Support\ServiceProvider;
use LaraGram\Console\Attribute\AsCommand;
use LaraGram\Console\Input\InputOption;

#[AsCommand(name: 'optimize:clear')]
class OptimizeClearCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'optimize:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove the cached bootstrap files';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->components->info('Clearing cached bootstrap files.');

        $exceptions = array_unique(array_filter(array_map('trim', explode(',', $this->option('except') ?? ''))));
        $exceptions = array_flip($exceptions);

        $tasks = array_filter($this->getOptimizeClearTasks(), function ($command, $key) use ($exceptions) {
            return !isset($exceptions[$command]) && !isset($exceptions[$key]);
        }, ARRAY_FILTER_USE_BOTH);

        foreach ($tasks as $description => $command) {
            $this->components->task($description, fn () => $this->callSilently($command) == 0);
        }

        $this->newLine();
    }

    /**
     * Get the commands that should be run to clear the "optimization" files.
     *
     * @return array
     */
    public function getOptimizeClearTasks()
    {
        return [
            'config' => 'config:clear',
            'events' => 'event:clear',
            'listens' => 'listen:clear',
            ...ServiceProvider::$optimizeClearCommands,
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
            ['except', 'e', InputOption::VALUE_OPTIONAL, 'The commands to skip'],
        ];
    }
}
