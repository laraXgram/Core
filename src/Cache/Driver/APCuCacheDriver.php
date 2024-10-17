<?php

namespace LaraGram\Cache\Driver;

use LaraGram\Contracts\CacheDriver;

class APCuCacheDriver implements CacheDriver {
    public function get($key) {
        return apcu_fetch($key);
    }

    public function set($key, $value, $ttl = 3600): void
    {
        apcu_store($key, $value, $ttl);
    }

    public function forgot($key): void
    {
        apcu_delete($key);
    }

    public function clear(): void
    {
        apcu_clear_cache();
    }
}