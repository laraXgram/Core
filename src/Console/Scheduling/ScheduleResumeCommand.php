<?php

namespace LaraGram\Console\Scheduling;

use LaraGram\Console\Command;
use LaraGram\Console\Events\ScheduleResumed;
use LaraGram\Contracts\Cache\Repository as Cache;
use LaraGram\Contracts\Events\Dispatcher;
use LaraGram\Console\Attribute\AsCommand;

#[AsCommand(name: 'schedule:resume', aliases: ['schedule:continue'])]
class ScheduleResumeCommand extends Command
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Resume the schedule';

    /**
     * The console command name aliases.
     *
     * @var list<string>
     */
    protected $aliases = ['schedule:continue'];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(Cache $cache, Dispatcher $dispatcher)
    {
        $cache->forget('illuminate:schedule:paused');

        $dispatcher->dispatch(new ScheduleResumed);

        $this->components->info('Scheduled task processing has resumed.');

        return 0;
    }
}
