<?php

namespace LaraGram\Foundation\Console;

use LaraGram\Console\Command;
use LaraGram\Filesystem\Filesystem;
use LaraGram\Foundation\Support\Providers\EventServiceProvider;
use LaraGram\Support\Facades\Console;
use LaraGram\Support\Facades\File;

class EventClearCommand extends Command
{
    protected $signature = 'event:clear';
    protected $description = "Clear all cached events and listeners";

    private Filesystem $filesystem;

    public function __construct()
    {
        parent::__construct();

        $this->filesystem = new Filesystem();
    }

    public function handle()
    {
        if ($this->getOption('h') == 'h') Console::output()->message($this->description, true);

        $this->filesystem->delete($this->app->getCachedEventsPath());

        $this->output->success('Cached events cleared successfully.');
    }
}