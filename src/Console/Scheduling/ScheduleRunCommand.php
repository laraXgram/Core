<?php

namespace LaraGram\Console\Scheduling;

use Exception;
use LaraGram\Console\Application;
use LaraGram\Console\Command;
use LaraGram\Console\Events\ScheduledTaskFailed;
use LaraGram\Console\Events\ScheduledTaskFinished;
use LaraGram\Console\Events\ScheduledTaskSkipped;
use LaraGram\Console\Events\ScheduledTaskStarting;
use LaraGram\Contracts\Cache\Repository as Cache;
use LaraGram\Contracts\Debug\ExceptionHandler;
use LaraGram\Contracts\Events\Dispatcher;
use LaraGram\Support\Tempora;
use LaraGram\Support\Facades\Date;
use LaraGram\Support\Sleep;
use LaraGram\Console\Attribute\AsCommand;
use Throwable;

#[AsCommand(name: 'schedule:run')]
class ScheduleRunCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'schedule:run {--whisper : Do not output message indicating that no jobs were ready to run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the scheduled commands';

    /**
     * The schedule instance.
     *
     * @var \LaraGram\Console\Scheduling\Schedule
     */
    protected $schedule;

    /**
     * The 24 hour timestamp this scheduler command started running.
     *
     * @var \LaraGram\Support\Tempora
     */
    protected $startedAt;

    /**
     * Check if any events ran.
     *
     * @var bool
     */
    protected $eventsRan = false;

    /**
     * The event dispatcher.
     *
     * @var \LaraGram\Contracts\Events\Dispatcher
     */
    protected $dispatcher;

    /**
     * The exception handler.
     *
     * @var \LaraGram\Contracts\Debug\ExceptionHandler
     */
    protected $handler;

    /**
     * The cache store implementation.
     *
     * @var \LaraGram\Contracts\Cache\Repository
     */
    protected $cache;

    /**
     * The PHP binary used by the command.
     *
     * @var string
     */
    protected $phpBinary;

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        $this->startedAt = Date::now();

        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @param  \LaraGram\Console\Scheduling\Schedule  $schedule
     * @param  \LaraGram\Contracts\Events\Dispatcher  $dispatcher
     * @param  \LaraGram\Contracts\Cache\Repository  $cache
     * @param  \LaraGram\Contracts\Debug\ExceptionHandler  $handler
     * @return void
     */
    public function handle(Schedule $schedule, Dispatcher $dispatcher, Cache $cache, ExceptionHandler $handler)
    {
        $this->schedule = $schedule;
        $this->dispatcher = $dispatcher;
        $this->cache = $cache;
        $this->handler = $handler;
        $this->phpBinary = Application::phpBinary();

        $events = $this->schedule->dueEvents($this->laragram);

        if ($events->contains->isRepeatable()) {
            $this->clearInterruptSignal();
        }

        $paused = $this->isPaused();

        foreach ($events as $event) {
            if ($paused && ! $event->runsWhenPaused()) {
                $this->dispatcher->dispatch(new ScheduledTaskSkipped($event));

                continue;
            }

            if (! $event->filtersPass($this->laragram)) {
                $this->dispatcher->dispatch(new ScheduledTaskSkipped($event));

                continue;
            }

            if (! $this->eventsRan) {
                $this->newLine();
            }

            if ($event->onOneServer) {
                $this->runSingleServerEvent($event);
            } else {
                $this->runEvent($event);
            }

            $this->eventsRan = true;
        }

        if ($events->contains->isRepeatable()) {
            $this->repeatEvents($events->filter->isRepeatable());
        }

        if (! $this->eventsRan) {
            if (! $this->option('whisper')) {
                $this->components->info('No scheduled commands are ready to run.');
            }
        } else {
            $this->newLine();
        }
    }

    /**
     * Run the given single server event.
     *
     * @param  \LaraGram\Console\Scheduling\Event  $event
     * @return void
     */
    protected function runSingleServerEvent($event)
    {
        if ($this->schedule->serverShouldRun($event, $this->startedAt)) {
            $this->runEvent($event);
        } else {
            $this->components->info(sprintf(
                'Skipping [%s] because the command already ran on another server.', $event->getSummaryForDisplay()
            ));
        }
    }

    /**
     * Run the given event.
     *
     * @param  \LaraGram\Console\Scheduling\Event  $event
     * @return void
     */
    protected function runEvent($event)
    {
        $summary = $event->getSummaryForDisplay();

        $command = $event instanceof CallbackEvent
            ? $summary
            : trim(str_replace($this->phpBinary, '', $event->command));

        $description = sprintf(
            '<fg=gray>%s</> Running [%s]%s',
            Tempora::now()->format('Y-m-d H:i:s'),
            $command,
            $event->runInBackground ? ' in background' : '',
        );

        $this->components->task($description, function () use ($event) {
            $this->dispatcher->dispatch(new ScheduledTaskStarting($event));

            $start = microtime(true);

            try {
                $event->run($this->laragram);

                $this->dispatcher->dispatch(new ScheduledTaskFinished(
                    $event,
                    round(microtime(true) - $start, 2)
                ));

                $this->eventsRan = true;

                if ($event->exitCode != 0 && ! $event->runInBackground) {
                    throw new Exception("Scheduled command [{$event->command}] failed with exit code [{$event->exitCode}].");
                }
            } catch (Throwable $e) {
                $this->dispatcher->dispatch(new ScheduledTaskFailed($event, $e));

                $this->handler->report($e);
            }

            return $event->exitCode == 0;
        });

        if (! $event instanceof CallbackEvent) {
            $this->components->bulletList([
                $event->getSummaryForDisplay(),
            ]);
        }
    }

    /**
     * Run the given repeating events.
     *
     * @param  \LaraGram\Support\Collection<\LaraGram\Console\Scheduling\Event>  $events
     * @return void
     */
    protected function repeatEvents($events)
    {
        $hasEnteredMaintenanceMode = false;

        $endOfMinute = $this->startedAt->copy()->endOfMinute();

        while (Date::now()->lte($endOfMinute)) {
            $paused = $this->isPaused();

            foreach ($events as $event) {
                if ($this->shouldInterrupt()) {
                    return;
                }

                if (! $event->shouldRepeatNow()) {
                    continue;
                }

                if (Date::now()->gt($endOfMinute)) {
                    return;
                }

                $hasEnteredMaintenanceMode = $hasEnteredMaintenanceMode || $this->laragram->isDownForMaintenance();

                if ($hasEnteredMaintenanceMode && ! $event->runsInMaintenanceMode()) {
                    continue;
                }

                if ($paused && ! $event->runsWhenPaused()) {
                    $this->dispatcher->dispatch(new ScheduledTaskSkipped($event));

                    continue;
                }

                if (! $event->filtersPass($this->laragram)) {
                    $this->dispatcher->dispatch(new ScheduledTaskSkipped($event));

                    continue;
                }

                if ($event->onOneServer) {
                    $this->runSingleServerEvent($event);
                } else {
                    $this->runEvent($event);
                }

                $this->eventsRan = true;
            }

            Sleep::usleep(100_000);
        }
    }

    /**
     * Determine if the schedule is paused.
     *
     * @return bool
     */
    protected function isPaused()
    {
        if (! Schedule::$pausable) {
            return false;
        }

        return $this->cache->get('illuminate:schedule:paused', false);
    }

    /**
     * Determine if the schedule run should be interrupted.
     *
     * @return bool
     */
    protected function shouldInterrupt()
    {
        if (! Schedule::$interruptible) {
            return false;
        }

        return $this->cache->get('illuminate:schedule:interrupt', false);
    }

    /**
     * Ensure the interrupt signal is cleared.
     *
     * @return void
     */
    protected function clearInterruptSignal()
    {
        $this->cache->forget('illuminate:schedule:interrupt');
    }
}
