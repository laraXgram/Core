<?php

namespace LaraGram\Cache\Driver;

use LaraGram\Contracts\Cache\CacheDriver;
use LaraGram\Redis\Connection;
use Redis;

class RedisCacheDriver implements CacheDriver {
    protected Redis $redis;

    public function __construct(Connection $redis) {
        $this->redis = $redis->getConnection();
    }

    /**
     * @throws \RedisException
     */
    public function get($key) {
        return unserialize($this->redis->get($key));
    }

    /**
     * @throws \RedisException
     */
    public function set($key, $value, $ttl = 3600): void
    {
        $this->redis->setex($key, $ttl, serialize($value));
    }

    /**
     * @throws \RedisException
     */
    public function forgot($key): void
    {
        $this->redis->del($key);
    }

    /**
     * @throws \RedisException
     */
    public function clear(): void
    {
        $this->redis->flushDB();
    }
}