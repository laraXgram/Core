<?php

namespace LaraGram\Bus;

use LaraGram\Contracts\Cache\Repository as Cache;
use LaraGram\Queue\Attributes\DebounceFor;
use LaraGram\Queue\Attributes\ReadsQueueAttributes;
use LaraGram\Support\Tempora;
use LaraGram\Support\Str;

class DebounceLock
{
    use ReadsQueueAttributes;

    /**
     * The cache repository implementation.
     *
     * @var \LaraGram\Contracts\Cache\Repository
     */
    protected $cache;

    /**
     * Create a new debounce lock manager instance.
     *
     * @param  \LaraGram\Contracts\Cache\Repository  $cache
     */
    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Store a debounce owner token for the given job.
     *
     * Overwrites any existing token, implementing last-writer-wins semantics.
     *
     * @param  mixed  $job
     * @param  int|null  $debounceFor
     * @param  int|null  $maxWait
     * @return array{owner: string, maxWaitExceeded: bool}
     */
    public function acquire($job, $debounceFor = null, $maxWait = null)
    {
        $cache = $this->resolveCache($job);

        $ttl = max(($debounceFor ?? $this->getDebounceDelay($job)) * 10, 300);

        $cache->put($key = static::getKey($job), $owner = Str::random(40), $ttl);

        return [
            'owner' => $owner,
            'maxWaitExceeded' => $this->maxWaitExceeded(
                $cache, $key, $ttl, $maxWait ?? $this->getMaxDebounceWait($job)
            ),
        ];
    }

    /**
     * Determine if the maximum debounce wait time has been exceeded.
     */
    protected function maxWaitExceeded(Cache $cache, string $key, int $ttl, ?int $maxWait): bool
    {
        if (is_null($maxWait)) {
            return false;
        }

        $timestampKey = $key.':first_dispatched_at';

        $firstDispatchedAt = $cache->get($timestampKey);

        if (is_null($firstDispatchedAt)) {
            $cache->put($timestampKey, Tempora::now()->getTimestamp(), $ttl);

            return false;
        }

        $elapsed = Tempora::now()->getTimestamp() - $firstDispatchedAt;

        if ($elapsed >= $maxWait) {
            $cache->forget($timestampKey);

            return true;
        }

        return false;
    }

    /**
     * Get the current owner for the given job.
     *
     * @param  mixed  $job
     * @return string|null
     */
    public function getCurrentOwner($job)
    {
        return $this->resolveCache($job)->get(static::getKey($job));
    }

    /**
     * Remove the debounce token for the given job.
     *
     * @param  mixed  $job
     * @param  string  $owner
     * @return void
     */
    public function release($job, string $owner = '')
    {
        $key = static::getKey($job);

        $cache = $this->resolveCache($job);

        if (! empty($owner) && $cache->get($key) !== $owner) {
            return;
        }

        $cache->forget($key);
        $cache->forget($key.':first_dispatched_at');
    }

    /**
     * Get the debounce delay for the given job.
     *
     * @param  mixed  $job
     * @return int|null
     */
    public function getDebounceDelay($job)
    {
        return $this->getAttributeValue($job, DebounceFor::class, 'debounceFor');
    }

    /**
     * Get the maximum debounce wait time for the given job.
     *
     * @param  mixed  $job
     * @return int|null
     */
    public function getMaxDebounceWait($job)
    {
        return $this->getAttributeInstance($job, DebounceFor::class)?->maxWait ?? null;
    }

    /**
     * Generate the cache key for the given job.
     *
     * @param  mixed  $job
     * @return string
     */
    public static function getKey($job)
    {
        $debounceId = method_exists($job, 'debounceId')
            ? $job->debounceId()
            : ($job->debounceId ?? '');

        $jobName = method_exists($job, 'displayName')
            ? hash('xxh128', $job->displayName())
            : get_class($job);

        return 'laravel_debounced_job:'.$jobName.':'.$debounceId;
    }

    /**
     * Resolve the cache store for the given job.
     *
     * @param  mixed  $job
     * @return \LaraGram\Contracts\Cache\Repository
     */
    protected function resolveCache($job)
    {
        return method_exists($job, 'debounceVia')
            ? ($job->debounceVia() ?? $this->cache)
            : $this->cache;
    }
}
