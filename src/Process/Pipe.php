<?php

namespace LaraGram\Process;

use LaraGram\Support\Collection;
use InvalidArgumentException;
use LaraGram\Support\HigherOrderTapProxy;

/**
 * @mixin \LaraGram\Process\Factory
 * @mixin \LaraGram\Process\PendingProcess
 */
class Pipe
{
    /**
     * The process factory instance.
     *
     * @var \LaraGram\Process\Factory
     */
    protected $factory;

    /**
     * The callback that resolves the pending processes.
     *
     * @var callable
     */
    protected $callback;

    /**
     * The array of pending processes.
     *
     * @var array
     */
    protected $pendingProcesses = [];

    /**
     * Create a new series of piped processes.
     *
     * @param  \LaraGram\Process\Factory  $factory
     * @param  callable  $callback
     * @return void
     */
    public function __construct(Factory $factory, callable $callback)
    {
        $this->factory = $factory;
        $this->callback = $callback;
    }

    /**
     * Add a process to the pipe with a key.
     *
     * @param  string  $key
     * @return \LaraGram\Process\PendingProcess|HigherOrderTapProxy
     */
    public function as(string $key)
    {
        return tap($this->factory->newPendingProcess(), function ($pendingProcess) use ($key) {
            $this->pendingProcesses[$key] = $pendingProcess;
        });
    }

    /**
     * Runs the processes in the pipe.
     *
     * @param  callable|null  $output
     * @return \LaraGram\Contracts\Process\ProcessResult
     */
    public function run(?callable $output = null)
    {
        call_user_func($this->callback, $this);

        return (new Collection($this->pendingProcesses))
                ->reduce(function ($previousProcessResult, $pendingProcess, $key) use ($output) {
                    if (! $pendingProcess instanceof PendingProcess) {
                        throw new InvalidArgumentException('Process pipe must only contain pending processes.');
                    }

                    if ($previousProcessResult && $previousProcessResult->failed()) {
                        return $previousProcessResult;
                    }

                    return $pendingProcess->when(
                        $previousProcessResult,
                        fn () => $pendingProcess->input($previousProcessResult->output())
                    )->run(output: $output ? function ($type, $buffer) use ($key, $output) {
                        $output($type, $buffer, $key);
                    } : null);
                });
    }

    /**
     * Dynamically proxy methods calls to a new pending process.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return \LaraGram\Process\PendingProcess|HigherOrderTapProxy
     */
    public function __call($method, $parameters)
    {
        return tap($this->factory->{$method}(...$parameters), function ($pendingProcess) {
            $this->pendingProcesses[] = $pendingProcess;
        });
    }
}
