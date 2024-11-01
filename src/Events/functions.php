<?php

namespace LaraGram\Events;

use Closure;

if (! function_exists('LaraGram\Events\queueable')) {
    /**
     * Create a new queued Closure event listener.
     *
     * @param  Closure  $closure
     * @return QueuedClosure
     */
    function queueable(Closure $closure)
    {
        return new QueuedClosure($closure);
    }
}
