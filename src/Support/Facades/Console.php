<?php

namespace LaraGram\Support\Facades;

use LaraGram\Console\Output;

/**
 * @method static Output output()
 * @method static void run(string $command, array $args = [])
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