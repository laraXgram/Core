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

        foreach ($this->limits($connection, $params, $extra) as [$key, $interval, $burst]) {
            $delay = max($delay, $this->reserve($key, $interval, $burst, $now));
        }

        $this->sleep($delay);
    }

    /**
     * React to a real 429 by pushing the relevant limits' next slot forward.
     *
     * Best-effort: in no-response transports there is nothing to inspect, so
     * this does nothing and the proactive pacing remains the guarantee.
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
            return; // no_response_curl / non-array transport — nothing to read.
        }

        if (($response['ok'] ?? true) !== false || (int) ($response['error_code'] ?? 0) !== 429) {
            return;
        }

        $retry = (float) ($response['parameters']['retry_after'] ?? $this->cfg('reactive.default_cooldown', 1.0));
        $retry += (float) $this->cfg('reactive.cooldown_margin', 0.5);
        $now = microtime(true);

        foreach ($this->limits($connection, $params, $extra) as [$key, $interval, $burst]) {
            $tau = ($burst - 1) * $interval;
            $this->mutate($key, function (float $tat) use ($now, $retry, $tau) {
                return max($tat, $now + $retry + $tau);
            }, (int) ceil($retry + $tau) + 1);
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
        $tuple = $this->resolveLimit($this->prefix() . ':' . $connection, $limit, $chatId);

        if ($tuple === null) {
            return 0.0;
        }

        [$key, $interval, $burst] = $tuple;
        $tat = (float) $this->store()->get($key, 0.0);

        return max(0.0, ($tat - ($burst - 1) * $interval) - microtime(true));
    }

    /**
     * Reserve the next slot for a limit (GCRA, under a lock) and return the
     * delay needed before the call may proceed.
     *
     * @param  string  $key
     * @param  float   $interval  Emission interval (per / rate).
     * @param  float   $burst     Bucket capacity (calls allowed back-to-back).
     * @param  float   $now
     * @return float
     */
    protected function reserve(string $key, float $interval, float $burst, float $now): float
    {
        $tau = ($burst - 1) * $interval;
        $ttl = (int) ceil($tau + $interval) + 1;
        $delay = 0.0;

        $this->mutate($key, function (float $tat) use ($now, $interval, $tau, &$delay) {
            $delay = max(0.0, ($tat - $tau) - $now);

            return max($now, $tat) + $interval;
        }, $ttl);

        return $delay;
    }

    /**
     * Atomically read-modify-write a TAT value under a per-key lock.
     *
     * The lock makes the reservation correct across concurrent processes. It is
     * held only for the read+write; failure to acquire never blocks the actual
     * API call (anti-flood must never break sending).
     *
     * @param  string    $key
     * @param  \Closure  $mutator  fn(float $tat): float
     * @param  int       $ttl
     * @return void
     */
    protected function mutate(string $key, \Closure $mutator, int $ttl): void
    {
        $store = $this->store();
        $lock = $store->getStore()->lock($key . ':lock', 5);

        try {
            $lock->block(1, function () use ($store, $key, $mutator, $ttl) {
                $tat = (float) $store->get($key, 0.0);
                $store->put($key, $mutator($tat), $ttl);
            });
        } catch (\Throwable) {
            // Could not coordinate (lock timeout / store error) — skip silently.
        }
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
     * Resolve a named limit to its [key, interval, burst] tuple, or null.
     *
     * @param  string       $base
     * @param  string       $name    global | private | group | <custom>
     * @param  string|null  $chatId
     * @return array{0: string, 1: float, 2: float}|null
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

        $rate = (float) ($cfg['rate'] ?? 0);
        $per = (float) ($cfg['per'] ?? 1);

        if ($rate <= 0 || $per <= 0) {
            return null;
        }

        $burst = max(1.0, (float) ($cfg['burst'] ?? ($rate * $per)));

        return [$key, $per / $rate, $burst];
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
            return 'group'; // @username — channel or group
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
