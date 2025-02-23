<?php

namespace LaraGram\Console\Scheduling;

use Closure;
use DateTime;
use DateTimeZone;
use LaraGram\Console\Application;
use LaraGram\Contracts\Container\Container;
use LaraGram\Contracts\Debug\ExceptionHandler;
use LaraGram\Support\Arr;
use LaraGram\Support\Stringable;
use LaraGram\Support\Traits\Macroable;
use LaraGram\Support\Traits\ReflectsClosures;
use LaraGram\Support\Traits\Tappable;
use LaraGram\Console\Process\Process;
use Throwable;

class Event
{
    use Macroable, ManagesAttributes, ManagesFrequencies, ReflectsClosures, Tappable;

    /**
     * The command string.
     *
     * @var string|null
     */
    public $command;

    /**
     * The location that output should be sent to.
     *
     * @var string
     */
    public $output = '/dev/null';

    /**
     * Indicates whether output should be appended.
     *
     * @var bool
     */
    public $shouldAppendOutput = false;

    /**
     * The array of callbacks to be run before the event is started.
     *
     * @var array
     */
    protected $beforeCallbacks = [];

    /**
     * The array of callbacks to be run after the event is finished.
     *
     * @var array
     */
    protected $afterCallbacks = [];

    /**
     * The event mutex implementation.
     *
     * @var \LaraGram\Console\Scheduling\EventMutex
     */
    public $mutex;

    /**
     * The mutex name resolver callback.
     *
     * @var \Closure|null
     */
    public $mutexNameResolver;

    /**
     * The last time the event was checked for eligibility to run.
     *
     * Utilized by sub-minute repeated events.
     *
     * @var \DateTimeInterface|null
     */
    protected $lastChecked;

    /**
     * The exit status code of the command.
     *
     * @var int|null
     */
    public $exitCode;

    /**
     * Create a new event instance.
     *
     * @param  \LaraGram\Console\Scheduling\EventMutex  $mutex
     * @param  string  $command
     * @param  \DateTimeZone|string|null  $timezone
     * @return void
     */
    public function __construct(EventMutex $mutex, $command, $timezone = null)
    {
        $this->mutex = $mutex;
        $this->command = $command;
        $this->timezone = $timezone;

        $this->output = $this->getDefaultOutput();
    }

    /**
     * Get the default output depending on the OS.
     *
     * @return string
     */
    public function getDefaultOutput()
    {
        return (DIRECTORY_SEPARATOR === '\\') ? 'NUL' : '/dev/null';
    }

    /**
     * Run the given event.
     *
     * @param  \LaraGram\Contracts\Container\Container  $container
     * @return void
     *
     * @throws \Throwable
     */
    public function run(Container $container)
    {
        if ($this->shouldSkipDueToOverlapping()) {
            return;
        }

        $exitCode = $this->start($container);

        if (! $this->runInBackground) {
            $this->finish($container, $exitCode);
        }
    }

    /**
     * Determine if the event should skip because another process is overlapping.
     *
     * @return bool
     */
    public function shouldSkipDueToOverlapping()
    {
        return $this->withoutOverlapping && ! $this->mutex->create($this);
    }

    /**
     * Determine if the event has been configured to repeat multiple times per minute.
     *
     * @return bool
     */
    public function isRepeatable()
    {
        return ! is_null($this->repeatSeconds);
    }

    /**
     * Determine if the event is ready to repeat.
     *
     * @return bool
     */
    public function shouldRepeatNow()
    {
        return $this->isRepeatable()
            && $this->lastChecked !== null
            && (time() - strtotime($this->lastChecked)) >= $this->repeatSeconds;
    }

    /**
     * Run the command process.
     *
     * @param  \LaraGram\Contracts\Container\Container  $container
     * @return int
     *
     * @throws \Throwable
     */
    protected function start($container)
    {
        try {
            $this->callBeforeCallbacks($container);

            return $this->execute($container);
        } catch (Throwable $exception) {
            $this->removeMutex();

            throw $exception;
        }
    }

    /**
     * Run the command process.
     *
     * @param  \LaraGram\Contracts\Container\Container  $container
     * @return int
     */
    protected function execute($container)
    {
        return Process::fromShellCommandline(
            $this->buildCommand(), app()->basePath(), null, null, null
        )->run(fn () => true);
    }

    /**
     * Mark the command process as finished and run callbacks/cleanup.
     *
     * @param  \LaraGram\Contracts\Container\Container  $container
     * @param  int  $exitCode
     * @return void
     */
    public function finish(Container $container, $exitCode)
    {
        $this->exitCode = (int) $exitCode;

        try {
            $this->callAfterCallbacks($container);
        } finally {
            $this->removeMutex();
        }
    }

    /**
     * Call all of the "before" callbacks for the event.
     *
     * @param  \LaraGram\Contracts\Container\Container  $container
     * @return void
     */
    public function callBeforeCallbacks(Container $container)
    {
        foreach ($this->beforeCallbacks as $callback) {
            $container->call($callback);
        }
    }

    /**
     * Call all of the "after" callbacks for the event.
     *
     * @param  \LaraGram\Contracts\Container\Container  $container
     * @return void
     */
    public function callAfterCallbacks(Container $container)
    {
        foreach ($this->afterCallbacks as $callback) {
            $container->call($callback);
        }
    }

    /**
     * Build the command string.
     *
     * @return string
     */
    public function buildCommand()
    {
        return (new CommandBuilder)->buildCommand($this);
    }

    /**
     * Determine if the given event should run based on the Cron expression.
     *
     * @param  \LaraGram\Contracts\Foundation\Application  $app
     * @return bool
     */
    public function isDue($app)
    {
        return $this->expressionPasses();
    }

    /**
     * Determine if the Cron expression passes.
     *
     * @return bool
     */
    public function expressionPasses()
    {
        $date = new DateTime();

        if ($this->timezone) {
            $timezone = new DateTimeZone($this->timezone);
            $date->setTimezone($timezone);
        }

        return $this->checkCronExpression($this->expression, $date);
    }

    protected function checkCronExpression($expression, $date)
    {
        list($minute, $hour, $day, $month, $weekday) = explode(' ', $expression);

        if (!$this->matchCronField($minute, $date->format('i'))) {
            return false;
        }

        if (!$this->matchCronField($hour, $date->format('H'))) {
            return false;
        }

        if (!$this->matchCronField($day, $date->format('d'))) {
            return false;
        }

        if (!$this->matchCronField($month, $date->format('m'))) {
            return false;
        }

        if (!$this->matchCronField($weekday, $date->format('w'))) {
            return false;
        }

        return true;
    }

    protected function matchCronField($field, $value)
    {
        if ($field === '*' || $field == $value) {
            return true;
        }

        $ranges = explode(',', $field);
        foreach ($ranges as $range) {
            if (strpos($range, '-') !== false) {
                list($start, $end) = explode('-', $range);
                if ($value >= $start && $value <= $end) {
                    return true;
                }
            } elseif ($range == $value) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if the event runs in the given environment.
     *
     * @param  string  $environment
     * @return bool
     */
    public function runsInEnvironment($environment)
    {
        return empty($this->environments) || in_array($environment, $this->environments);
    }

    /**
     * Determine if the filters pass for the event.
     *
     * @param  \LaraGram\Contracts\Foundation\Application  $app
     * @return bool
     */
    public function filtersPass($app)
    {
        $this->lastChecked = new DateTime();

        foreach ($this->filters as $callback) {
            if (! $app->call($callback)) {
                return false;
            }
        }

        foreach ($this->rejects as $callback) {
            if ($app->call($callback)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Ensure that the output is stored on disk in a log file.
     *
     * @return $this
     */
    public function storeOutput()
    {
        $this->ensureOutputIsBeingCaptured();

        return $this;
    }

    /**
     * Send the output of the command to a given location.
     *
     * @param  string  $location
     * @param  bool  $append
     * @return $this
     */
    public function sendOutputTo($location, $append = false)
    {
        $this->output = $location;

        $this->shouldAppendOutput = $append;

        return $this;
    }

    /**
     * Append the output of the command to a given location.
     *
     * @param  string  $location
     * @return $this
     */
    public function appendOutputTo($location)
    {
        return $this->sendOutputTo($location, true);
    }

    /**
     * Register a callback to ping a given URL before the job runs.
     *
     * @param  string  $url
     * @return $this
     */
    public function pingBefore($url)
    {
        return $this->before($this->pingCallback($url));
    }

    /**
     * Register a callback to ping a given URL before the job runs if the given condition is true.
     *
     * @param  bool  $value
     * @param  string  $url
     * @return $this
     */
    public function pingBeforeIf($value, $url)
    {
        return $value ? $this->pingBefore($url) : $this;
    }

    /**
     * Register a callback to ping a given URL after the job runs.
     *
     * @param  string  $url
     * @return $this
     */
    public function thenPing($url)
    {
        return $this->then($this->pingCallback($url));
    }

    /**
     * Register a callback to ping a given URL after the job runs if the given condition is true.
     *
     * @param  bool  $value
     * @param  string  $url
     * @return $this
     */
    public function thenPingIf($value, $url)
    {
        return $value ? $this->thenPing($url) : $this;
    }

    /**
     * Register a callback to ping a given URL if the operation succeeds.
     *
     * @param  string  $url
     * @return $this
     */
    public function pingOnSuccess($url)
    {
        return $this->onSuccess($this->pingCallback($url));
    }

    /**
     * Register a callback to ping a given URL if the operation succeeds and if the given condition is true.
     *
     * @param  bool  $value
     * @param  string  $url
     * @return $this
     */
    public function pingOnSuccessIf($value, $url)
    {
        return $value ? $this->onSuccess($this->pingCallback($url)) : $this;
    }

    /**
     * Register a callback to ping a given URL if the operation fails.
     *
     * @param  string  $url
     * @return $this
     */
    public function pingOnFailure($url)
    {
        return $this->onFailure($this->pingCallback($url));
    }

    /**
     * Register a callback to ping a given URL if the operation fails and if the given condition is true.
     *
     * @param  bool  $value
     * @param  string  $url
     * @return $this
     */
    public function pingOnFailureIf($value, $url)
    {
        return $value ? $this->onFailure($this->pingCallback($url)) : $this;
    }

    /**
     * Get the callback that pings the given URL.
     *
     * @param  string  $url
     * @return \Closure
     */
    protected function pingCallback($url)
    {
        return function (Container $container) use ($url) {
            try {
                $this->getHttpClient($container)->request('GET', $url);
            } catch (\Exception $e) {
                $container->make(ExceptionHandler::class)->report($e);

            }
        };
    }

    /**
     * Register a callback to be called before the operation.
     *
     * @param  \Closure  $callback
     * @return $this
     */
    public function before(Closure $callback)
    {
        $this->beforeCallbacks[] = $callback;

        return $this;
    }

    /**
     * Register a callback to be called after the operation.
     *
     * @param  \Closure  $callback
     * @return $this
     */
    public function after(Closure $callback)
    {
        return $this->then($callback);
    }

    /**
     * Register a callback to be called after the operation.
     *
     * @param  \Closure  $callback
     * @return $this
     */
    public function then(Closure $callback)
    {
        $parameters = $this->closureParameterTypes($callback);

        if (Arr::get($parameters, 'output') === Stringable::class) {
            return $this->thenWithOutput($callback);
        }

        $this->afterCallbacks[] = $callback;

        return $this;
    }

    /**
     * Register a callback that uses the output after the job runs.
     *
     * @param  \Closure  $callback
     * @param  bool  $onlyIfOutputExists
     * @return $this
     */
    public function thenWithOutput(Closure $callback, $onlyIfOutputExists = false)
    {
        $this->ensureOutputIsBeingCaptured();

        return $this->then($this->withOutputCallback($callback, $onlyIfOutputExists));
    }

    /**
     * Register a callback to be called if the operation succeeds.
     *
     * @param  \Closure  $callback
     * @return $this
     */
    public function onSuccess(Closure $callback)
    {
        $parameters = $this->closureParameterTypes($callback);

        if (Arr::get($parameters, 'output') === Stringable::class) {
            return $this->onSuccessWithOutput($callback);
        }

        return $this->then(function (Container $container) use ($callback) {
            if ($this->exitCode === 0) {
                $container->call($callback);
            }
        });
    }

    /**
     * Register a callback that uses the output if the operation succeeds.
     *
     * @param  \Closure  $callback
     * @param  bool  $onlyIfOutputExists
     * @return $this
     */
    public function onSuccessWithOutput(Closure $callback, $onlyIfOutputExists = false)
    {
        $this->ensureOutputIsBeingCaptured();

        return $this->onSuccess($this->withOutputCallback($callback, $onlyIfOutputExists));
    }

    /**
     * Register a callback to be called if the operation fails.
     *
     * @param  \Closure  $callback
     * @return $this
     */
    public function onFailure(Closure $callback)
    {
        $parameters = $this->closureParameterTypes($callback);

        if (Arr::get($parameters, 'output') === Stringable::class) {
            return $this->onFailureWithOutput($callback);
        }

        return $this->then(function (Container $container) use ($callback) {
            if ($this->exitCode !== 0) {
                $container->call($callback);
            }
        });
    }

    /**
     * Register a callback that uses the output if the operation fails.
     *
     * @param  \Closure  $callback
     * @param  bool  $onlyIfOutputExists
     * @return $this
     */
    public function onFailureWithOutput(Closure $callback, $onlyIfOutputExists = false)
    {
        $this->ensureOutputIsBeingCaptured();

        return $this->onFailure($this->withOutputCallback($callback, $onlyIfOutputExists));
    }

    /**
     * Get a callback that provides output.
     *
     * @param  \Closure  $callback
     * @param  bool  $onlyIfOutputExists
     * @return \Closure
     */
    protected function withOutputCallback(Closure $callback, $onlyIfOutputExists = false)
    {
        return function (Container $container) use ($callback, $onlyIfOutputExists) {
            $output = $this->output && is_file($this->output) ? file_get_contents($this->output) : '';

            return $onlyIfOutputExists && empty($output)
                            ? null
                            : $container->call($callback, ['output' => new Stringable($output)]);
        };
    }

    /**
     * Get the summary of the event for display.
     *
     * @return string
     */
    public function getSummaryForDisplay()
    {
        if (is_string($this->description)) {
            return $this->description;
        }

        return $this->buildCommand();
    }

    /**
     * Determine the next due date for an event.
     *
     * @param  \DateTimeInterface|string  $currentTime
     * @param  int  $nth
     * @param  bool  $allowCurrentDate
     * @return DateTime
     */
    public function nextRunDate($currentTime = 'now', $nth = 0, $allowCurrentDate = false)
    {
        $currentDate = new DateTime($currentTime);

        $expression = $this->getExpression();

        $parts = explode(' ', $expression);

        $minute = $parts[0];
        $hour = $parts[1];
        $day = $parts[2];
        $month = $parts[3];
        $weekday = $parts[4];

        $nextRunDate = clone $currentDate;

        if ($minute !== '*' && $minute >= 0 && $minute < 60) {
            $nextRunDate->setTime($hour, $minute);
        }

        if ($day !== '*' && $day >= 1 && $day <= 31) {
            $nextRunDate->setDate($nextRunDate->format('Y'), $nextRunDate->format('m'), $day);
        }

        if ($month !== '*' && $month >= 1 && $month <= 12) {
            $nextRunDate->setDate($nextRunDate->format('Y'), $month, $nextRunDate->format('d'));
        }

        if ($weekday !== '*' && $weekday >= 0 && $weekday <= 6) {
            $nextRunDate->modify('next Sunday');
        }

        if ($allowCurrentDate && $nextRunDate <= $currentDate) {
            return $currentDate;
        }

        return $nextRunDate;
    }


    /**
     * Get the Cron expression for the event.
     *
     * @return string
     */
    public function getExpression()
    {
        return $this->expression;
    }

    /**
     * Set the event mutex implementation to be used.
     *
     * @param  \LaraGram\Console\Scheduling\EventMutex  $mutex
     * @return $this
     */
    public function preventOverlapsUsing(EventMutex $mutex)
    {
        $this->mutex = $mutex;

        return $this;
    }

    /**
     * Get the mutex name for the scheduled command.
     *
     * @return string
     */
    public function mutexName()
    {
        $mutexNameResolver = $this->mutexNameResolver;

        if (! is_null($mutexNameResolver) && is_callable($mutexNameResolver)) {
            return $mutexNameResolver($this);
        }

        return 'framework'.DIRECTORY_SEPARATOR.'schedule-'.
            sha1($this->expression.$this->normalizeCommand($this->command ?? ''));
    }

    /**
     * Set the mutex name or name resolver callback.
     *
     * @param  \Closure|string  $mutexName
     * @return $this
     */
    public function createMutexNameUsing(Closure|string $mutexName)
    {
        $this->mutexNameResolver = is_string($mutexName) ? fn () => $mutexName : $mutexName;

        return $this;
    }

    /**
     * Delete the mutex for the event.
     *
     * @return void
     */
    protected function removeMutex()
    {
        if ($this->withoutOverlapping) {
            $this->mutex->forget($this);
        }
    }

    /**
     * Format the given command string with a normalized PHP binary path.
     *
     * @param  string  $command
     * @return string
     */
    public static function normalizeCommand($command)
    {
        return str_replace([
            Application::phpBinary(),
            Application::commanderBinary(),
        ], [
            'php',
            preg_replace("#['\"]#", '', Application::commanderBinary()),
        ], $command);
    }
}
