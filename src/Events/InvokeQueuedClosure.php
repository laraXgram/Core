<?php

namespace LaraGram\Events;

use LaraGram\Support\SerializableClosure\SerializableClosure;
use Throwable;

class InvokeQueuedClosure
{
    /**
     * Handle the event.
     *
     * @param  SerializableClosure  $closure
     * @param  array  $arguments
     * @return void
     */
    public function handle($closure, array $arguments)
    {
        call_user_func($closure->getClosure(), ...$arguments);
    }

    /**
     * Handle a job failure.
     *
     * @param  SerializableClosure  $closure
     * @param  array  $arguments
     * @param  array  $catchCallbacks
     * @param  Throwable  $exception
     * @return void
     */
    public function failed($closure, array $arguments, array $catchCallbacks, $exception)
    {
        $arguments[] = $exception;

        collect($catchCallbacks)->each->__invoke(...$arguments);
    }
}
