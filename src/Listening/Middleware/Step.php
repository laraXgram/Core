<?php

namespace LaraGram\Listening\Middleware;

use Closure;
use LaraGram\Support\Facades\Step as StepFacade;

class Step
{
    /**
     * Handle an incoming request.
     *
     * @param \LaraGram\Request\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next, $step)
    {
        if (StepFacade::is($step)) {
            return $next($request);
        }

        return false;
    }
}
