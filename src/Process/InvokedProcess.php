<?php

namespace LaraGram\Process;

use LaraGram\Contracts\Process\InvokedProcess as InvokedProcessContract;
use LaraGram\Process\Exceptions\ProcessTimedOutException;
use LaraGram\Console\Process\Exception\ProcessTimedOutException as LaraGramTimeoutException;
use LaraGram\Console\Process\Process;

class InvokedProcess implements InvokedProcessContract
{
    /**
     * The underlying process instance.
     *
     * @var \LaraGram\Console\Process\Process
     */
    protected $process;

    /**
     * Create a new invoked process instance.
     *
     * @param  \LaraGram\Console\Process\Process  $process
     * @return void
     */
    public function __construct(Process $process)
    {
        $this->process = $process;
    }

    /**
     * Get the process ID if the process is still running.
     *
     * @return int|null
     */
    public function id()
    {
        return $this->process->getPid();
    }

    /**
     * Send a signal to the process.
     *
     * @param  int  $signal
     * @return $this
     */
    public function signal(int $signal)
    {
        $this->process->signal($signal);

        return $this;
    }

    /**
     * Stop the process if it is still running.
     *
     * @param  float  $timeout
     * @param  int|null  $signal
     * @return int|null
     */
    public function stop(float $timeout = 10, ?int $signal = null)
    {
        return $this->process->stop($timeout, $signal);
    }

    /**
     * Determine if the process is still running.
     *
     * @return bool
     */
    public function running()
    {
        return $this->process->isRunning();
    }

    /**
     * Get the standard output for the process.
     *
     * @return string
     */
    public function output()
    {
        return $this->process->getOutput();
    }

    /**
     * Get the error output for the process.
     *
     * @return string
     */
    public function errorOutput()
    {
        return $this->process->getErrorOutput();
    }

    /**
     * Get the latest standard output for the process.
     *
     * @return string
     */
    public function latestOutput()
    {
        return $this->process->getIncrementalOutput();
    }

    /**
     * Get the latest error output for the process.
     *
     * @return string
     */
    public function latestErrorOutput()
    {
        return $this->process->getIncrementalErrorOutput();
    }

    /**
     * Wait for the process to finish.
     *
     * @param  callable|null  $output
     * @return \LaraGram\Process\ProcessResult
     *
     * @throws \LaraGram\Process\Exceptions\ProcessTimedOutException
     */
    public function wait(?callable $output = null)
    {
        try {
            $this->process->wait($output);

            return new ProcessResult($this->process);
        } catch (LaraGramTimeoutException $e) {
            throw new ProcessTimedOutException($e, new ProcessResult($this->process));
        }
    }

    /**
     * Wait until the given callback returns true.
     *
     * @param  callable|null  $output
     * @return \LaraGram\Process\ProcessResult
     *
     * @throws \LaraGram\Process\Exceptions\ProcessTimedOutException
     */
    public function waitUntil(?callable $output = null)
    {
        try {
            $this->process->waitUntil($output);

            return new ProcessResult($this->process);
        } catch (LaraGramTimeoutException $e) {
            throw new ProcessTimedOutException($e, new ProcessResult($this->process));
        }
    }
}
