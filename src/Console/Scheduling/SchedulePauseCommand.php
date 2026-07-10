<?php

namespace LaraGram\Console\Scheduling;

use LaraGram\Console\Command;
use LaraGram\Console\Events\SchedulePaused;
use LaraGram\Contracts\Cache\Repository as Cache;
use LaraGram\Contracts\Events\Dispatcher;
use LaraGram\Console\Attribute\AsCommand;

#[AsCommand(name: 'schedule:pause')]
class SchedulePauseCommand extends Command
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pause the scheduler';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(Cache $cache, Dispatcher $dispatcher)
    {
        if (! Schedule::$pausable) {
            $this->components->error('Schedule pausing is currently disabled.');

            return 1;
        }

        $cache->forever('illuminate:schedule:paused', true);

        $dispatcher->dispatch(new SchedulePaused);

        $this->components->info('Scheduled task processing has been paused.');

        return 0;
    }
}
