<?php

namespace LaraGram\Foundation\Http\Middleware;

use LaraGram\Container\Container;
use LaraGram\Foundation\Routing\PrecognitionCallableDispatcher;
use LaraGram\Foundation\Routing\PrecognitionControllerDispatcher;
use LaraGram\Routing\Contracts\CallableDispatcher as CallableDispatcherContract;
use LaraGram\Routing\Contracts\ControllerDispatcher as ControllerDispatcherContract;

class HandlePrecognitiveRequests
{
    /**
     * The container instance.
     *
     * @var \LaraGram\Container\Container
     */
    protected $container;

    /**
     * Create a new middleware instance.
     *
     * @param  \LaraGram\Container\Container  $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \LaraGram\Http\Request  $request
     * @param  \Closure  $next
     * @return \LaraGram\Http\Response
     */
    public function handle($request, $next)
    {
        if (! $request->isAttemptingPrecognition()) {
            return $this->appendVaryHeader($request, $next($request));
        }

        $bindings = $this->container->getBindings();
        $callableBinding = $bindings[CallableDispatcherContract::class] ?? null;
        $controllerBinding = $bindings[ControllerDispatcherContract::class] ?? null;

        $this->prepareForPrecognition($request);

        return tap($next($request), function ($response) use ($request, $callableBinding, $controllerBinding) {
            $response->headers->set('Precognition', 'true');

            $this->appendVaryHeader($request, $response);

            $this->restoreDispatchers($callableBinding, $controllerBinding);
        });
    }

    /**
     * Prepare to handle a precognitive request.
     *
     * @param  \LaraGram\Http\Request  $request
     * @return void
     */
    protected function prepareForPrecognition($request)
    {
        $request->attributes->set('precognitive', true);

        $this->container->bind(CallableDispatcherContract::class, fn ($app) => new PrecognitionCallableDispatcher($app));
        $this->container->bind(ControllerDispatcherContract::class, fn ($app) => new PrecognitionControllerDispatcher($app));
    }

    /**
     * Append the appropriate "Vary" header to the given response.
     *
     * @param  \LaraGram\Http\Request  $request
     * @param  \LaraGram\Http\Response  $response
     * @return \LaraGram\Http\Response
     */
    protected function appendVaryHeader($request, $response)
    {
        return tap($response, fn () => $response->headers->set('Vary', implode(', ', array_filter([
            $response->headers->get('Vary'),
            'Precognition',
        ]))));
    }

    /**
     * Restore the original route dispatcher bindings.
     *
     * @param  array|null  $callableBinding
     * @param  array|null  $controllerBinding
     * @return void
     */
    protected function restoreDispatchers($callableBinding, $controllerBinding)
    {
        if ($callableBinding) {
            $this->container->bind(CallableDispatcherContract::class, $callableBinding['concrete'], $callableBinding['shared']);
        }

        if ($controllerBinding) {
            $this->container->bind(ControllerDispatcherContract::class, $controllerBinding['concrete'], $controllerBinding['shared']);
        }
    }
}
