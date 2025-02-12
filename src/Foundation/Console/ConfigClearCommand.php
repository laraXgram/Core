<?php

namespace LaraGram\Foundation\Console;

use LaraGram\Console\Command;
use LaraGram\Filesystem\Filesystem;
use LaraGram\Console\Attribute\AsCommand;

#[AsCommand(name: 'config:clear')]
class ConfigClearCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'config:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove the configuration cache file';

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
     */
    public function handle()
    {
        $this->files->delete($this->laragram->getCachedConfigPath());

        $this->components->info('Configuration cache cleared successfully.');
    }
}
