<?php

namespace LaraGram\Auth\Status;

use InvalidArgumentException;
use LaraGram\Support\Manager;
use LaraGram\Support\Str;

/**
 * Resolves the Telegram chat-member status of a user through a configurable
 * driver: live (Telegram API), eloquent, database or any cache store.
 *
 * @mixin \LaraGram\Contracts\Auth\StatusProvider
 */
class StatusManager extends Manager
{
    /**
     * The per-request resolved status cache, keyed by "chatId:userId".
     *
     * @var array<string, string|null>
     */
    protected $resolved = [];

    /**
     * The driver types that are read-only and therefore never observe updates.
     *
     * @var string[]
     */
    protected $readOnlyDrivers = ['live'];

    /**
     * Get the default status driver name.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return $this->config->get('auth.status.default', 'live');
    }

    /**
     * Create a new driver instance from its configuration block.
     *
     * Each entry under `auth.status.drivers` names a configuration whose
     * `driver` key selects the underlying provider type. This mirrors the
     * cache / database managers and lets several named instances share a type.
     *
     * @param  string  $name
     * @return \LaraGram\Contracts\Auth\StatusProvider
     *
     * @throws \InvalidArgumentException
     */
    protected function createDriver($name)
    {
        $config = $this->config->get('auth.status.drivers.'.$name);

        if (is_null($config)) {
            throw new InvalidArgumentException("Status driver [{$name}] is not defined.");
        }

        $type = $config['driver'] ?? $name;

        if (isset($this->customCreators[$type])) {
            return $this->customCreators[$type]($this->container, $config);
        }

        $method = 'create'.Str::studly($type).'Driver';

        if (! method_exists($this, $method)) {
            throw new InvalidArgumentException("Status driver type [{$type}] is not supported.");
        }

        return $this->$method($config);
    }

    /**
     * Determine whether chat-member updates should be observed and persisted.
     *
     * Defaults to "auto": every writable (mirror) driver observes, while the
     * read-only live driver does not. An explicit `auth.status.observe` value
     * always wins, so it can be forced on or off regardless of the driver.
     *
     * @return bool
     */
    public function shouldObserve()
    {
        $observe = $this->config->get('auth.status.observe');

        if (! is_null($observe)) {
            return (bool) $observe;
        }

        $default = $this->getDefaultDriver();
        $type = $this->config->get('auth.status.drivers.'.$default.'.driver', $default);

        return ! in_array($type, $this->readOnlyDrivers, true);
    }

    /**
     * Determine if the current (or given) user holds the given status.
     *
     * @param  string  $status
     * @param  int|string|null  $userId
     * @param  int|string|null  $chatId
     * @return bool
     */
    public function is($status, $userId = null, $chatId = null)
    {
        return $this->statusFor($userId, $chatId) === $status;
    }

    /**
     * Resolve the status of the current (or given) user, memoized per request.
     *
     * @param  int|string|null  $userId
     * @param  int|string|null  $chatId
     * @return string|null
     */
    public function statusFor($userId = null, $chatId = null)
    {
        $userId ??= $this->currentUserId();
        $chatId ??= $this->currentChatId();

        if (is_null($userId) || is_null($chatId)) {
            return null;
        }

        $key = $chatId.':'.$userId;

        return $this->resolved[$key] ??= $this->driver()->get($userId, $chatId);
    }

    /**
     * Persist the status of a user (for the observe listeners / manual sync).
     *
     * @param  int|string  $userId
     * @param  int|string  $chatId
     * @param  array  $attributes
     * @return void
     */
    public function record($userId, $chatId, array $attributes)
    {
        unset($this->resolved[$chatId.':'.$userId]);

        $this->driver()->put($userId, $chatId, $attributes);
    }

    /**
     * Create the live (Telegram API) driver.
     *
     * @param  array  $config
     * @return \LaraGram\Auth\Status\LiveStatusProvider
     */
    protected function createLiveDriver(array $config)
    {
        return new LiveStatusProvider;
    }

    /**
     * Create the Eloquent driver.
     *
     * @param  array  $config
     * @return \LaraGram\Auth\Status\EloquentStatusProvider
     */
    protected function createEloquentDriver(array $config)
    {
        return new EloquentStatusProvider(
            $config['model'],
            $config['status_column'] ?? 'status',
            $config['user_column'] ?? 'user_id',
            $config['chat_column'] ?? 'chat_id',
        );
    }

    /**
     * Create the database driver.
     *
     * @param  array  $config
     * @return \LaraGram\Auth\Status\DatabaseStatusProvider
     */
    protected function createDatabaseDriver(array $config)
    {
        return new DatabaseStatusProvider(
            $this->container->make('db')->connection($config['connection'] ?? null),
            $config['table'] ?? 'users',
            $config['status_column'] ?? 'status',
            $config['user_column'] ?? 'user_id',
            $config['chat_column'] ?? 'chat_id',
        );
    }

    /**
     * Create the cache driver (redis / memcached / file / array ...).
     *
     * @param  array  $config
     * @return \LaraGram\Auth\Status\CacheStatusProvider
     */
    protected function createCacheDriver(array $config)
    {
        return new CacheStatusProvider(
            $this->container->make('cache')->store($config['store'] ?? null),
            $config['prefix'] ?? 'chat_status',
            $config['ttl'] ?? null,
        );
    }

    /**
     * Get the current user id from the incoming update.
     *
     * @return int|string|null
     */
    protected function currentUserId()
    {
        return user()->id ?? null;
    }

    /**
     * Get the current chat id from the incoming update.
     *
     * @return int|string|null
     */
    protected function currentChatId()
    {
        return chat()->id ?? null;
    }
}
