<?php

namespace LaraGram\Support\Facades;

use Closure;
use LaraGram\Process\Factory;

/**
 * @method static \LaraGram\Process\PendingProcess command(array|string $command)
 * @method static \LaraGram\Process\PendingProcess path(string $path)
 * @method static \LaraGram\Process\PendingProcess timeout(int $timeout)
 * @method static \LaraGram\Process\PendingProcess idleTimeout(int $timeout)
 * @method static \LaraGram\Process\PendingProcess forever()
 * @method static \LaraGram\Process\PendingProcess env(array $environment)
 * @method static \LaraGram\Process\PendingProcess input(\Traversable|resource|string|int|float|bool|null $input)
 * @method static \LaraGram\Process\PendingProcess quietly()
 * @method static \LaraGram\Process\PendingProcess tty(bool $tty = true)
 * @method static \LaraGram\Process\PendingProcess options(array $options)
 * @method static \LaraGram\Contracts\Process\ProcessResult run(array|string|null $command = null, callable|null $output = null)
 * @method static \LaraGram\Process\InvokedProcess start(array|string|null $command = null, callable|null $output = null)
 * @method static \LaraGram\Process\PendingProcess withFakeHandlers(array $fakeHandlers)
 * @method static \LaraGram\Process\PendingProcess|mixed when(\Closure|mixed|null $value = null, callable|null $callback = null, callable|null $default = null)
 * @method static \LaraGram\Process\PendingProcess|mixed unless(\Closure|mixed|null $value = null, callable|null $callback = null, callable|null $default = null)
 * @method static \LaraGram\Process\FakeProcessResult result(array|string $output = '', array|string $errorOutput = '', int $exitCode = 0)
 * @method static \LaraGram\Process\FakeProcessDescription describe()
 * @method static \LaraGram\Process\FakeProcessSequence sequence(array $processes = [])
 * @method static bool isRecording()
 * @method static \LaraGram\Process\Factory recordIfRecording(\LaraGram\Process\PendingProcess $process, \LaraGram\Contracts\Process\ProcessResult $result)
 * @method static \LaraGram\Process\Factory record(\LaraGram\Process\PendingProcess $process, \LaraGram\Contracts\Process\ProcessResult $result)
 * @method static \LaraGram\Process\Factory preventStrayProcesses(bool $prevent = true)
 * @method static bool preventingStrayProcesses()
 * @method static \LaraGram\Process\Factory assertRan(\Closure|string $callback)
 * @method static \LaraGram\Process\Factory assertRanTimes(\Closure|string $callback, int $times = 1)
 * @method static \LaraGram\Process\Factory assertNotRan(\Closure|string $callback)
 * @method static \LaraGram\Process\Factory assertDidntRun(\Closure|string $callback)
 * @method static \LaraGram\Process\Factory assertNothingRan()
 * @method static \LaraGram\Process\Pool pool(callable $callback)
 * @method static \LaraGram\Contracts\Process\ProcessResult pipe(callable|array $callback, callable|null $output = null)
 * @method static \LaraGram\Process\ProcessPoolResults concurrently(callable $callback, callable|null $output = null)
 * @method static \LaraGram\Process\PendingProcess newPendingProcess()
 * @method static void macro(string $name, object|callable $macro)
 * @method static void mixin(object $mixin, bool $replace = true)
 * @method static bool hasMacro(string $name)
 * @method static void flushMacros()
 * @method static mixed macroCall(string $method, array $parameters)
 *
 * @see \LaraGram\Process\PendingProcess
 * @see \LaraGram\Process\Factory
 */
class Process extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return Factory::class;
    }

    /**
     * Indicate that the process factory should fake processes.
     *
     * @param  \Closure|array|null  $callback
     * @return \LaraGram\Process\Factory
     */
    public static function fake(Closure|array|null $callback = null)
    {
        return tap(static::getFacadeRoot(), function ($fake) use ($callback) {
            static::swap($fake->fake($callback));
        });
    }
}
