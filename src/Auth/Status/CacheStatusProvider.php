<?php

namespace LaraGram\Auth\Status;

use LaraGram\Contracts\Auth\StatusProvider;
use LaraGram\Contracts\Cache\Repository;

class CacheStatusProvider implements StatusProvider
{
    /**
     * The cache repository (redis / memcached / file / array ...).
     *
     * @var \LaraGram\Contracts\Cache\Repository
     */
    protected $store;

    /**
     * The cache key prefix.
     *
     * @var string
     */
    protected $prefix;

    /**
     * The time-to-live in seconds (null = forever).
     *
     * @var int|null
     */
    protected $ttl;

    /**
     * Create a new cache status provider.
     *
     * @param  \LaraGram\Contracts\Cache\Repository  $store
     * @param  string  $prefix
     * @param  int|null  $ttl
     * @return void
     */
    public function __construct(Repository $store, $prefix = 'chat_status', $ttl = null)
    {
        $this->store = $store;
        $this->prefix = $prefix;
        $this->ttl = $ttl;
    }

    /**
     * {@inheritdoc}
     */
    public function get($userId, $chatId)
    {
        return $this->store->get($this->key($userId, $chatId));
    }

    /**
     * {@inheritdoc}
     */
    public function put($userId, $chatId, array $attributes)
    {
        $status = $attributes['status'] ?? null;

        if (is_null($status)) {
            return;
        }

        is_null($this->ttl)
            ? $this->store->forever($this->key($userId, $chatId), $status)
            : $this->store->put($this->key($userId, $chatId), $status, $this->ttl);
    }

    /**
     * Build the cache key for a user/chat pair.
     *
     * @param  int|string  $userId
     * @param  int|string  $chatId
     * @return string
     */
    protected function key($userId, $chatId)
    {
        return $this->prefix.':'.$chatId.':'.$userId;
    }
}
