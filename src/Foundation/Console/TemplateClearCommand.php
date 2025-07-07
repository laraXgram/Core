<?php

namespace LaraGram\Foundation\Console;

use LaraGram\Console\Command;
use LaraGram\Filesystem\Filesystem;
use RuntimeException;
use LaraGram\Console\Attribute\AsCommand;

#[AsCommand(name: 'template:clear')]
class TemplateClearCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'template:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all compiled template files';

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
        $path = $this->laragram['config']['template.compiled'];

        if (! $path) {
            throw new RuntimeException('Template path not found.');
        }

        $this->laragram['template.engine.resolver']
            ->resolve('temple8')
            ->forgetCompiledOrNotExpired();

        foreach ($this->files->glob("{$path}/*") as $template) {
            $this->files->delete($template);
        }

        $this->components->info('Compiled templates cleared successfully.');
    }
}
