<?php

namespace LaraGram\Broadcasting\Telegram;

use LaraGram\Container\Container;
use LaraGram\Contracts\Support\Arrayable;
use LaraGram\Contracts\Support\Jsonable;
use JsonSerializable;

class Progress implements Arrayable, Jsonable, JsonSerializable
{
    /**
     * The counters tracked for every broadcast.
     *
     * @var array<int, string>
     */
    public const COUNTERS = ['sent', 'failed', 'unreachable', 'skipped'];

    /**
     * Create a new progress tracker.
     *
     * @param  string  $id
     * @param  \LaraGram\Contracts\Cache\Repository  $cache
     * @param  int  $ttl
     */
    public function __construct(
        public readonly string $id,
        protected $cache,
        protected int $ttl = 604800,
    ) {
        //
    }

    /**
     * Get the progress tracker of a broadcast.
     *
     * @param  string  $id
     * @return static
     */
    public static function for(string $id)
    {
        return new static(
            $id,
            static::store(),
            (int) Container::getInstance()['config']->get('broadcasting.progress.ttl', 604800),
        );
    }

    /**
     * Get the cache store holding broadcast progress.
     *
     * @return \LaraGram\Contracts\Cache\Repository
     */
    public static function store()
    {
        $app = Container::getInstance();

        return $app['cache']->store($app['config']->get('broadcasting.progress.store'));
    }

    /**
     * Get the most recent broadcasts, newest first.
     *
     * @param  int  $limit
     * @return array<int, static>
     */
    public static function recent(int $limit = 20): array
    {
        $ids = array_slice((array) static::store()->get('laragram:broadcast:recent', []), 0, $limit);

        return array_values(array_filter(
            array_map(fn ($id) => static::for($id), $ids),
            fn (Progress $progress) => $progress->exists(),
        ));
    }

    /**
     * Record a broadcast that was queued or scheduled but has not started yet.
     *
     * @param  string  $method
     * @param  string|null  $bot
     * @param  array  $audiences
     * @param  int|null  $scheduledAt
     * @param  array  $reportTo
     * @param  array  $steps  The steps of a recallable broadcast.
     * @param  int|null  $ttl  How long the broadcast is remembered, in seconds.
     * @return void
     */
    public function queue(string $method, ?string $bot, array $audiences, ?int $scheduledAt = null, array $reportTo = [], array $steps = [], ?int $ttl = null): void
    {
        $this->ttl = $ttl ?? $this->ttl;

        $this->reset();

        $this->putMeta([
            'id' => $this->id,
            'method' => $method,
            'bot' => $bot,
            'audiences' => array_values($audiences),
            'status' => $scheduledAt !== null && $scheduledAt > time() ? 'scheduled' : 'queued',
            'total' => null,
            'queued_at' => time(),
            'scheduled_at' => $scheduledAt,
            'started_at' => null,
            'finished_at' => null,
            'report_to' => array_values($reportTo),
            'steps' => array_values($steps),
        ]);

        $this->remember();
    }

    /**
     * Start tracking a broadcast. A cancellation made while it was queued is kept.
     *
     * @param  string  $method
     * @param  string  $bot
     * @param  array  $audiences
     * @param  array  $reportTo
     * @return void
     */
    public function start(string $method, string $bot, array $audiences, array $reportTo = []): void
    {
        $meta = $this->meta();

        if (! in_array($meta['status'] ?? null, ['queued', 'scheduled', 'cancelled'], true)) {
            $this->reset();
        }

        $this->putMeta(array_merge($meta, [
            'id' => $this->id,
            'method' => $method,
            'bot' => $bot,
            'audiences' => array_values($audiences),
            'status' => $this->cancelled() ? 'cancelled' : 'running',
            'total' => null,
            'started_at' => time(),
            'finished_at' => null,
            'report_to' => array_values($reportTo ?: ($meta['report_to'] ?? [])),
        ]));

        $this->remember();
    }

    /**
     * Mark the broadcast as waiting for its delivery window to open.
     *
     * @param  int  $resumesAt
     * @return void
     */
    public function waitUntil(int $resumesAt): void
    {
        $meta = $this->meta();

        if (($meta['status'] ?? null) === 'running' || ($meta['status'] ?? null) === 'waiting') {
            $this->putMeta(array_merge($meta, ['status' => 'waiting', 'resumes_at' => $resumesAt]));
        }
    }

    /**
     * Mark a waiting broadcast as running again.
     *
     * @return void
     */
    public function resume(): void
    {
        $meta = $this->meta();

        if (($meta['status'] ?? null) === 'waiting') {
            $this->putMeta(array_merge($meta, ['status' => 'running', 'resumes_at' => null]));
        }
    }

    /**
     * Reset the counters and flags of the broadcast.
     *
     * @return void
     */
    protected function reset(): void
    {
        foreach (self::COUNTERS as $counter) {
            $this->cache->put($this->key($counter), 0, $this->ttl);
        }

        $this->cache->forget($this->key('cancelled'));
        $this->cache->forget($this->key('completed'));
    }

    /**
     * Add the broadcast to the recent broadcasts index.
     *
     * @return void
     */
    protected function remember(): void
    {
        $ids = array_values(array_diff((array) $this->cache->get('laragram:broadcast:recent', []), [$this->id]));

        array_unshift($ids, $this->id);

        $this->cache->put('laragram:broadcast:recent', array_slice($ids, 0, 100), $this->ttl);
    }

    /**
     * Get the chats the completion report is sent to.
     *
     * @return array<int, int|string>
     */
    public function reportTo(): array
    {
        return (array) ($this->meta()['report_to'] ?? []);
    }

    /**
     * Get the steps of the broadcast, when it was sent as recallable.
     *
     * @return array<int, \LaraGram\Broadcasting\Telegram\Action>
     */
    public function steps(): array
    {
        return array_map(
            fn (array $step) => Action::fromArray($step),
            (array) ($this->meta()['steps'] ?? [])
        );
    }

    /**
     * Get the moment the broadcast is scheduled for, if any.
     *
     * @return int|null
     */
    public function scheduledAt(): ?int
    {
        return $this->meta()['scheduled_at'] ?? null;
    }

    /**
     * Record that every recipient has been counted (and queued).
     *
     * @param  int  $total
     * @return void
     */
    public function seal(int $total): void
    {
        $this->putMeta(array_merge($this->meta(), ['total' => $total]));
    }

    /**
     * Increment a counter.
     *
     * @param  string  $counter
     * @param  int  $by
     * @return void
     */
    public function increment(string $counter, int $by = 1): void
    {
        if ($by > 0) {
            $this->cache->increment($this->key($counter), $by);
        }
    }

    /**
     * Mark the broadcast as completed when every recipient was processed.
     *
     * Returns true only for the single caller that completed it.
     *
     * @return bool
     */
    public function completeIfDone(): bool
    {
        $meta = $this->meta();

        if (($meta['total'] ?? null) === null || $this->processed() < $meta['total']) {
            return false;
        }

        if (! $this->cache->add($this->key('completed'), true, $this->ttl)) {
            return false;
        }

        $this->putMeta(array_merge($meta, [
            'status' => $this->cancelled() ? 'cancelled' : 'finished',
            'finished_at' => time(),
        ]));

        return true;
    }

    /**
     * Cancel the broadcast.
     *
     * @return void
     */
    public function cancel(): void
    {
        $this->cache->put($this->key('cancelled'), true, $this->ttl);

        $meta = $this->meta();

        $this->putMeta(array_merge($meta, [
            'status' => 'cancelled',
            // A broadcast cancelled before it started has nothing left to finish.
            'finished_at' => in_array($meta['status'] ?? null, ['queued', 'scheduled'], true) ? time() : ($meta['finished_at'] ?? null),
        ]));
    }

    /**
     * Determine if the broadcast was cancelled.
     *
     * @return bool
     */
    public function cancelled(): bool
    {
        return (bool) $this->cache->get($this->key('cancelled'), false);
    }

    /**
     * Determine if the broadcast is known.
     *
     * @return bool
     */
    public function exists(): bool
    {
        return $this->meta() !== [];
    }

    /**
     * Determine if every recipient was processed.
     *
     * @return bool
     */
    public function finished(): bool
    {
        return in_array($this->status(), ['finished', 'cancelled'], true)
            && ! is_null($this->meta()['finished_at'] ?? null);
    }

    /**
     * Get the status: queued, scheduled, running, waiting, finished or cancelled.
     *
     * @return string|null
     */
    public function status(): ?string
    {
        return $this->meta()['status'] ?? null;
    }

    /**
     * Get the number of recipients, or null while they are still being counted.
     *
     * @return int|null
     */
    public function total(): ?int
    {
        return $this->meta()['total'] ?? null;
    }

    public function sent(): int
    {
        return (int) $this->cache->get($this->key('sent'), 0);
    }

    public function failed(): int
    {
        return (int) $this->cache->get($this->key('failed'), 0);
    }

    public function unreachable(): int
    {
        return (int) $this->cache->get($this->key('unreachable'), 0);
    }

    public function skipped(): int
    {
        return (int) $this->cache->get($this->key('skipped'), 0);
    }

    /**
     * Get the number of recipients processed so far.
     *
     * @return int
     */
    public function processed(): int
    {
        return $this->sent() + $this->failed() + $this->unreachable() + $this->skipped();
    }

    /**
     * Get the completion percentage (0-100), or null while counting.
     *
     * @return float|null
     */
    public function percentage(): ?float
    {
        $total = $this->total();

        if ($total === null) {
            return null;
        }

        return $total === 0 ? 100.0 : round(min(100, $this->processed() / $total * 100), 2);
    }

    /**
     * Get the progress as an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        $meta = $this->meta();

        return array_merge($meta, [
            'sent' => $this->sent(),
            'failed' => $this->failed(),
            'unreachable' => $this->unreachable(),
            'skipped' => $this->skipped(),
            'processed' => $this->processed(),
            'percentage' => $this->percentage(),
        ]);
    }

    /**
     * Convert the progress to JSON.
     *
     * @param  int  $options
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * Get the JSON serializable form of the progress.
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Get the stored metadata.
     *
     * @return array
     */
    protected function meta(): array
    {
        return (array) $this->cache->get($this->key('meta'), []);
    }

    /**
     * Store the metadata.
     *
     * @param  array  $meta
     * @return void
     */
    protected function putMeta(array $meta): void
    {
        $this->cache->put($this->key('meta'), $meta, $this->ttl);
    }

    /**
     * Get the cache key for a value of this broadcast.
     *
     * @param  string  $name
     * @return string
     */
    protected function key(string $name): string
    {
        return 'laragram:broadcast:'.$this->id.':'.$name;
    }
}
