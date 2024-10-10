<?php

namespace LaraGram\Cache\Driver;

use LaraGram\Cache\Database\Cache;
use LaraGram\Contracts\CacheDriver;

class DatabaseCacheDriver implements CacheDriver
{
    public function get($key)
    {
        $cache = Cache::where('cache_key', md5($key))->first();

        if ($cache && $cache->expiry_time && strtotime($cache->expiry_time) < time()) {
            $this->forgot(md5($key));
            return null;
        }

        return $cache ? unserialize($cache->cache_value) : null;
    }

    public function set($key, $value, $ttl = 3600): void
    {
        $expiryTime = date('Y-m-d H:i:s', time() + $ttl);

        Cache::updateOrCreate(
            ['cache_key' => md5($key)],
            ['cache_value' => serialize($value), 'expiry_time' => $expiryTime]
        );
    }

    public function forgot($key): void
    {
        Cache::where('cache_key', md5($key))->delete();
    }

    public function clear(): void
    {
        Cache::truncate();
    }

    public function all()
    {
        return Cache::all();
    }

    public function pull($key)
    {
        $data = $this->get($key);
        $this->forgot($key);
        return $data;
    }
}