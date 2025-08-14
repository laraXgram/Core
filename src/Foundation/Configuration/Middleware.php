<?php

namespace LaraGram\Foundation\Configuration;

use LaraGram\Support\Arr;

class Middleware
{
    /**
     * The user defined global middleware stack.
     *
     * @var array
     */
    protected $global = [];

    /**
     * The middleware that should be prepended to the global middleware stack.
     *
     * @var array
     */
    protected $prepends = [];

    /**
     * The middleware that should be appended to the global middleware stack.
     *
     * @var array
     */
    protected $appends = [];

    /**
     * The middleware that should be removed from the global middleware stack.
     *
     * @var array
     */
    protected $removals = [];

    /**
     * The middleware that should be replaced in the global middleware stack.
     *
     * @var array
     */
    protected $replacements = [];

    /**
     * The user defined middleware groups.
     *
     * @var array
     */
    protected $groups = [];

    /**
     * The middleware that should be prepended to the specified groups.
     *
     * @var array
     */
    protected $groupPrepends = [];

    /**
     * The middleware that should be appended to the specified groups.
     *
     * @var array
     */
    protected $groupAppends = [];

    /**
     * The middleware that should be removed from the specified groups.
     *
     * @var array
     */
    protected $groupRemovals = [];

    /**
     * The middleware that should be replaced in the specified groups.
     *
     * @var array
     */
    protected $groupReplacements = [];

    /**
     * Indicates if Redis throttling should be applied.
     *
     * @var bool
     */
    protected $throttleWithRedis = false;

    /**
     * The custom middleware aliases.
     *
     * @var array
     */
    protected $customAliases = [];

    /**
     * The custom middleware priority definition.
     *
     * @var array
     */
    protected $priority = [];

    /**
     * The middleware to prepend to the middleware priority definition.
     *
     * @var array
     */
    protected $prependPriority = [];

    /**
     * The middleware to append to the middleware priority definition.
     *
     * @var array
     */
    protected $appendPriority = [];

    /**
     * Prepend middleware to the application's global middleware stack.
     *
     * @param  array|string  $middleware
     * @return $this
     */
    public function prepend(array|string $middleware)
    {
        $this->prepends = array_merge(
            Arr::wrap($middleware),
            $this->prepends
        );

        return $this;
    }

    /**
     * Append middleware to the application's global middleware stack.
     *
     * @param  array|string  $middleware
     * @return $this
     */
    public function append(array|string $middleware)
    {
        $this->appends = array_merge(
            $this->appends,
            Arr::wrap($middleware)
        );

        return $this;
    }

    /**
     * Remove middleware from the application's global middleware stack.
     *
     * @param  array|string  $middleware
     * @return $this
     */
    public function remove(array|string $middleware)
    {
        $this->removals = array_merge(
            $this->removals,
            Arr::wrap($middleware)
        );

        return $this;
    }

    /**
     * Specify a middleware that should be replaced with another middleware.
     *
     * @param  string  $search
     * @param  string  $replace
     * @return $this
     */
    public function replace(string $search, string $replace)
    {
        $this->replacements[$search] = $replace;

        return $this;
    }

    /**
     * Define the global middleware for the application.
     *
     * @param  array  $middleware
     * @return $this
     */
    public function use(array $middleware)
    {
        $this->global = $middleware;

        return $this;
    }

    /**
     * Define a middleware group.
     *
     * @param  string  $group
     * @param  array  $middleware
     * @return $this
     */
    public function group(string $group, array $middleware)
    {
        $this->groups[$group] = $middleware;

        return $this;
    }

    /**
     * Prepend the given middleware to the specified group.
     *
     * @param  string  $group
     * @param  array|string  $middleware
     * @return $this
     */
    public function prependToGroup(string $group, array|string $middleware)
    {
        $this->groupPrepends[$group] = array_merge(
            Arr::wrap($middleware),
            $this->groupPrepends[$group] ?? []
        );

        return $this;
    }

    /**
     * Append the given middleware to the specified group.
     *
     * @param  string  $group
     * @param  array|string  $middleware
     * @return $this
     */
    public function appendToGroup(string $group, array|string $middleware)
    {
        $this->groupAppends[$group] = array_merge(
            $this->groupAppends[$group] ?? [],
            Arr::wrap($middleware)
        );

        return $this;
    }

    /**
     * Remove the given middleware from the specified group.
     *
     * @param  string  $group
     * @param  array|string  $middleware
     * @return $this
     */
    public function removeFromGroup(string $group, array|string $middleware)
    {
        $this->groupRemovals[$group] = array_merge(
            Arr::wrap($middleware),
            $this->groupRemovals[$group] ?? []
        );

        return $this;
    }

    /**
     * Replace the given middleware in the specified group with another middleware.
     *
     * @param  string  $group
     * @param  string  $search
     * @param  string  $replace
     * @return $this
     */
    public function replaceInGroup(string $group, string $search, string $replace)
    {
        $this->groupReplacements[$group][$search] = $replace;

        return $this;
    }

    /**
     * Modify the middleware in the "bot" group.
     *
     * @param  array|string  $append
     * @param  array|string  $prepend
     * @param  array|string  $remove
     * @param  array  $replace
     * @return $this
     */
    public function bot(array|string $append = [], array|string $prepend = [], array|string $remove = [], array $replace = [])
    {
        return $this->modifyGroup('bot', $append, $prepend, $remove, $replace);
    }

    /**
     * Modify the middleware in the given group.
     *
     * @param  string  $group
     * @param  array|string  $append
     * @param  array|string  $prepend
     * @param  array|string  $remove
     * @param  array  $replace
     * @return $this
     */
    protected function modifyGroup(string $group, array|string $append, array|string $prepend, array|string $remove, array $replace)
    {
        if (! empty($append)) {
            $this->appendToGroup($group, $append);
        }

        if (! empty($prepend)) {
            $this->prependToGroup($group, $prepend);
        }

        if (! empty($remove)) {
            $this->removeFromGroup($group, $remove);
        }

        if (! empty($replace)) {
            foreach ($replace as $search => $replace) {
                $this->replaceInGroup($group, $search, $replace);
            }
        }

        return $this;
    }

    /**
     * Register additional middleware aliases.
     *
     * @param  array  $aliases
     * @return $this
     */
    public function alias(array $aliases)
    {
        $this->customAliases = $aliases;

        return $this;
    }

    /**
     * Define the middleware priority for the application.
     *
     * @param  array  $priority
     * @return $this
     */
    public function priority(array $priority)
    {
        $this->priority = $priority;

        return $this;
    }

    /**
     * Prepend middleware to the priority middleware.
     *
     * @param  array|string  $before
     * @param  string  $prepend
     * @return $this
     */
    public function prependToPriorityList($before, $prepend)
    {
        $this->prependPriority[$prepend] = $before;

        return $this;
    }

    /**
     * Append middleware to the priority middleware.
     *
     * @param  array|string  $after
     * @param  string  $append
     * @return $this
     */
    public function appendToPriorityList($after, $append)
    {
        $this->appendPriority[$append] = $after;

        return $this;
    }

    /**
     * Get the global middleware.
     *
     * @return array
     */
    public function getGlobalMiddleware()
    {
        $middleware = $this->global ?: array_values(array_filter([
            \LaraGram\Foundation\Bot\Middleware\InvokeDeferredCallbacks::class,
        ]));

        $middleware = array_map(function ($middleware) {
            return $this->replacements[$middleware] ?? $middleware;
        }, $middleware);

        return array_values(array_filter(
            array_diff(
                array_unique(array_merge($this->prepends, $middleware, $this->appends)),
                $this->removals
            )
        ));
    }

    /**
     * Get the middleware groups.
     *
     * @return array
     */
    public function getMiddlewareGroups()
    {
        $middleware = [
            'bot' => array_values(array_filter([
                \LaraGram\Listening\Middleware\SubstituteBindings::class,
            ])),
        ];

        $middleware = array_merge($middleware, $this->groups);

        foreach ($middleware as $group => $groupedMiddleware) {
            foreach ($groupedMiddleware as $index => $groupMiddleware) {
                if (isset($this->groupReplacements[$group][$groupMiddleware])) {
                    $middleware[$group][$index] = $this->groupReplacements[$group][$groupMiddleware];
                }
            }
        }

        foreach ($this->groupRemovals as $group => $removals) {
            $middleware[$group] = array_values(array_filter(
                array_diff($middleware[$group] ?? [], $removals)
            ));
        }

        foreach ($this->groupPrepends as $group => $prepends) {
            $middleware[$group] = array_values(array_filter(
                array_unique(array_merge($prepends, $middleware[$group] ?? []))
            ));
        }

        foreach ($this->groupAppends as $group => $appends) {
            $middleware[$group] = array_values(array_filter(
                array_unique(array_merge($middleware[$group] ?? [], $appends))
            ));
        }

        return $middleware;
    }

    /**
     * Indicate that LaraGram's throttling middleware should use Redis.
     *
     * @return $this
     */
    public function throttleWithRedis()
    {
        $this->throttleWithRedis = true;

        return $this;
    }

    /**
     * Get the middleware aliases.
     *
     * @return array
     */
    public function getMiddlewareAliases()
    {
        return array_merge($this->defaultAliases(), $this->customAliases);
    }

    /**
     * Get the default middleware aliases.
     *
     * @return array
     */
    protected function defaultAliases()
    {
        $aliases = [
            'can' => \LaraGram\Auth\Middleware\Authorize::class,
            'reply' => \LaraGram\Listening\Middleware\Reply::class,
            'scope' => \LaraGram\Listening\Middleware\Scope::class,
            'step' => \LaraGram\Listening\Middleware\Step::class,
            'throttle' => $this->throttleWithRedis
                ? \LaraGram\Listening\Middleware\ThrottleRequestsWithRedis::class
                : \LaraGram\Listening\Middleware\ThrottleRequests::class,
        ];

        return $aliases;
    }

    /**
     * Get the middleware priority for the application.
     *
     * @return array
     */
    public function getMiddlewarePriority()
    {
        return $this->priority;
    }

    /**
     * Get the middleware to prepend to the middleware priority definition.
     *
     * @return array
     */
    public function getMiddlewarePriorityPrepends()
    {
        return $this->prependPriority;
    }

    /**
     * Get the middleware to append to the middleware priority definition.
     *
     * @return array
     */
    public function getMiddlewarePriorityAppends()
    {
        return $this->appendPriority;
    }
}
