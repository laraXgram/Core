<?php

namespace LaraGram\Listening\Middleware;

use Closure;
use LaraGram\Contracts\Container\Container;
use LaraGram\Listening\Navigation\Navigator;

/**
 * Take the user back when a button made by Make::back() is pressed.
 */
class HandleBackButton
{
    /**
     * Create a new middleware instance.
     *
     * @param  \LaraGram\Listening\Navigation\Navigator  $navigator
     * @param  \LaraGram\Contracts\Container\Container  $container
     * @return void
     */
    public function __construct(
        protected Navigator $navigator,
        protected Container $container,
    ) {
    }

    /**
     * Handle an incoming update.
     *
     * @param  \LaraGram\Request\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $button = Navigator::parseButton(callback_query()?->data);

        if ($button === null) {
            return $next($request);
        }

        // Leaving for an earlier step leaves the conversation too.
        if ($this->container->bound('conversation') && $this->container->make('conversation')->active()) {
            $this->container->make('conversation')->cancel('back');
        }

        return $this->navigator->navigate($button['to'], $button['fallback'], $request);
    }
}
