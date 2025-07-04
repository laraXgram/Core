<?php

namespace LaraGram\Listening\Middleware;

use Closure;
use LaraGram\Contracts\Listening\Registrar;
use LaraGram\Database\Eloquent\ModelNotFoundException;

class SubstituteBindings
{
    /**
     * The listener instance.
     *
     * @var \LaraGram\Contracts\Listening\Registrar
     */
    protected $listener;

    /**
     * Create a new bindings substitutor.
     *
     * @param  \LaraGram\Contracts\Listening\Registrar  $listener
     * @return void
     */
    public function __construct(Registrar $listener)
    {
        $this->listener = $listener;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \LaraGram\Request\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $listen = $request->listen();

        try {
            $this->listener->substituteBindings($listen);
            $this->listener->substituteImplicitBindings($listen);
        } catch (ModelNotFoundException $exception) {
            if ($listen->getMissing()) {
                return $listen->getMissing()($request, $exception);
            }

            throw $exception;
        }
        return $next($request);
    }
}
