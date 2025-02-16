<?php

namespace LaraGram\Bus;

use LaraGram\Contracts\Cache\CacheDriver as Cache;

class UniqueLock
{
    protected $cache;

    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Attempt to acquire a lock for the given job.
     *
     * @param  mixed  $job
     * @return bool
     */
    public function acquire($job)
    {
        $uniqueFor = method_exists($job, 'uniqueFor')
            ? $job->uniqueFor()
            : ($job->uniqueFor ?? 0);

        $cache = method_exists($job, 'uniqueVia')
            ? $job->uniqueVia()
            : $this->cache;

        return (bool) $cache->lock($this->getKey($job), $uniqueFor)->get();
    }

    /**
     * Release the lock for the given job.
     *
     * @param  mixed  $job
     * @return void
     */
    public function release($job)
    {
        $cache = method_exists($job, 'uniqueVia')
            ? $job->uniqueVia()
            : $this->cache;

        $cache->lock($this->getKey($job))->forceRelease();
    }

    /**
     * Generate the lock key for the given job.
     *
     * @param  mixed  $job
     * @return string
     */
    protected function getKey($job)
    {
        $uniqueId = method_exists($job, 'uniqueId')
            ? $job->uniqueId()
            : ($job->uniqueId ?? '');

        return 'laragram_unique_job:'.get_class($job).':'.$uniqueId;
    }
}
