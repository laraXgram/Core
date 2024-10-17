<?php

namespace LaraGram\Support\Facades;

use LaraGram\Console\Output;

/**
 * @method static Output output()
 * @method static void run(string $command, array $args = [])
 * @method static void starting(callable $callback)
 * @method static void macro(string $name, callable $macro)
 * @method static bool hasMacro(string $name)
 */
class Console extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'console';
    }
}