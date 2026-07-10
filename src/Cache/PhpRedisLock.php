<?php

namespace LaraGram\Cache;

use LaraGram\Redis\Connections\PhpRedisConnection;

class PhpRedisLock extends RedisLock
{
    /**
     * Create a new phpredis lock instance.
     *
     * @param  \LaraGram\Redis\Connections\PhpRedisConnection  $redis
     * @param  string  $name
     * @param  int  $seconds
     * @param  string|null  $owner
     */
    public function __construct(PhpRedisConnection $redis, string $name, int $seconds, ?string $owner = null)
    {
        parent::__construct($redis, $name, $seconds, $owner);
    }

    /**
     * {@inheritDoc}
     */
    public function refresh($seconds = null)
    {
        $seconds ??= $this->seconds;

        return (bool) $this->redis->eval(
            LuaScripts::refreshLock(),
            1,
            $this->name,
            ...$this->redis->pack([$this->owner, $seconds])
        );
    }

    /**
     * {@inheritDoc}
     */
    public function release()
    {
        return (bool) $this->redis->eval(
            LuaScripts::releaseLock(),
            1,
            $this->name,
            ...$this->redis->pack([$this->owner])
        );
    }
}
