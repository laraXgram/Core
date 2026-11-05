<?php

namespace LaraGram\Foundation\Console\Dev;

use Closure;
use LaraGram\Console\Process\ExecutableFinder;
use LaraGram\Console\Process\Process;
use Throwable;

class Supervisor
{
    /**
     * How many times a crashed command is started again.
     */
    public const MAX_RESTARTS = 5;

    /**
     * The seconds to wait before starting a crashed command again.
     */
    protected const RESTART_DELAY = 1.0;

    /**
     * The seconds a command has to run for a crash to be worth retrying.
     *
     * A command that dies faster never got off the ground: a typo, a port in
     * use. Trying again only scrolls the real error out of view.
     */
    protected const MIN_UPTIME = 1.0;

    /**
     * The seconds a command gets to act on SIGTERM before it is killed.
     */
    protected const TERMINATE_GRACE = 2.0;

    /**
     * The running state of each command.
     *
     * @var array<int, array{process: ?Process, startedAt: float, restartAt: ?float, restarts: int, stdout: string, stderr: string}>
     */
    protected array $states = [];

    /**
     * The command line that puts a command in its own process group.
     *
     * @var list<string>|null
     */
    protected ?array $groupPrefix = null;

    /**
     * Create a new supervisor instance.
     *
     * @param  list<array{name: string, command: string, color?: string}>  $commands
     * @param  \Closure(array): void  $listener  Receives every event of every command.
     */
    public function __construct(
        protected array $commands,
        protected string $cwd,
        protected bool $autoRestart,
        protected Closure $listener,
        protected array $env = [],
    ) {
        foreach ($commands as $index => $command) {
            $this->states[$index] = ['process' => null, 'startedAt' => 0.0, 'restartAt' => null, 'restarts' => 0, 'stdout' => '', 'stderr' => ''];
        }

        $this->groupPrefix = $this->findGroupPrefix();
    }

    /**
     * Start every command.
     */
    public function start(): void
    {
        foreach (array_keys($this->commands) as $index) {
            $this->spawn($index);
        }
    }

    /**
     * Read what the commands printed and act on the ones that ended.
     */
    public function tick(): void
    {
        $now = microtime(true);

        foreach ($this->states as $index => &$state) {
            if ($state['restartAt'] !== null && $now >= $state['restartAt']) {
                $state['restartAt'] = null;
                unset($state);
                $this->spawn($index);
                $state = &$this->states[$index];

                continue;
            }

            if ($state['process'] === null) {
                continue;
            }

            $this->read($index);

            if (! $state['process']->isRunning()) {
                unset($state);
                $this->handleExit($index);
                $state = &$this->states[$index];
            }
        }
    }

    /**
     * Start a command again by hand, whatever state it is in.
     */
    public function restart(int $index): void
    {
        $state = &$this->states[$index];

        $state['restartAt'] = null;
        $state['restarts'] = 0;

        if ($state['process']?->isRunning()) {
            $this->signal($state['process'], 'KILL');

            while ($state['process']->isRunning()) {
                usleep(10_000);
            }
        }

        $state['process'] = null;
        $state['stdout'] = '';
        $state['stderr'] = '';

        unset($state);

        $this->spawn($index);
    }

    /**
     * Determine if every command has stopped for good.
     */
    public function isSettled(): bool
    {
        foreach ($this->states as $state) {
            if ($state['process'] !== null || $state['restartAt'] !== null) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine the status of a command: running, restarting or stopped.
     */
    public function status(int $index): string
    {
        return match (true) {
            $this->states[$index]['restartAt'] !== null => 'restarting',
            $this->states[$index]['process'] !== null => 'running',
            default => 'stopped',
        };
    }

    /**
     * Stop every command, asking first and insisting after the grace period.
     */
    public function terminate(): void
    {
        foreach ($this->states as &$state) {
            $state['restartAt'] = null;

            if ($state['process']?->isRunning()) {
                $this->signal($state['process'], 'TERM');
            }
        }

        unset($state);

        $deadline = microtime(true) + self::TERMINATE_GRACE;

        while (microtime(true) < $deadline && $this->anyAlive()) {
            $this->drain();

            usleep(25_000);
        }

        foreach ($this->states as $state) {
            if ($state['process'] !== null) {
                $this->signal($state['process'], 'KILL');
            }
        }

        $this->drain();

        foreach ($this->states as &$state) {
            $state['process'] = null;
        }
    }

    /**
     * Start a command.
     */
    protected function spawn(int $index): void
    {
        $command = $this->commands[$index]['command'];
        $state = &$this->states[$index];

        try {
            $process = PHP_OS_FAMILY === 'Windows'
                ? Process::fromShellCommandline($command, $this->cwd, $this->env, null, null)
                : new Process([...($this->groupPrefix ?? []), 'sh', '-c', $command], $this->cwd, $this->env, null, null);

            if (PHP_OS_FAMILY === 'Windows') {
                $process->setOptions(['create_process_group' => true]);
            }

            $process->start();
        } catch (Throwable $e) {
            $state['process'] = null;

            $this->emit(['type' => 'error', 'index' => $index, 'message' => $e->getMessage()]);
            $this->emit(['type' => 'failed', 'index' => $index, 'code' => null, 'reason' => 'spawn-error']);

            return;
        }

        $state['process'] = $process;
        $state['startedAt'] = microtime(true);

        $this->emit(['type' => 'start', 'index' => $index, 'pid' => $process->getPid()]);
    }

    /**
     * Pass on the complete lines a command printed.
     */
    protected function read(int $index): void
    {
        $state = &$this->states[$index];
        $process = $state['process'];

        foreach (['stdout' => $process->getIncrementalOutput(), 'stderr' => $process->getIncrementalErrorOutput()] as $stream => $chunk) {
            if ($chunk === '') {
                continue;
            }

            $lines = explode("\n", $state[$stream].str_replace("\r", '', $chunk));

            $state[$stream] = array_pop($lines);

            foreach ($lines as $line) {
                $this->emit(['type' => 'output', 'index' => $index, 'stream' => $stream, 'text' => $line]);
            }
        }

        // The process keeps everything it read; only the partial lines above matter.
        $process->clearOutput();
        $process->clearErrorOutput();
    }

    /**
     * Report that a command ended and decide if it runs again.
     */
    protected function handleExit(int $index): void
    {
        $this->read($index);

        $state = &$this->states[$index];
        $process = $state['process'];

        foreach (['stdout', 'stderr'] as $stream) {
            if (trim($state[$stream]) !== '') {
                $this->emit(['type' => 'output', 'index' => $index, 'stream' => $stream, 'text' => $state[$stream]]);
            }

            $state[$stream] = '';
        }

        $code = $process->getExitCode();
        $signal = $process->hasBeenSignaled() ? $process->getTermSignal() : null;
        $uptime = microtime(true) - $state['startedAt'];

        $state['process'] = null;

        $this->emit(['type' => 'exit', 'index' => $index, 'code' => $code, 'signal' => $signal]);

        if ($code === 0 || $signal !== null) {
            $state['restarts'] = 0;

            return;
        }

        $state['restarts']++;

        $reason = match (true) {
            ! $this->autoRestart => 'restart-disabled',
            $uptime < self::MIN_UPTIME => 'crashed-immediately',
            $state['restarts'] > self::MAX_RESTARTS => 'attempts-exhausted',
            default => null,
        };

        if ($reason !== null) {
            $this->emit(['type' => 'failed', 'index' => $index, 'code' => $code, 'reason' => $reason]);

            return;
        }

        $state['restartAt'] = microtime(true) + self::RESTART_DELAY;

        $this->emit(['type' => 'restarting', 'index' => $index, 'attempt' => $state['restarts'], 'max' => self::MAX_RESTARTS]);
    }

    /**
     * Determine if any command is still running.
     */
    protected function anyAlive(): bool
    {
        foreach ($this->states as $state) {
            if ($state['process']?->isRunning()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Pass on what the commands printed while they stop.
     */
    protected function drain(): void
    {
        foreach ($this->states as $index => $state) {
            if ($state['process'] !== null) {
                $this->read($index);
            }
        }
    }

    /**
     * Send a signal to a command and everything it started.
     */
    protected function signal(Process $process, string $signal): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $process->stop(0);

            return;
        }

        if (($pid = $process->getPid()) === null) {
            return;
        }

        $target = $this->groupPrefix === null ? (string) $pid : '-'.$pid;

        // The command is usually a wrapper; the group holds what it started.
        @exec(sprintf('kill -s %s -- %s 2>/dev/null', $signal, $target));
    }

    /**
     * Find the command that runs another one in its own process group.
     *
     * @return list<string>|null
     */
    protected function findGroupPrefix(): ?array
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return null;
        }

        $finder = new ExecutableFinder;

        if ($setsid = $finder->find('setsid')) {
            return [$setsid];
        }

        if ($perl = $finder->find('perl')) {
            return [$perl, '-e', 'use POSIX; POSIX::setsid(); exec(@ARGV) or exit 127;'];
        }

        return null;
    }

    /**
     * Send an event to the listener.
     */
    protected function emit(array $event): void
    {
        ($this->listener)($event);
    }
}
