<?php

namespace LaraGram\Request\Proxy;

use LaraGram\Contracts\Container\Container;

class ProxyManager
{
    /**
     * curl error numbers that mean "this proxy is unreachable" and should
     * trigger a fail-over to the next proxy.
     *
     * @var array<int, int>
     */
    protected const FAILOVER_ERRORS = [
        5,   // CURLE_COULDNT_RESOLVE_PROXY
        6,   // CURLE_COULDNT_RESOLVE_HOST
        7,   // CURLE_COULDNT_CONNECT
        28,  // CURLE_OPERATION_TIMEDOUT
        35,  // CURLE_SSL_CONNECT_ERROR
        52,  // CURLE_GOT_NOTHING
        55,  // CURLE_SEND_ERROR
        56,  // CURLE_RECV_ERROR
        97,  // CURLE_PROXY
    ];

    /**
     * The container instance.
     *
     * @var \LaraGram\Contracts\Container\Container
     */
    protected $app;

    /**
     * The configuration repository.
     *
     * @var \LaraGram\Contracts\Config\Repository
     */
    protected $config;

    /**
     * The loaded pool, keyed by proxy id.
     *
     * @var array<string, \LaraGram\Request\Proxy\Proxy>|null
     */
    protected ?array $pool = null;

    /**
     * The resolved cache repository backing the shared state, if any.
     *
     * @var \LaraGram\Contracts\Cache\Repository|null
     */
    protected $store = null;

    /**
     * In-process fallback state (used when no cache store is configured).
     *
     * @var array<string, mixed>
     */
    protected array $local = [];

    /**
     * Create a new proxy manager.
     *
     * @param  \LaraGram\Contracts\Container\Container  $app
     * @return void
     */
    public function __construct(Container $app)
    {
        $this->app = $app;
        $this->config = $app->make('config');
    }

    /**
     * Determine whether proxying is enabled.
     *
     * @return bool
     */
    public function enabled(): bool
    {
        return (bool) $this->cfg('enabled', false) && $this->all() !== [];
    }

    /**
     * Get every proxy in the pool.
     *
     * @return array<string, \LaraGram\Request\Proxy\Proxy>
     */
    public function all(): array
    {
        if ($this->pool !== null) {
            return $this->pool;
        }

        $this->pool = [];

        foreach ((array) $this->cfg('list', []) as $key => $definition) {
            $name = is_string($key) ? $key : null;

            try {
                $proxy = Proxy::make($definition, $name);
                $this->pool[$proxy->id()] = $proxy;
            } catch (ProxyException) {
                // Skip malformed entries rather than breaking the whole pool.
            }
        }

        return $this->pool;
    }

    /**
     * Add a proxy to the pool at runtime.
     *
     * @param  \LaraGram\Request\Proxy\Proxy|array|string  $proxy
     * @param  string|null                                 $name
     * @return \LaraGram\Request\Proxy\Proxy
     */
    public function add(Proxy|array|string $proxy, ?string $name = null): Proxy
    {
        $proxy = Proxy::make($proxy, $name);

        $this->all();
        $this->pool[$proxy->id()] = $proxy;

        return $proxy;
    }

    /**
     * Remove a proxy from the pool by id.
     *
     * @param  string  $id
     * @return bool
     */
    public function remove(string $id): bool
    {
        $this->all();

        if (! isset($this->pool[$id])) {
            return false;
        }

        unset($this->pool[$id]);
        $this->clearState('down:' . $id);

        if ($this->getState('active') === $id) {
            $this->clearState('active');
        }

        return true;
    }

    /**
     * Get a single proxy by id.
     *
     * @param  string  $id
     * @return \LaraGram\Request\Proxy\Proxy|null
     */
    public function get(string $id): ?Proxy
    {
        return $this->all()[$id] ?? null;
    }

    /**
     * List the pool with per-proxy status information.
     *
     * @return array<int, array>
     */
    public function list(): array
    {
        $active = $this->current();

        return array_values(array_map(function (Proxy $proxy) use ($active) {
            return array_merge($proxy->toArray(), [
                'down'   => $this->isDown($proxy),
                'active' => $active !== null && $active->id() === $proxy->id(),
            ]);
        }, $this->all()));
    }

    /**
     * Get the currently active proxy, skipping any that are marked down.
     *
     * When every proxy is down the down-state is reset so the pool gets a
     * fresh chance instead of the bot going completely dark.
     *
     * @return \LaraGram\Request\Proxy\Proxy|null
     */
    public function current(): ?Proxy
    {
        $pool = $this->all();

        if ($pool === []) {
            return null;
        }

        $activeId = $this->getState('active');

        if ($activeId !== null && isset($pool[$activeId]) && ! $this->isDown($pool[$activeId])) {
            return $pool[$activeId];
        }

        foreach ($this->ordered() as $proxy) {
            if (! $this->isDown($proxy)) {
                $this->setState('active', $proxy->id());

                return $proxy;
            }
        }

        $this->resetDown();
        $first = $this->ordered()[0];
        $this->setState('active', $first->id());

        return $first;
    }

    /**
     * Force a specific proxy to become the active one.
     *
     * @param  string  $id
     * @return \LaraGram\Request\Proxy\Proxy
     *
     * @throws \LaraGram\Request\Proxy\ProxyException
     */
    public function use(string $id): Proxy
    {
        $proxy = $this->get($id);

        if ($proxy === null) {
            throw new ProxyException("Unknown proxy [{$id}].");
        }

        $this->setState('active', $id);
        $this->clearState('down:' . $id);

        return $proxy;
    }

    /**
     * Advance to the next healthy proxy after the current one, marking the
     * current active proxy as down.
     *
     * @return \LaraGram\Request\Proxy\Proxy|null
     */
    public function rotate(): ?Proxy
    {
        if (($active = $this->current()) !== null) {
            $this->markDown($active);
        }

        $this->clearState('active');

        return $this->current();
    }

    /**
     * Mark a proxy as down for the configured cooldown period.
     *
     * @param  \LaraGram\Request\Proxy\Proxy  $proxy
     * @return void
     */
    public function markDown(Proxy $proxy): void
    {
        $ttl = max(1, (int) $this->cfg('retry_after', 60));

        $this->setState('down:' . $proxy->id(), time(), $ttl);
    }

    /**
     * Mark a proxy as healthy again.
     *
     * @param  \LaraGram\Request\Proxy\Proxy  $proxy
     * @return void
     */
    public function markUp(Proxy $proxy): void
    {
        $this->clearState('down:' . $proxy->id());
    }

    /**
     * Determine whether a proxy is currently marked down.
     *
     * @param  \LaraGram\Request\Proxy\Proxy  $proxy
     * @return bool
     */
    public function isDown(Proxy $proxy): bool
    {
        return $this->getState('down:' . $proxy->id()) !== null;
    }

    /**
     * Get the proxies currently marked down.
     *
     * @return array<int, \LaraGram\Request\Proxy\Proxy>
     */
    public function down(): array
    {
        return array_values(array_filter($this->all(), fn (Proxy $p) => $this->isDown($p)));
    }

    /**
     * Get the proxies currently considered healthy.
     *
     * @return array<int, \LaraGram\Request\Proxy\Proxy>
     */
    public function up(): array
    {
        return array_values(array_filter($this->all(), fn (Proxy $p) => ! $this->isDown($p)));
    }

    /**
     * Clear every down mark in the pool.
     *
     * @return void
     */
    public function resetDown(): void
    {
        foreach ($this->all() as $proxy) {
            $this->clearState('down:' . $proxy->id());
        }
    }

    /**
     * Ping a proxy (defaults to the active one) against the health target.
     *
     * @param  \LaraGram\Request\Proxy\Proxy|string|null  $proxy
     * @return float|false  Latency in milliseconds, or false when unreachable.
     */
    public function ping(Proxy|string|null $proxy = null): float|false
    {
        $proxy = match (true) {
            $proxy instanceof Proxy => $proxy,
            is_string($proxy)       => $this->get($proxy),
            default                 => $this->current(),
        };

        if ($proxy === null) {
            return false;
        }

        $target = (string) $this->cfg('health.url', 'https://api.telegram.org');
        $timeout = (int) $this->cfg('health.timeout', $this->cfg('connect_timeout', 5));

        return (new ProxyConnection($timeout, $timeout))->ping($proxy, $target);
    }

    /**
     * Ping every proxy in the pool and update their down-state accordingly.
     *
     * @return array<int, array>  [['id' => .., 'latency' => float|false, 'ok' => bool], ...]
     */
    public function pingAll(): array
    {
        $results = [];

        foreach ($this->all() as $proxy) {
            $latency = $this->ping($proxy);

            if ($latency === false) {
                $this->markDown($proxy);
            } else {
                $this->markUp($proxy);
            }

            $results[] = [
                'id'      => $proxy->id(),
                'proxy'   => $proxy->display(),
                'ok'      => $latency !== false,
                'latency' => $latency,
            ];
        }

        return $results;
    }

    /**
     * A snapshot of the pool's health.
     *
     * @return array
     */
    public function stats(): array
    {
        $active = $this->current();

        return [
            'enabled' => $this->enabled(),
            'total'   => count($this->all()),
            'up'      => count($this->up()),
            'down'    => count($this->down()),
            'active'  => $active?->id(),
            'proxies' => $this->list(),
        ];
    }

    /**
     * Send a Bot-API call through the proxy pool, failing over to the next
     * healthy proxy (up to the configured retry count) when a proxy dies.
     *
     * @param  string  $token
     * @param  string  $apiServer
     * @param  string  $method
     * @param  array   $params
     * @param  bool    $noResponse  Use the fire-and-forget transport (no fail-over).
     * @param  string|null  $forced  Force a specific proxy id for this call.
     * @return mixed
     */
    public function dispatch(string $token, string $apiServer, string $method, array $params, bool $noResponse = false, ?string $forced = null): mixed
    {
        $connection = new ProxyConnection(
            (int) $this->cfg('connect_timeout', 5),
            (int) $this->cfg('timeout', 10),
        );

        if ($forced !== null) {
            $proxy = $this->use($forced);
        } else {
            $proxy = $this->current();
        }

        if ($noResponse) {
            return $connection->fire($proxy, $token, $apiServer, $method, $params);
        }

        $attempts = max(1, (int) $this->cfg('retry', 2) + 1);
        $last = ['ok' => false, 'code' => -1, 'message' => 'No proxy available.'];

        for ($i = 0; $i < $attempts && $proxy !== null; $i++) {
            $result = $connection->send($proxy, $token, $apiServer, $method, $params);
            $last = $result['response'];

            if (! in_array($result['errno'], static::FAILOVER_ERRORS, true)) {
                $this->markUp($proxy);

                return $result['response'];
            }

            $this->markDown($proxy);
            $this->clearState('active');
            $proxy = $forced !== null ? null : $this->current();
        }

        return $last;
    }

    /**
     * The pool ordered per the configured selection strategy.
     *
     * @return array<int, \LaraGram\Request\Proxy\Proxy>
     */
    protected function ordered(): array
    {
        $proxies = array_values($this->all());

        if ($this->cfg('strategy', 'failover') === 'random') {
            shuffle($proxies);

            return $proxies;
        }

        return match ((string) $this->cfg('strategy', 'failover')) {
            'round_robin' => $this->rotateOrder($proxies),
            default       => $proxies, // failover: stable order, first healthy wins
        };
    }

    /**
     * Rotate the pool order by a persisted cursor for round-robin selection.
     *
     * @param  array<int, \LaraGram\Request\Proxy\Proxy>  $proxies
     * @return array<int, \LaraGram\Request\Proxy\Proxy>
     */
    protected function rotateOrder(array $proxies): array
    {
        $count = count($proxies);

        if ($count <= 1) {
            return $proxies;
        }

        $cursor = ((int) $this->getState('cursor', 0)) % $count;
        $this->setState('cursor', $cursor + 1, 3600);

        return array_merge(array_slice($proxies, $cursor), array_slice($proxies, 0, $cursor));
    }

    /**
     * Resolve (and cache) the backing cache repository, or null for local.
     *
     * @return \LaraGram\Contracts\Cache\Repository|null
     */
    protected function store()
    {
        if ($this->store !== null) {
            return $this->store;
        }

        $name = $this->cfg('store');

        if ($name === null || $name === false) {
            return null;
        }

        try {
            return $this->store = $this->app->make('cache')->store($name);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Read a state value (cache-backed when a store is configured).
     *
     * @param  string  $key
     * @param  mixed   $default
     * @return mixed
     */
    protected function getState(string $key, mixed $default = null): mixed
    {
        $store = $this->store();

        if ($store === null) {
            $entry = $this->local[$key] ?? null;

            if ($entry === null) {
                return $default;
            }

            if ($entry['expires'] !== null && $entry['expires'] < time()) {
                unset($this->local[$key]);

                return $default;
            }

            return $entry['value'];
        }

        try {
            return $store->get($this->prefix() . ':' . $key, $default);
        } catch (\Throwable) {
            return $default;
        }
    }

    /**
     * Write a state value with an optional TTL in seconds.
     *
     * @param  string    $key
     * @param  mixed     $value
     * @param  int|null  $ttl
     * @return void
     */
    protected function setState(string $key, mixed $value, ?int $ttl = null): void
    {
        $store = $this->store();

        if ($store === null) {
            $this->local[$key] = [
                'value'   => $value,
                'expires' => $ttl !== null ? time() + $ttl : null,
            ];

            return;
        }

        try {
            $fullKey = $this->prefix() . ':' . $key;
            $ttl !== null ? $store->put($fullKey, $value, $ttl) : $store->forever($fullKey, $value);
        } catch (\Throwable) {
            //
        }
    }

    /**
     * Forget a state value.
     *
     * @param  string  $key
     * @return void
     */
    protected function clearState(string $key): void
    {
        if (($store = $this->store()) === null) {
            unset($this->local[$key]);

            return;
        }

        try {
            $store->forget($this->prefix() . ':' . $key);
        } catch (\Throwable) {
            //
        }
    }

    /**
     * The cache key prefix for proxy state.
     *
     * @return string
     */
    protected function prefix(): string
    {
        return (string) $this->cfg('prefix', 'proxy');
    }

    /**
     * Read a bot.proxy configuration value.
     *
     * @param  string  $key
     * @param  mixed   $default
     * @return mixed
     */
    protected function cfg(string $key, mixed $default = null): mixed
    {
        return $this->config->get('bot.proxy.' . $key, $default);
    }
}
