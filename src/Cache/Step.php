<?php

namespace LaraGram\Cache;

use LaraGram\Support\Facades\Cache;
use LaraGram\Support\Facades\Hash;

class Step
{
    /**
     * The cache store implementation.
     *
     * @var \LaraGram\Contracts\Cache\Repository
     */
    protected $cache;

    /**
     * The step key name
     *
     * @var string
     */
    protected $key;

    /**
     * Create a new step manager instance.
     *
     * @param  \LaraGram\Contracts\Cache\Repository  $cache
     * @return void
     */
    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
        $this->key = Hash::make(id().":step");
    }

    /**
     * Create and set new step.
     *
     * @param  string $step
     * @param  int|null $ttl
     * @return bool
     */
    public function set(string $step, int $ttl = null): bool
    {
        return $this->cache->set($this->key, $step, $ttl);
    }

    /**
     * Get current step.
     *
     * @return mixed
     */
    public function get(): mixed
    {
        return $this->cache->get($this->key);
    }

    /**
     * Clear current step.
     *
     * @return bool
     */
    public function forget(): bool
    {
        return $this->cache->forget($this->key);
    }

    /**
     * Check user has any step.
     *
     * @return bool
     */
    public function hasStep(): bool
    {
        return $this->cache->has($this->key);
    }

    /**
     * Check user has not any step.
     *
     * @return bool
     */
    public function hasNotStep(): bool
    {
        return ! $this->hasStep();
    }

    /**
     * Get and clear current step.
     *
     * @return mixed
     */
    public function pull(): mixed
    {
        return $this->cache->pull($this->key);
    }

    /**
     * Check if the step has a specific value.
     *
     * @param  string $key
     * @return bool
     */
    public function is($key): mixed
    {
        return $this->get() === $key;
    }

    /**
     * Check if the step has not a specific value.
     *
     * @param  string $key
     * @return bool
     */
    public function isNot($key): mixed
    {
        return $this->get() !== $key;
    }
}