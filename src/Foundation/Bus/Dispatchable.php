<?php

namespace LaraGram\Foundation\Bus;

use Closure;
use LaraGram\Contracts\Bus\Dispatcher;
use LaraGram\Support\Fluent;

trait Dispatchable
{
    public static function dispatch(...$arguments)
    {
        return new PendingDispatch(new static(...$arguments));
    }

    public static function dispatchIf($boolean, ...$arguments)
    {
        if ($boolean instanceof Closure) {
            $dispatchable = new static(...$arguments);

            return value($boolean, $dispatchable)
                ? new PendingDispatch($dispatchable)
                : new Fluent;
        }

        return value($boolean)
            ? new PendingDispatch(new static(...$arguments))
            : new Fluent;
    }

    public static function dispatchUnless($boolean, ...$arguments)
    {
        if ($boolean instanceof Closure) {
            $dispatchable = new static(...$arguments);

            return ! value($boolean, $dispatchable)
                ? new PendingDispatch($dispatchable)
                : new Fluent;
        }

        return ! value($boolean)
            ? new PendingDispatch(new static(...$arguments))
            : new Fluent;
    }

    /**
     * Dispatch a command to its appropriate handler in the current process.
     *
     * Queueable jobs will be dispatched to the "sync" queue.
     *
     * @param  mixed  ...$arguments
     * @return mixed
     */
    public static function dispatchSync(...$arguments)
    {
        return app(Dispatcher::class)->dispatchSync(new static(...$arguments));
    }

    /**
     * Dispatch a command to its appropriate handler after the current process.
     *
     * @param  mixed  ...$arguments
     * @return mixed
     */
    public static function dispatchAfterResponse(...$arguments)
    {
        return self::dispatch(...$arguments)->afterResponse();
    }

    public static function withChain($chain)
    {
        return new PendingChain(static::class, $chain);
    }
}
