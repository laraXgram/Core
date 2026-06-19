<?php

namespace LaraGram\Cache;

use LaraGram\Contracts\Config\Repository as Config;

/**
 * Manages step manager instances across multiple cache stores.
 *
 * @mixin \LaraGram\Cache\Step
 */
class StepManager
{
    /**
     * The cache manager instance.
     *
     * @var \LaraGram\Cache\CacheManager
     */
    protected $cache;

    /**
     * The configuration repository instance.
     *
     * @var \LaraGram\Contracts\Config\Repository
     */
    protected $config;

    /**
     * The resolved step instances keyed by step store name.
     *
     * @var array<string, \LaraGram\Cache\Step>
     */
    protected $stores = [];

    /**
     * Create a new step manager instance.
     *
     * @param  \LaraGram\Cache\CacheManager  $cache
     * @param  \LaraGram\Contracts\Config\Repository  $config
     * @return void
     */
    public function __construct(CacheManager $cache, Config $config)
    {
        $this->cache = $cache;
        $this->config = $config;
    }

    /**
     * Get a step manager instance for the given step store.
     *
     * @param  string|null  $name
     * @return \LaraGram\Cache\Step
     */
    public function store(?string $name = null): Step
    {
        $name = $name ?: $this->getDefaultStore();

        return $this->stores[$name] ??= $this->resolve($name);
    }

    /**
     * Resolve the step instance for the given step store.
     *
     * @param  string  $name
     * @return \LaraGram\Cache\Step
     */
    protected function resolve(string $name): Step
    {
        $stores = $this->config->get('cache.step.stores', []);

        // A step store maps to a cache store name. When no mapping exists
        // the given name is treated as a cache store name directly, so any
        // store defined in cache.stores can be used without extra config.
        $cacheStore = $stores[$name] ?? $name;

        return new Step($this->cache->store($cacheStore));
    }

    /**
     * Get the name of the default step store.
     *
     * @return string
     */
    public function getDefaultStore(): string
    {
        return $this->config->get('cache.step.default')
            ?: $this->cache->getDefaultDriver();
    }

    /**
     * Dynamically pass methods to the default step store.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->store()->{$method}(...$parameters);
    }
}
