<?php

namespace LaraGram\Foundation\Console;

use LaraGram\Console\Command;
use LaraGram\Filesystem\Filesystem;
use LaraGram\Foundation\Support\Providers\EventServiceProvider;
use LaraGram\Support\Facades\Console;

class EventCacheCommand extends Command
{
    protected $signature = 'event:cache';
    protected $description = "Discover and cache the application's events and listeners";

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

        file_put_contents(
            $this->app->getCachedEventsPath(),
            '<?php return '.var_export($this->getEvents(), true).';'
        );

        $this->output->success('Events cached successfully.');
    }

    protected function getEvents()
    {
        $events = [];

        foreach ($this->app->getProviders(EventServiceProvider::class) as $provider) {
            $providerEvents = array_merge_recursive($provider->shouldDiscoverEvents() ? $provider->discoverEvents() : [], $provider->listens());

            $events[get_class($provider)] = $providerEvents;
        }

        return $events;
    }
}