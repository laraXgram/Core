<?php

namespace LaraGram\Listening;

use BackedEnum;
use Closure;
use LaraGram\Contracts\Listening\PathGenerator as PathGeneratorContract;
use LaraGram\Contracts\Listening\PathListenable;
use LaraGram\Listening\Exceptions\PathGenerationException;
use LaraGram\Request\Request;
use LaraGram\Support\Arr;
use LaraGram\Support\Collection;
use LaraGram\Support\InteractsWithTime;
use LaraGram\Support\Traits\Macroable;
use InvalidArgumentException;
use LaraGram\Listening\Exceptions\ListenNotFoundException;

class PathGenerator implements PathGeneratorContract
{
    use InteractsWithTime, Macroable;

    /**
     * The listen collection.
     *
     * @var \LaraGram\Listening\ListenCollectionInterface
     */
    protected $listens;

    /**
     * The request instance.
     *
     * @var \LaraGram\Request\Request
     */
    protected $request;

    /**
     * The root namespace being applied to controller actions.
     *
     * @var string
     */
    protected $rootNamespace;

    /**
     * The cache resolver callable.
     *
     * @var callable
     */
    protected $cacheResolver;

    /**
     * The encryption key resolver callable.
     *
     * @var callable
     */
    protected $keyResolver;

    /**
     * The missing named listen resolver callable.
     *
     * @var callable
     */
    protected $missingNamedListenResolver;

    /**
     * The callback to use to format paths.
     *
     * @var \Closure
     */
    protected $formatPathUsing;

    /**
     * The listen Path generator instance.
     *
     * @var \LaraGram\Listening\ListenPathGenerator|null
     */
    protected $listenGenerator;

    /**
     * Create a new Path Generator instance.
     *
     * @param  \LaraGram\Listening\ListenCollectionInterface  $listens
     * @param  \LaraGram\Request\Request  $request
     * @return void
     */
    public function __construct(ListenCollectionInterface $listens, Request $request)
    {
        $this->listens = $listens;

        $this->setRequest($request);
    }

    /**
     * Get the Path to a named listen.
     *
     * @param \BackedEnum|string $name
     * @param mixed $parameters
     * @return string
     *
     * @throws \LaraGram\Listening\Exceptions\ListenNotFoundException|\InvalidArgumentException|PathGenerationException
     */
    public function listen($name, $parameters = [])
    {
        if ($name instanceof BackedEnum && ! is_string($name = $name->value)) {
            throw new InvalidArgumentException('Attribute [name] expects a string backed enum.');
        }

        if (! is_null($listen = $this->listens->getByName($name))) {
            return $this->toListen($listen, $parameters);
        }

        if (! is_null($this->missingNamedListenResolver) &&
            ! is_null($path = call_user_func($this->missingNamedListenResolver, $name, $parameters))) {
            return $path;
        }

        throw new ListenNotFoundException("Listen [{$name}] not defined.");
    }

    /**
     * Get the Path for a given listen instance.
     *
     * @param  \LaraGram\Listening\Listen  $listen
     * @param  mixed  $parameters
     * @return string
     *
     * @throws \LaraGram\Listening\Exceptions\PathGenerationException
     */
    public function toListen($listen, $parameters)
    {
        $parameters = Collection::wrap($parameters)->map(function ($value, $key) use ($listen) {
            return $value instanceof PathListenable && $listen->bindingFieldFor($key)
                ? $value->{$listen->bindingFieldFor($key)}
                : $value;
        })->all();

        array_walk_recursive($parameters, function (&$item) {
            if ($item instanceof BackedEnum) {
                $item = $item->value;
            }
        });

        return $this->listenPath()->to(
            $listen, $this->formatParameters($parameters)
        );
    }

    /**
     * Get the Path to a controller action.
     *
     * @param string|array $action
     * @param mixed $parameters
     * @return string
     *
     * @throws PathGenerationException|\InvalidArgumentException
     */
    public function action($action, $parameters = [])
    {
        if (is_null($listen = $this->listens->getByAction($action = $this->formatAction($action)))) {
            throw new InvalidArgumentException("Action {$action} not defined.");
        }

        return $this->toListen($listen, $parameters);
    }

    /**
     * Format the given controller action.
     *
     * @param  string|array  $action
     * @return string
     */
    protected function formatAction($action)
    {
        if (is_array($action)) {
            $action = '\\'.implode('@', $action);
        }

        if ($this->rootNamespace && ! str_starts_with($action, '\\')) {
            return $this->rootNamespace.'\\'.$action;
        }

        return trim($action, '\\');
    }

    /**
     * Format the array of Path parameters.
     *
     * @param  mixed  $parameters
     * @return array
     */
    public function formatParameters($parameters)
    {
        $parameters = Arr::wrap($parameters);

        foreach ($parameters as $key => $parameter) {
            if ($parameter instanceof PathListenable) {
                $parameters[$key] = $parameter->getListenKey();
            }
        }

        return $parameters;
    }

    /**
     * Format the given Path segments into a single Path.
     *
     * @param  string  $path
     * @param  \LaraGram\Listening\Listen|null  $listen
     * @return string
     */
    public function format($path, $listen = null)
    {
        if ($this->formatPathUsing) {
            $path = call_user_func($this->formatPathUsing, $path, $listen);
        }

        return $path;
    }

    /**
     * Get the Listen Path generator instance.
     *
     * @return \LaraGram\Listening\ListenPathGenerator
     */
    protected function listenPath()
    {
        if (! $this->listenGenerator) {
            $this->listenGenerator = new ListenPathGenerator($this, $this->request);
        }

        return $this->listenGenerator;
    }

    /**
     * Set the default named parameters used by the URPathL generator.
     *
     * @param  array  $defaults
     * @return void
     */
    public function defaults(array $defaults)
    {
        $this->listenPath()->defaults($defaults);
    }

    /**
     * Get the default named parameters used by the URL generator.
     *
     * @return array
     */
    public function getDefaultParameters()
    {
        return $this->listenPath()->defaultParameters;
    }

    /**
     * Set a callback to be used to format the path of generated Paths.
     *
     * @param  \Closure  $callback
     * @return $this
     */
    public function formatPathUsing(Closure $callback)
    {
        $this->formatPathUsing = $callback;

        return $this;
    }

    /**
     * Get the path formatter being used by the Path generator.
     *
     * @return \Closure
     */
    public function pathFormatter()
    {
        return $this->formatPathUsing ?: function ($path) {
            return $path;
        };
    }

    /**
     * Get the request instance.
     *
     * @return \LaraGram\Request\Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Set the current request instance.
     *
     * @param  \LaraGram\Request\Request  $request
     * @return void
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;

        tap(optional($this->listenGenerator)->defaultParameters ?: [], function ($defaults) {
            $this->listenGenerator = null;

            if (! empty($defaults)) {
                $this->defaults($defaults);
            }
        });
    }

    /**
     * Set the route collection.
     *
     * @param  \LaraGram\Listening\ListenCollectionInterface  $routes
     * @return $this
     */
    public function setListens(ListenCollectionInterface $routes)
    {
        $this->listens = $routes;

        return $this;
    }

    /**
     * Get the cache implementation from the resolver.
     *
     * @return \LaraGram\Cache\CacheManager|null
     */
    protected function getCache()
    {
        if ($this->cacheResolver) {
            return call_user_func($this->cacheResolver);
        }
    }

    /**
     * Set the cache resolver for the generator.
     *
     * @param  callable  $cacheResolver
     * @return $this
     */
    public function setCacheResolver(callable $cacheResolver)
    {
        $this->cacheResolver = $cacheResolver;

        return $this;
    }

    /**
     * Set the encryption key resolver.
     *
     * @param  callable  $keyResolver
     * @return $this
     */
    public function setKeyResolver(callable $keyResolver)
    {
        $this->keyResolver = $keyResolver;

        return $this;
    }

    /**
     * Clone a new instance of the Path generator with a different encryption key resolver.
     *
     * @param  callable  $keyResolver
     * @return \LaraGram\Listening\pathGenerator
     */
    public function withKeyResolver(callable $keyResolver)
    {
        return (clone $this)->setKeyResolver($keyResolver);
    }

    /**
     * Set the callback that should be used to attempt to resolve missing named listens.
     *
     * @param  callable  $missingNamedListenResolver
     * @return $this
     */
    public function resolveMissingNamedListensUsing(callable $missingNamedListenResolver)
    {
        $this->missingNamedListenResolver = $missingNamedListenResolver;

        return $this;
    }

    /**
     * Get the root controller namespace.
     *
     * @return string
     */
    public function getRootControllerNamespace()
    {
        return $this->rootNamespace;
    }

    /**
     * Set the root controller namespace.
     *
     * @param  string  $rootNamespace
     * @return $this
     */
    public function setRootControllerNamespace($rootNamespace)
    {
        $this->rootNamespace = $rootNamespace;

        return $this;
    }
}
