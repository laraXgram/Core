<?php

namespace LaraGram\Foundation\Console;

use LaraGram\Console\Command;
use LaraGram\Filesystem\Filesystem;
use LaraGram\Console\Attribute\AsCommand;

#[AsCommand(name: 'listen:clear')]
class ListenClearCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'listen:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove the listen cache file';

    /**
     * The filesystem instance.
     *
     * @var \LaraGram\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Create a new listen clear command instance.
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
        $this->files->delete($this->laragram->getCachedListensPath());

        $this->components->info('Listen cache cleared successfully.');
    }
}
