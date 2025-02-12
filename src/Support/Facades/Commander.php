<?php

namespace LaraGram\Support\Facades;

use LaraGram\Contracts\Console\Kernel as ConsoleKernelContract;

/**
 * @method static int handle(\LaraGram\Console\Input\InputInterface $input, \LaraGram\Console\Output\OutputInterface|null $output = null)
 * @method static void terminate(\LaraGram\Console\Input\InputInterface $input, int $status)
 * @method static void whenCommandLifecycleIsLongerThan(\DateTimeInterface|float|int $threshold, callable $handler)
 * @method static \DateTimeInterface|null commandStartedAt()
 * @method static \LaraGram\Foundation\Console\ClosureCommand command(string $signature, \Closure $callback)
 * @method static void registerCommand(\LaraGram\Console\Command\Command $command)
 * @method static int call(string $command, array $parameters = [], \LaraGram\Console\Output\OutputInterface|null $outputBuffer = null)
 * @method static array all()
 * @method static string output()
 * @method static void bootstrap()
 * @method static void bootstrapWithoutBootingProviders()
 * @method static void setCommander(\LaraGram\Console\Application|null $artisan)
 * @method static \LaraGram\Foundation\Console\Kernel addCommands(array $commands)
 * @method static \LaraGram\Foundation\Console\Kernel addCommandPaths(array $paths)
 *
 * @see \LaraGram\Foundation\Console\Kernel
 */
class Commander extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return ConsoleKernelContract::class;
    }
}
