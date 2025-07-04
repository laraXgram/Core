<?php

namespace LaraGram\Listening\Contracts;

use LaraGram\Listening\Listen;

interface CallableDispatcher
{
    /**
     * Dispatch a request to a given callable.
     *
     * @param  \LaraGram\Listening\Listen  $listen
     * @param  callable  $callable
     * @return mixed
     */
    public function dispatch(Listen $listen, $callable);
}
