<?php

namespace LaraGram\Listening;

use LaraGram\Request\RedirectResponse;
use LaraGram\Support\Traits\Macroable;
use LaraGram\Cache\CacheManager as CacheStore;

class Redirector
{
    use Macroable;

    /**
     * The URL generator instance.
     *
     * @var \LaraGram\Listening\PathGenerator
     */
    protected $generator;

    /**
     * The cache store instance.
     *
     * @var \LaraGram\Cache\CacheManager
     */
    protected $cache;

    /**
     * Create a new Redirector instance.
     *
     * @param  \LaraGram\Listening\PathGenerator  $generator
     * @return void
     */
    public function __construct(PathGenerator $generator)
    {
        $this->generator = $generator;
    }

    /**
     * Create a new redirect response to a named listen.
     *
     * @param  \BackedEnum|string  $listen
     * @param  mixed  $parameters
     * @return \LaraGram\Request\RedirectResponse
     */
    public function listen($listen, $parameters = [])
    {
        return $this->createRedirect($this->generator->listen($listen, $parameters));
    }

    /**
     * Create a new redirect response to a controller action.
     *
     * @param  string|array  $action
     * @param  mixed  $parameters
     * @return \LaraGram\Request\RedirectResponse
     */
    public function action($action, $parameters = [])
    {
        return $this->createRedirect($this->generator->action($action, $parameters));
    }

    /**
     * Create a new response that takes the user back along their trail.
     *
     * @param  int|string  $to  The number of steps, or the name of a step.
     * @param  \Closure|string|null  $fallback  A listen name or a callback to run when there is nothing to go back to.
     * @return \LaraGram\Listening\Navigation\BackResponse
     */
    public function back($to = 1, $fallback = null)
    {
        return tap(app('navigation')->back($to, $fallback), function ($redirect) {
            if (isset($this->cache)) {
                $redirect->setCache($this->cache);
            }

            $redirect->setRequest($this->generator->getRequest());
        });
    }

    /**
     * Create a new redirect response.
     *
     * @param  string  $path
     * @return \LaraGram\Request\RedirectResponse
     */
    protected function createRedirect($path)
    {
        return tap(new RedirectResponse($path), function ($redirect) {
            if (isset($this->cache)) {
                $redirect->setCache($this->cache);
            }

            $redirect->setRequest($this->generator->getRequest());
        });
    }

    /**
     * Get the URL generator instance.
     *
     * @return \LaraGram\Listening\PathGenerator
     */
    public function getUrlGenerator()
    {
        return $this->generator;
    }

    /**
     * Set the active cache store.
     *
     * @param  \LaraGram\Cache\CacheManager  $cache
     * @return void
     */
    public function setCache(CacheStore $cache)
    {
        $this->cache = $cache;
    }
}
