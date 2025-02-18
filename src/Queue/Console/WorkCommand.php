<?php

namespace LaraGram\Queue\Console;

use DateTime;
use DateTimeZone;
use LaraGram\Console\Command;
use LaraGram\Contracts\Cache\Repository as Cache;
use LaraGram\Contracts\Queue\Job;
use LaraGram\Queue\Events\JobFailed;
use LaraGram\Queue\Events\JobProcessed;
use LaraGram\Queue\Events\JobProcessing;
use LaraGram\Queue\Events\JobReleasedAfterException;
use LaraGram\Queue\Worker;
use LaraGram\Queue\WorkerOptions;
use LaraGram\Support\InteractsWithTime;
use LaraGram\Support\Stringable;
use LaraGram\Console\Attribute\AsCommand;
use LaraGram\Console\Terminal;
use Throwable;

use function LaraGram\Console\Prompts\Convertor\terminal;

#[AsCommand(name: 'queue:work')]
class WorkCommand extends Command
{
    use InteractsWithTime;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'queue:work
                            {connection? : The name of the queue connection to work}
                            {--name=default : The name of the worker}
                            {--queue= : The names of the queues to work}
                            {--daemon : Run the worker in daemon mode (Deprecated)}
                            {--once : Only process the next job on the queue}
                            {--stop-when-empty : Stop when the queue is empty}
                            {--delay=0 : The number of seconds to delay failed jobs (Deprecated)}
                            {--backoff=0 : The number of seconds to wait before retrying a job that encountered an uncaught exception}
                            {--max-jobs=0 : The number of jobs to process before stopping}
                            {--max-time=0 : The maximum number of seconds the worker should run}
                            {--force : Force the worker to run even in maintenance mode}
                            {--memory=128 : The memory limit in megabytes}
                            {--sleep=3 : Number of seconds to sleep when no job is available}
                            {--rest=0 : Number of seconds to rest between jobs}
                            {--timeout=60 : The number of seconds a child process can run}
                            {--tries=1 : Number of times to attempt a job before logging it failed}
                            {--json : Output the queue worker information as JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start processing jobs on the queue as a daemon';

    /**
     * The queue worker instance.
     *
     * @var \LaraGram\Queue\Worker
     */
    protected $worker;

    /**
     * The cache store implementation.
     *
     * @var \LaraGram\Contracts\Cache\Repository
     */
    protected $cache;

    /**
     * Holds the start time of the last processed job, if any.
     *
     * @var float|null
     */
    protected $latestStartedAt;

    /**
     * Indicates if the worker's event listeners have been registered.
     *
     * @var bool
     */
    private static $hasRegisteredListeners = false;

    /**
     * Create a new queue work command.
     *
     * @param  \LaraGram\Queue\Worker  $worker
     * @param  \LaraGram\Contracts\Cache\Repository  $cache
     * @return void
     */
    public function __construct(Worker $worker, Cache $cache)
    {
        parent::__construct();

        $this->cache = $cache;
        $this->worker = $worker;
    }

    /**
     * Execute the console command.
     *
     * @return int|null
     */
    public function handle()
    {
        if ($this->option('once')) {
            return $this->worker->sleep($this->option('sleep'));
        }

        // We'll listen to the processed and failed events so we can write information
        // to the console as jobs are processed, which will let the developer watch
        // which jobs are coming through a queue and be informed on its progress.
        $this->listenForEvents();

        $connection = $this->argument('connection')
                        ?: $this->laragram['config']['queue.default'];

        // We need to get the right queue for the connection which is set in the queue
        // configuration file for the application. We will pull it based on the set
        // connection being run for the queue operation currently being executed.
        $queue = $this->getQueue($connection);

        if (! $this->outputUsingJson() && Terminal::hasSttyAvailable()) {
            $this->components->info(
                sprintf('Processing jobs from the [%s] %s.', $queue, (new Stringable('queue'))->plural(explode(',', $queue)))
            );
        }

        return $this->runWorker(
            $connection, $queue
        );
    }

    /**
     * Run the worker instance.
     *
     * @param  string  $connection
     * @param  string  $queue
     * @return int|null
     */
    protected function runWorker($connection, $queue)
    {
        return $this->worker
            ->setName($this->option('name'))
            ->setCache($this->cache)
            ->{$this->option('once') ? 'runNextJob' : 'daemon'}(
                $connection, $queue, $this->gatherWorkerOptions()
            );
    }

    /**
     * Gather all of the queue worker options as a single object.
     *
     * @return \LaraGram\Queue\WorkerOptions
     */
    protected function gatherWorkerOptions()
    {
        return new WorkerOptions(
            $this->option('name'),
            max($this->option('backoff'), $this->option('delay')),
            $this->option('memory'),
            $this->option('timeout'),
            $this->option('sleep'),
            $this->option('tries'),
            $this->option('force'),
            $this->option('stop-when-empty'),
            $this->option('max-jobs'),
            $this->option('max-time'),
            $this->option('rest')
        );
    }

    /**
     * Listen for the queue events in order to update the console output.
     *
     * @return void
     */
    protected function listenForEvents()
    {
        if (static::$hasRegisteredListeners) {
            return;
        }

        $this->laragram['events']->listen(JobProcessing::class, function ($event) {
            $this->writeOutput($event->job, 'starting');
        });

        $this->laragram['events']->listen(JobProcessed::class, function ($event) {
            $this->writeOutput($event->job, 'success');
        });

        $this->laragram['events']->listen(JobReleasedAfterException::class, function ($event) {
            $this->writeOutput($event->job, 'released_after_exception');
        });

        $this->laragram['events']->listen(JobFailed::class, function ($event) {
            $this->writeOutput($event->job, 'failed', $event->exception);

            $this->logFailedJob($event);
        });

        static::$hasRegisteredListeners = true;
    }

    /**
     * Write the status output for the queue worker for JSON or TTY.
     *
     * @param  Job  $job
     * @param  string  $status
     * @param  Throwable|null  $exception
     * @return void
     */
    protected function writeOutput(Job $job, $status, ?Throwable $exception = null)
    {
        $this->outputUsingJson()
            ? $this->writeOutputAsJson($job, $status, $exception)
            : $this->writeOutputForCli($job, $status);
    }

    /**
     * Write the status output for the queue worker.
     *
     * @param  \LaraGram\Contracts\Queue\Job  $job
     * @param  string  $status
     * @return void
     */
    protected function writeOutputForCli(Job $job, $status)
    {
        $this->output->write(sprintf(
            '  <fg=gray>%s</> %s%s',
            $this->now()->format('Y-m-d H:i:s'),
            $job->resolveName(),
            $this->output->isVerbose()
                ? sprintf(' <fg=gray>%s</>', $job->getJobId())
                : ''
        ));

        if ($status == 'starting') {
            $this->latestStartedAt = microtime(true);

            $dots = max(terminal()->width() - mb_strlen($job->resolveName()) - (
                $this->output->isVerbose() ? (mb_strlen($job->getJobId()) + 1) : 0
            ) - 33, 0);

            $this->output->write(' '.str_repeat('<fg=gray>.</>', $dots));

            return $this->output->writeln(' <fg=yellow;options=bold>RUNNING</>');
        }

        $runTime = $this->runTimeForHumans($this->latestStartedAt);

        $dots = max(terminal()->width() - mb_strlen($job->resolveName()) - (
            $this->output->isVerbose() ? (mb_strlen($job->getJobId()) + 1) : 0
        ) - mb_strlen($runTime) - 31, 0);

        $this->output->write(' '.str_repeat('<fg=gray>.</>', $dots));
        $this->output->write(" <fg=gray>$runTime</>");

        $this->output->writeln(match ($status) {
            'success' => ' <fg=green;options=bold>DONE</>',
            'released_after_exception' => ' <fg=yellow;options=bold>FAIL</>',
            default => ' <fg=red;options=bold>FAIL</>',
        });
    }

    /**
     * Write the status output for the queue worker in JSON format.
     *
     * @param  \LaraGram\Contracts\Queue\Job  $job
     * @param  string  $status
     * @param  Throwable|null  $exception
     * @return void
     */
    protected function writeOutputAsJson(Job $job, $status, ?Throwable $exception = null)
    {
        $log = array_filter([
            'level' => $status === 'starting' || $status === 'success' ? 'info' : 'warning',
            'id' => $job->getJobId(),
            'uuid' => $job->uuid(),
            'connection' => $job->getConnectionName(),
            'queue' => $job->getQueue(),
            'job' => $job->resolveName(),
            'status' => $status,
            'result' => match (true) {
                $job->isDeleted() => 'deleted',
                $job->isReleased() => 'released',
                $job->hasFailed() => 'failed',
                default => '',
            },
            'attempts' => $job->attempts(),
            'exception' => $exception ? $exception::class : '',
            'message' => $exception?->getMessage(),
            'timestamp' => $this->now()->format('Y-m-d\TH:i:s.uP'),
        ]);

        if ($status === 'starting') {
            $this->latestStartedAt = microtime(true);
        } else {
            $log['duration'] = round(microtime(true) - $this->latestStartedAt, 6);
        }

        $this->output->writeln(json_encode($log));
    }

    /**
     * Get the current date / time.
     *
     * @return DateTime
     */
    protected function now()
    {
        $queueTimezone = $this->laragram['config']->get('queue.output_timezone');
        $defaultTimezone = $this->laragram['config']->get('app.timezone');

        if ($queueTimezone && $queueTimezone !== $defaultTimezone) {
            $dateTime = new DateTime(null, new DateTimeZone($queueTimezone));
            return $dateTime;
        }

        return new DateTime();

    }

    /**
     * Store a failed job event.
     *
     * @param  \LaraGram\Queue\Events\JobFailed  $event
     * @return void
     */
    protected function logFailedJob(JobFailed $event)
    {
        $this->laragram['queue.failer']->log(
            $event->connectionName,
            $event->job->getQueue(),
            $event->job->getRawBody(),
            $event->exception
        );
    }

    /**
     * Get the queue name for the worker.
     *
     * @param  string  $connection
     * @return string
     */
    protected function getQueue($connection)
    {
        return $this->option('queue') ?: $this->laragram['config']->get(
            "queue.connections.{$connection}.queue", 'default'
        );
    }

    /**
     * Determine if the worker should output using JSON.
     *
     * @return bool
     */
    protected function outputUsingJson()
    {
        if (! $this->hasOption('json')) {
            return false;
        }

        return $this->option('json');
    }

    /**
     * Reset static variables.
     *
     * @return void
     */
    public static function flushState()
    {
        static::$hasRegisteredListeners = false;
    }
}
