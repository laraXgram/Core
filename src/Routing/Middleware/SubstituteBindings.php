<?php

namespace LaraGram\Routing\Middleware;

use Closure;
use LaraGram\Contracts\Routing\Registrar;
use LaraGram\Database\Eloquent\ModelNotFoundException;

class SubstituteBindings
{
    /**
     * The router instance.
     *
     * @var \LaraGram\Contracts\Routing\Registrar
     */
    protected $router;

    /**
     * Create a new bindings substitutor.
     *
     * @param  \LaraGram\Contracts\Routing\Registrar  $router
     */
    public function __construct(Registrar $router)
    {
        $this->router = $router;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \LaraGram\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     *
     * @throws \LaraGram\Database\Eloquent\ModelNotFoundException
     */
    public function handle($request, Closure $next)
    {
        $route = $request->route();

        try {
            $this->router->substituteBindings($route);
            $this->router->substituteImplicitBindings($route);
        } catch (ModelNotFoundException $exception) {
            if ($route->getMissing()) {
                return $route->getMissing()($request, $exception);
            }

            throw $exception;
        }

        return $next($request);
    }
}
