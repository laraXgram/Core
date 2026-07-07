<?php

namespace LaraGram\Request\AntiFlood;

use LaraGram\Contracts\Container\Container;

class AntiFlood
{
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
     * The resolved cache repository backing the state.
     *
     * @var \LaraGram\Contracts\Cache\Repository|null
     */
    protected $store = null;

    /**
     * In-process state for limits marked shared = false (current request only).
     *
     * @var array<string, array>
     */
    protected array $local = [];

    /**
     * Create a new anti-flood engine.
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
     * Determine whether anti-flood throttling is enabled.
     *
     * @return bool
     */
    public function enabled(): bool
    {
        return (bool) $this->cfg('enabled', false);
    }

    /**
     * Pace an outgoing API call, sleeping only as long as the limits require.
     *
     * @param  string  $connection
     * @param  string  $method
     * @param  array   $params
     * @param  array   $extra  Extra named limits to also enforce (antiFloodWith).
     * @return void
     */
    public function gate(string $connection, string $method, array $params, array $extra = []): void
    {
        if (! $this->enabled()) {
            return;
        }

        $now = microtime(true);
        $delay = 0.0;

        foreach ($this->limits($connection, $params, $extra) as $limit) {
            $delay = max($delay, $this->reserve($limit, $now));
        }

        $this->sleep($delay);
    }

    /**
     * React to a real 429 by pushing the relevant limits' next slot forward.
     *
     * @param  string  $connection
     * @param  string  $method
     * @param  array   $params
     * @param  mixed   $response
     * @param  array   $extra
     * @return void
     */
    public function report(string $connection, string $method, array $params, mixed $response, array $extra = []): void
    {
        if (! $this->enabled() || ! $this->cfg('reactive.enabled', true)) {
            return;
        }

        if (! is_array($response)) {
            return;
        }

        if (($response['ok'] ?? true) !== false || (int) ($response['error_code'] ?? 0) !== 429) {
            return;
        }

        $retry = (float) ($response['parameters']['retry_after'] ?? $this->cfg('reactive.default_cooldown', 1.0));
        $retry += (float) $this->cfg('reactive.cooldown_margin', 0.5);
        $now = microtime(true);

        foreach ($this->limits($connection, $params, $extra) as $limit) {
            $tau = ($limit['burst'] - 1) * $limit['interval'];
            $until = $now + $retry + $tau;
            $per = $limit['per'];

            $this->withState($limit['key'], $limit['shared'], function (array $s) use ($until, $per, $now) {
                if ((float) ($s['tat'] ?? 0.0) < $until) {
                    $s['tat'] = $until;
                }

                $ttl = (int) ceil(max($per, $until - $now)) + 1;

                return [$s, 0.0, $ttl];
            });
        }
    }

    /**
     * Peek how long until a limit's next call would be allowed, without
     * consuming a slot. Useful for broadcast ETA / scheduling.
     *
     * @param  string       $limit       global | private | group | <custom>
     * @param  string|null  $chatId
     * @param  string|null  $connection
     * @return float  Seconds until the next call is free (0 = ready now).
     */
    public function availableIn(string $limit, ?string $chatId = null, ?string $connection = null): float
    {
        if (! $this->enabled()) {
            return 0.0;
        }

        $connection ??= (string) $this->config->get('bot.default', '');
        $spec = $this->resolveLimit($this->prefix() . ':' . $connection, $limit, $chatId);

        if ($spec === null) {
            return 0.0;
        }

        $state = $spec['shared']
            ? (array) $this->store()->get($spec['key'], [])
            : ($this->local[$spec['key']] ?? []);

        $tat = (float) ($state['tat'] ?? 0.0);

        return max(0.0, ($tat - ($spec['burst'] - 1) * $spec['interval']) - microtime(true));
    }

    /**
     * Reserve the next slot for a limit (GCRA) and return the delay needed
     * before the call may proceed.
     *
     * @param  array  $limit  Spec from resolveLimit().
     * @param  float  $now
     * @return float
     */
    protected function reserve(array $limit, float $now): float
    {
        $interval = $limit['interval'];
        $tau = ($limit['burst'] - 1) * $interval;
        $every = $limit['every'];
        $pause = $limit['pause'];
        $per = $limit['per'];

        return $this->withState($limit['key'], $limit['shared'], function (array $s) use ($now, $interval, $tau, $every, $pause, $per) {
            $tat = (float) ($s['tat'] ?? 0.0);
            $n = (int) ($s['n'] ?? 0);

            $delay = max(0.0, ($tat - $tau) - $now);
            $newTat = max($now, $tat) + $interval;
            $n++;

            if ($every > 0 && $pause > 0 && $n % $every === 0) {
                $delay += $pause;
                $newTat += $pause;
            }

            $ttl = (int) ceil(max($per, $newTat - $now)) + 1;

            return [['tat' => $newTat, 'n' => $n], $delay, $ttl];
        });
    }

    /**
     * Read-modify-write a limit's state and return the closure's result.
     *
     * The closure returns [newState, result, ttlSeconds].
     *
     * @param  string    $key
     * @param  bool      $shared
     * @param  \Closure  $fn  fn(array $state): array{0: array, 1: float, 2: int}
     * @return float
     */
    protected function withState(string $key, bool $shared, \Closure $fn): float
    {
        if (! $shared) {
            $res = $fn($this->local[$key] ?? []);
            $this->local[$key] = $res[0];

            return (float) $res[1];
        }

        $store = $this->store();
        $lock = $store->getStore()->lock($key . ':lock', 5);
        $result = 0.0;

        try {
            $lock->block(1, function () use ($store, $key, $fn, &$result) {
                $res = $fn((array) $store->get($key, []));
                $store->put($key, $res[0], $res[2]);
                $result = $res[1];
            });
        } catch (\Throwable) {
            //
        }

        return (float) $result;
    }

    /**
     * Build the [key, interval, burst] tuples for the limits of a call.
     *
     * @param  string  $connection
     * @param  array   $params
     * @param  array   $extra
     * @return array<int, array{0: string, 1: float, 2: float}>
     */
    protected function limits(string $connection, array $params, array $extra = []): array
    {
        $base = $this->prefix() . ':' . $connection;
        $out = [];

        if ($g = $this->resolveLimit($base, 'global', null)) {
            $out[] = $g;
        }

        $chatId = $this->chatId($params);

        if ($extra !== []) {
            foreach ($extra as $name) {
                if ($e = $this->resolveLimit($base, $name, $chatId)) {
                    $out[] = $e;
                }
            }
        } elseif ($chatId !== null && ($c = $this->resolveLimit($base, $this->chatType($chatId), $chatId))) {
            $out[] = $c;
        }

        return $out;
    }

    /**
     * Resolve a named limit to a spec, or null when it is not configured.
     *
     * @param  string       $base
     * @param  string       $name    global | private | group | <custom>
     * @param  string|null  $chatId
     * @return array{key: string, interval: float, burst: float, every: int, pause: float, shared: bool}|null
     */
    protected function resolveLimit(string $base, string $name, ?string $chatId): ?array
    {
        if ($name === 'global') {
            $cfg = (array) $this->cfg('global', []);
            $key = $base . ':global';
        } elseif ($name === 'private' || $name === 'group') {
            $cfg = (array) $this->cfg('chat.' . $name, []);
            $key = $base . ':chat:' . ($chatId ?? 'none');
        } else {
            $cfg = (array) $this->cfg('custom.' . $name, []);
            $key = $base . ':custom:' . $name;
        }

        $rate = (float) ($cfg['rate'] ?? 1);
        $per = (float) ($cfg['per'] ?? 1);

        if ($rate <= 0 || $per <= 0) {
            return null;
        }

        return [
            'key' => $key,
            'per' => $per,
            'interval' => $per / $rate,
            'burst' => max(1.0, (float) ($cfg['burst'] ?? 0)),
            'every' => max(0, (int) ($cfg['every'] ?? 0)),
            'pause' => max(0.0, (float) ($cfg['pause'] ?? 0)),
            'shared' => (bool) ($cfg['shared'] ?? true),
        ];
    }

    /**
     * Extract the target chat identifier from a call's parameters.
     *
     * @param  array  $params
     * @return string|null
     */
    protected function chatId(array $params): ?string
    {
        $chatId = $params['chat_id'] ?? null;

        if ($chatId === null || $chatId === '') {
            return null;
        }

        return (string) $chatId;
    }

    /**
     * Classify a chat id as a private chat or a group/channel.
     *
     * @param  string  $chatId
     * @return string  "private" | "group"
     */
    protected function chatType(string $chatId): string
    {
        if (! is_numeric($chatId)) {
            return 'group';
        }

        return ((int) $chatId) < 0 ? 'group' : 'private';
    }

    /**
     * Sleep for the given number of seconds (coroutine-aware).
     *
     * @param  float  $seconds
     * @return void
     */
    protected function sleep(float $seconds): void
    {
        if ($seconds <= 0) {
            return;
        }

        $cap = (float) $this->cfg('sleep.max_delay', 5.0);
        if ($cap > 0 && $seconds > $cap) {
            $seconds = $cap;
        }

        $driver = $this->cfg('sleep.driver', 'auto');
        $coroutine = $driver === 'coroutine'
            || ($driver === 'auto' && $this->inSwooleCoroutine());

        if ($coroutine) {
            \Swoole\Coroutine::sleep($seconds);
            return;
        }

        usleep((int) round($seconds * 1_000_000));
    }

    /**
     * Determine whether we are inside an active Swoole coroutine.
     *
     * @return bool
     */
    protected function inSwooleCoroutine(): bool
    {
        return extension_loaded('swoole')
            && class_exists(\Swoole\Coroutine::class)
            && \Swoole\Coroutine::getCid() > 0;
    }

    /**
     * Resolve (and cache) the backing cache repository.
     *
     * @return \LaraGram\Contracts\Cache\Repository
     */
    protected function store()
    {
        return $this->store ??= $this->app->make('cache')->store($this->cfg('store'));
    }

    /**
     * Get the configured cache key prefix.
     *
     * @return string
     */
    protected function prefix(): string
    {
        return (string) $this->cfg('prefix', 'antiflood');
    }

    /**
     * Read an anti_flood configuration value.
     *
     * @param  string  $key
     * @param  mixed   $default
     * @return mixed
     */
    protected function cfg(string $key, mixed $default = null): mixed
    {
        return $this->config->get('bot.anti_flood.' . $key, $default);
    }
}
