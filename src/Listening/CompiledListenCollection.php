<?php

namespace LaraGram\Listening;

use LaraGram\Container\Container;
use LaraGram\Listening\Exceptions\MethodNotAllowedException;
use LaraGram\Listening\Exceptions\ResourceNotFoundException;
use LaraGram\Request\Request;
use LaraGram\Support\Collection;

class CompiledListenCollection extends AbstractListenCollection
{
    /**
     * The compiled listens collection.
     *
     * @var array
     */
    protected $compiled = [];

    /**
     * An array of the listen attributes keyed by name.
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * The dynamically added listens that were added after loading the cached, compiled listens.
     *
     * @var \LaraGram\Listening\ListenCollection|null
     */
    protected $listens;

    /**
     * The listener instance used by the listen.
     *
     * @var \LaraGram\Listening\Listener
     */
    protected $listener;

    /**
     * The container instance used by the listen.
     *
     * @var \LaraGram\Container\Container
     */
    protected $container;

    /**
     * Create a new CompiledListenCollection instance.
     *
     * @param  array  $compiled
     * @param  array  $attributes
     * @return void
     */
    public function __construct(array $compiled, array $attributes)
    {
        $this->compiled = $compiled;
        $this->attributes = $attributes;
        $this->listens = new ListenCollection();
    }

    /**
     * Add a Listen instance to the collection.
     *
     * @param  \LaraGram\Listening\Listen  $listen
     * @return \LaraGram\Listening\Listen
     */
    public function add(Listen $listen)
    {
        return $this->listens->add($listen);
    }

    /**
     * Refresh the name look-up table.
     *
     * This is done in case any names are fluently defined or if listens are overwritten.
     *
     * @return void
     */
    public function refreshNameLookups()
    {
        //
    }

    /**
     * Refresh the action look-up table.
     *
     * This is done in case any actions are overwritten with new controllers.
     *
     * @return void
     */
    public function refreshActionLookups()
    {
        //
    }

    /**
     * Find the first listen matching a given request.
     *
     * @param  \LaraGram\Request\Request  $request
     * @return \LaraGram\Listening\Listen
     *
     * @throws \Exception
     */
    public function match(Request $request)
    {
        $matcher = new CompiledPatternMatcher(
            $this->compiled, (new RequestContext)->fromRequest($request), $this->attributes
        );

        $listen = null;

        try {
            if ($result = $matcher->matchRequest($request)) {
                $listen = $this->getByName($result['_listen']);
            }
        } catch (ResourceNotFoundException|MethodNotAllowedException) {
            try {
                return $this->listens->match($request);
            } catch (\Exception) {
                //
            }
        }

        if ($listen && $listen->isFallback) {
            try {
                $dynamicListen = $this->listens->match($request);

                if (! $dynamicListen->isFallback) {
                    $listen = $dynamicListen;
                }
            } catch (\Exception) {
                //
            }
        }

        return $this->handleMatchedListen($request, $listen);
    }

    /**
     * Get listens from the collection by method.
     *
     * @param  string|null  $method
     * @return \LaraGram\Listening\Listen[]
     */
    public function get($method = null)
    {
        return $this->getListensByMethod()[$method] ?? [];
    }

    /**
     * Determine if the listen collection contains a given named listen.
     *
     * @param  string  $name
     * @return bool
     */
    public function hasNamedListen($name)
    {
        return isset($this->attributes[$name]) || $this->listens->hasNamedListen($name);
    }

    /**
     * Get a listen instance by its name.
     *
     * @param  string  $name
     * @return \LaraGram\Listening\Listen|null
     */
    public function getByName($name)
    {
        if (isset($this->attributes[$name])) {
            return $this->newListen($this->attributes[$name]);
        }

        return $this->listens->getByName($name);
    }

    /**
     * Get a listen instance by its controller action.
     *
     * @param  string  $action
     * @return \LaraGram\Listening\Listen|null
     */
    public function getByAction($action)
    {
        $attributes = (new Collection($this->attributes))->first(function (array $attributes) use ($action) {
            if (isset($attributes['action']['controller'])) {
                return trim($attributes['action']['controller'], '\\') === $action;
            }

            return $attributes['action']['uses'] === $action;
        });

        if ($attributes) {
            return $this->newListen($attributes);
        }

        return $this->listens->getByAction($action);
    }

    /**
     * Get all of the listens in the collection.
     *
     * @return \LaraGram\Listening\Listen[]
     */
    public function getListens()
    {
        return (new Collection($this->attributes))
            ->map(function (array $attributes) {
                return $this->newListen($attributes);
            })
            ->merge($this->listens->getListens())
            ->values()
            ->all();
    }

    /**
     * Get all of the listens keyed by their HTTP verb / method.
     *
     * @return array
     */
    public function getListensByMethod()
    {
        return (new Collection($this->getListens()))
            ->groupBy(function (Listen $listen) {
                return $listen->methods();
            })
            ->map(function (Collection $listens) {
                return $listens->mapWithKeys(function (Listen $listen) {
                    return [$listen->pattern => $listen];
                })->all();
            })
            ->all();
    }

    /**
     * Get all of the listens keyed by their name.
     *
     * @return \LaraGram\Listening\Listen[]
     */
    public function getListensByName()
    {
        return (new Collection($this->getListens()))
            ->keyBy(function (Listen $listen) {
                return $listen->getName();
            })
            ->all();
    }

    /**
     * Resolve an array of attributes to a Listen instance.
     *
     * @param  array  $attributes
     * @return \LaraGram\Listening\Listen
     */
    protected function newListen(array $attributes)
    {
        if (empty($attributes['action']['prefix'] ?? '')) {
            $baseUri = $attributes['pattern'];
        } else {
            $prefix = $attributes['action']['prefix'];

            $baseUri = ($prefix ?? '').$attributes['pattern'];
        }

        return $this->listener->newListen($attributes['methods'], $baseUri, $attributes['action'])
            ->setFallback($attributes['fallback'])
            ->setDefaults($attributes['defaults'])
            ->setWheres($attributes['wheres'])
            ->setBindingFields($attributes['bindingFields'])
            ->connection($attributes['action']['connection'])
            ->block($attributes['lockSeconds'] ?? null, $attributes['waitSeconds'] ?? null)
            ->withTrashed($attributes['withTrashed'] ?? false);
    }

    /**
     * Set the listener instance on the listen.
     *
     * @param  \LaraGram\Listening\Listener  $listener
     * @return $this
     */
    public function setListener(Listener $listener)
    {
        $this->listener = $listener;

        return $this;
    }

    /**
     * Set the container instance on the listen.
     *
     * @param  \LaraGram\Container\Container  $container
     * @return $this
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;

        return $this;
    }
}
