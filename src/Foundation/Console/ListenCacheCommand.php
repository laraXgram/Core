<?php

namespace LaraGram\Foundation\Console;

use LaraGram\Console\Command;
use LaraGram\Contracts\Console\Kernel as ConsoleKernelContract;
use LaraGram\Filesystem\Filesystem;
use LaraGram\Listening\ListenCollection;
use LaraGram\Console\Attribute\AsCommand;

#[AsCommand(name: 'listen:cache')]
class ListenCacheCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'listen:cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a listen cache file for faster listen registration';

    /**
     * The filesystem instance.
     *
     * @var \LaraGram\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Create a new listen command instance.
     *
     * @param  \LaraGram\Filesystem\Filesystem  $files
     * @return void
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->callSilent('listen:clear');

        $listens = $this->getFreshApplicationListens();

        if (count($listens) === 0) {
            return $this->components->error("Your application doesn't have any listens.");
        }

        foreach ($listens as $listen) {
            $listen->prepareForSerialization();
        }

        $this->files->put(
            $this->laragram->getCachedListensPath(), $this->buildListenCacheFile($listens)
        );

        $this->components->info('Listens cached successfully.');
    }

    /**
     * Boot a fresh copy of the application and get the listens.
     *
     * @return \LaraGram\Listening\ListenCollection
     */
    protected function getFreshApplicationListens()
    {
        return tap($this->getFreshApplication()['listener']->getListens(), function ($listens) {
            $listens->refreshNameLookups();
            $listens->refreshActionLookups();
        });
    }

    /**
     * Get a fresh application instance.
     *
     * @return \LaraGram\Contracts\Foundation\Application
     */
    protected function getFreshApplication()
    {
        return tap(require $this->laragram->bootstrapPath('app.php'), function ($app) {
            $app->make(ConsoleKernelContract::class)->bootstrap();
        });
    }

    /**
     * Build the listen cache file.
     *
     * @param  \LaraGram\Listening\ListenCollection  $listens
     * @return string
     */
    protected function buildListenCacheFile(ListenCollection $listens)
    {
        $stub = $this->files->get(__DIR__.'/stubs/listens.stub');

        return str_replace('{{listens}}', var_export($listens->compile(), true), $stub);
    }
}
