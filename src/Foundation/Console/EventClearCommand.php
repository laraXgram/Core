<?php

namespace LaraGram\Foundation\Console;

use LaraGram\Console\Command;
use LaraGram\Filesystem\Filesystem;
use LaraGram\Console\Attribute\AsCommand;

#[AsCommand(name: 'event:clear')]
class EventClearCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'event:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all cached events and listeners';

    /**
     * The filesystem instance.
     *
     * @var \LaraGram\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Create a new config clear command instance.
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
     *
     * @throws \RuntimeException
     */
    public function handle()
    {
        $this->files->delete($this->laragram->getCachedEventsPath());

        $this->components->info('Cached events cleared successfully.');
    }
}
