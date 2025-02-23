<?php

namespace LaraGram\Console\Scheduling;

use LaraGram\Console\Command;
use LaraGram\Console\Events\ScheduledBackgroundTaskFinished;
use LaraGram\Contracts\Events\Dispatcher;
use LaraGram\Support\Collection;
use LaraGram\Console\Attribute\AsCommand;

#[AsCommand(name: 'schedule:finish')]
class ScheduleFinishCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'schedule:finish {id} {code=0}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Handle the completion of a scheduled command';

    /**
     * Indicates whether the command should be shown in the Commander command list.
     *
     * @var bool
     */
    protected $hidden = true;

    /**
     * Execute the console command.
     *
     * @param  \LaraGram\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    public function handle(Schedule $schedule)
    {
        (new Collection($schedule->events()))->filter(function ($value) {
            return $value->mutexName() == $this->argument('id');
        })->each(function ($event) {
            $event->finish($this->laragram, $this->argument('code'));

            $this->laragram->make(Dispatcher::class)->dispatch(new ScheduledBackgroundTaskFinished($event));
        });
    }
}
