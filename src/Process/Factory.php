<?php

namespace LaraGram\Process;

use Closure;
use LaraGram\Contracts\Process\ProcessResult as ProcessResultContract;
use LaraGram\Support\Collection;
use LaraGram\Support\Traits\Macroable;

class Factory
{
    use Macroable {
        __call as macroCall;
    }

    /**
     * Indicates if the process factory has faked process handlers.
     *
     * @var bool
     */
    protected $recording = false;

    /**
     * All of the recorded processes.
     *
     * @var array
     */
    protected $recorded = [];

    /**
     * The registered fake handler callbacks.
     *
     * @var array
     */
    protected $fakeHandlers = [];

    /**
     * Indicates that an exception should be thrown if any process is not faked.
     *
     * @var bool
     */
    protected $preventStrayProcesses = false;

    /**
     * Create a new fake process response for testing purposes.
     *
     * @param  array|string  $output
     * @param  array|string  $errorOutput
     * @param  int  $exitCode
     * @return \LaraGram\Process\FakeProcessResult
     */
    public function result(array|string $output = '', array|string $errorOutput = '', int $exitCode = 0)
    {
        return new FakeProcessResult(
            output: $output,
            errorOutput: $errorOutput,
            exitCode: $exitCode,
        );
    }

    /**
     * Begin describing a fake process lifecycle.
     *
     * @return \LaraGram\Process\FakeProcessDescription
     */
    public function describe()
    {
        return new FakeProcessDescription;
    }

    /**
     * Begin describing a fake process sequence.
     *
     * @param  array  $processes
     * @return \LaraGram\Process\FakeProcessSequence
     */
    public function sequence(array $processes = [])
    {
        return new FakeProcessSequence($processes);
    }

    /**
     * Indicate that the process factory should fake processes.
     *
     * @param  \Closure|array|null  $callback
     * @return $this
     */
    public function fake(Closure|array|null $callback = null)
    {
        $this->recording = true;

        if (is_null($callback)) {
            $this->fakeHandlers = ['*' => fn () => new FakeProcessResult];

            return $this;
        }

        if ($callback instanceof Closure) {
            $this->fakeHandlers = ['*' => $callback];

            return $this;
        }

        foreach ($callback as $command => $handler) {
            $this->fakeHandlers[is_numeric($command) ? '*' : $command] = $handler instanceof Closure
                    ? $handler
                    : fn () => $handler;
        }

        return $this;
    }

    /**
     * Determine if the process factory has fake process handlers and is recording processes.
     *
     * @return bool
     */
    public function isRecording()
    {
        return $this->recording;
    }

    /**
     * Record the given process if processes should be recorded.
     *
     * @param  \LaraGram\Process\PendingProcess  $process
     * @param  \LaraGram\Contracts\Process\ProcessResult  $result
     * @return $this
     */
    public function recordIfRecording(PendingProcess $process, ProcessResultContract $result)
    {
        if ($this->isRecording()) {
            $this->record($process, $result);
        }

        return $this;
    }

    /**
     * Record the given process.
     *
     * @param  \LaraGram\Process\PendingProcess  $process
     * @param  \LaraGram\Contracts\Process\ProcessResult  $result
     * @return $this
     */
    public function record(PendingProcess $process, ProcessResultContract $result)
    {
        $this->recorded[] = [$process, $result];

        return $this;
    }

    /**
     * Indicate that an exception should be thrown if any process is not faked.
     *
     * @param  bool  $prevent
     * @return $this
     */
    public function preventStrayProcesses(bool $prevent = true)
    {
        $this->preventStrayProcesses = $prevent;

        return $this;
    }

    /**
     * Determine if stray processes are being prevented.
     *
     * @return bool
     */
    public function preventingStrayProcesses()
    {
        return $this->preventStrayProcesses;
    }

    /**
     * Start defining a pool of processes.
     *
     * @param  callable  $callback
     * @return \LaraGram\Process\Pool
     */
    public function pool(callable $callback)
    {
        return new Pool($this, $callback);
    }

    /**
     * Start defining a series of piped processes.
     *
     * @param  callable|array  $callback
     * @return \LaraGram\Contracts\Process\ProcessResult
     */
    public function pipe(callable|array $callback, ?callable $output = null)
    {
        return is_array($callback)
            ? (new Pipe($this, fn ($pipe) => (new Collection($callback))->each(
                fn ($command) => $pipe->command($command)
            )))->run(output: $output)
            : (new Pipe($this, $callback))->run(output: $output);
    }

    /**
     * Run a pool of processes and wait for them to finish executing.
     *
     * @param  callable  $callback
     * @param  callable|null  $output
     * @return \LaraGram\Process\ProcessPoolResults
     */
    public function concurrently(callable $callback, ?callable $output = null)
    {
        return (new Pool($this, $callback))->start($output)->wait();
    }

    /**
     * Create a new pending process associated with this factory.
     *
     * @return \LaraGram\Process\PendingProcess
     */
    public function newPendingProcess()
    {
        return (new PendingProcess($this))->withFakeHandlers($this->fakeHandlers);
    }

    /**
     * Dynamically proxy methods to a new pending process instance.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $parameters);
        }

        return $this->newPendingProcess()->{$method}(...$parameters);
    }
}
