<?php

namespace LaraGram\Foundation\Console;

use LaraGram\Console\Command;
use LaraGram\Filesystem\Filesystem;
use RuntimeException;
use LaraGram\Console\Attribute\AsCommand;

#[AsCommand(name: 'view:clear')]
class ViewClearCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'view:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all compiled view files';

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
        $path = $this->laragram['config']['view.compiled'];

        if (! $path) {
            throw new RuntimeException('View path not found.');
        }

        $this->laragram['view.engine.resolver']
            ->resolve('blade')
            ->forgetCompiledOrNotExpired();

        foreach ($this->files->glob("{$path}/*") as $view) {
            if ($this->files->isDirectory($view)) {
                $this->files->deleteDirectory($view);
            } else {
                $this->files->delete($view);
            }
        }

        $this->components->info('Compiled views cleared successfully.');
    }
}
