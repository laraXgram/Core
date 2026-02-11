<?php

namespace LaraGram\Cache;

use LaraGram\Support\Collection;

class Step
{
    /**
     * The cache store implementation.
     *
     * @var \LaraGram\Cache\CacheManager
     */
    protected $cache;

    /**
     * The step key name
     *
     * @var string
     */
    protected $key;

    /**
     * Create a new step manager instance.
     *
     * @param  \LaraGram\Cache\CacheManager  $cache
     * @return void
     */
    public function __construct(CacheManager $cache)
    {
        $this->cache = $cache;
        $this->key = user()->id.":step";
    }

    /**
     * Create and set new step.
     *
     * @param  string $step
     * @param  int|null $ttl
     * @return bool
     */
    public function set(string $step, int $ttl = null): bool
    {
        $this->forget();

        return $this->cache->set($this->key, $step, $ttl);
    }

    /**
     * Get current step.
     *
     * @return mixed
     */
    public function get(): mixed
    {
        return $this->cache->get($this->key);
    }

    /**
     * Clear current step.
     *
     * @return bool
     */
    public function forget(): bool
    {
        return $this->cache->forget($this->key);
    }

    /**
     * Check user has any step.
     *
     * @return bool
     */
    public function hasStep(): bool
    {
        return $this->cache->has($this->key);
    }

    /**
     * Check user has not any step.
     *
     * @return bool
     */
    public function hasNotStep(): bool
    {
        return ! $this->hasStep();
    }

    /**
     * Get and clear current step.
     *
     * @return mixed
     */
    public function pull(): mixed
    {
        return $this->cache->pull($this->key);
    }

    /**
     * Check if the step has a specific value.
     *
     * @param  string $key
     * @return bool
     */
    public function is($key): mixed
    {
        return $this->get() === $key;
    }

    /**
     * Check if the step has not a specific value.
     *
     * @param  string $key
     * @return bool
     */
    public function isNot($key): mixed
    {
        return $this->get() !== $key;
    }

    private function getSequenceKey(): string
    {
        return user()->id . ':sequence';
    }

    /**
     * Get the current sequence data from cache.
     *
     * @return array|null  The sequence structure or null if not started.
     */
    public function getSequence(): ?array
    {
        return $this->cache->get($this->getSequenceKey());
    }

    private function saveSequence(array $data): void
    {
        $this->cache->set($this->getSequenceKey(), $data);
    }

    private function clearSequence(): void
    {
        $this->cache->forget($this->getSequenceKey());
    }

    private function hasNext(array $data): bool
    {
        return $data['current'] < count($data['steps']) - 1;
    }

    private function hasPrevious(array $data): bool
    {
        return $data['current'] > 0;
    }

    /**
     * Start a new step sequence.
     *
     * Stores the sequence in cache and activates the first step.
     *
     * @param  array<int, string>  $sequence
     * @return void
     */
    public function startSequence(array $sequence): void
    {
        if (empty($sequence)) {
            return;
        }

        $data = [
            'steps'   => array_values($sequence),
            'current' => 0,
        ];

        $this->saveSequence($data);

        $this->set($data['steps'][0]);
    }

    /**
     * End the current sequence and clear the active step.
     *
     * @return void
     */
    public function endSequence(): void
    {
        $this->clearSequence();
        $this->forget();
    }

    /**
     * Move to the next step in the sequence.
     *
     * Does nothing if already at the last step
     * or if no sequence exists.
     *
     * @return void
     */
    public function next(): void
    {
        $this->move(1);
    }

    /**
     * Move to the previous step in the sequence.
     *
     * Does nothing if already at the first step
     * or if no sequence exists.
     *
     * @return void
     */
    public function previous(): void
    {
        $this->move(-1);
    }

    private function move(int $direction): void
    {
        $data = $this->getSequence();

        if (!$data) {
            return;
        }

        $newIndex = $data['current'] + $direction;

        if ($newIndex < 0 || $newIndex >= count($data['steps'])) {
            return;
        }

        $data['current'] = $newIndex;

        $this->saveSequence($data);

        $this->set($data['steps'][$newIndex]);
    }

    /**
     * Get the current step from the active sequence.
     *
     * @return string|null
     */
    public function current()
    {
        $data = $this->getSequence();

        return $data
            ? $data['steps'][$data['current']]
            : null;
    }

    /**
     * Determine if the current step is the first in sequence.
     *
     * @return bool
     */
    public function isFirst(): bool
    {
        $data = $this->getSequence();

        return !$data || $data['current'] === 0;
    }

    /**
     * Determine if the current step is the last in sequence.
     *
     * @return bool
     */
    public function isLast(): bool
    {
        $data = $this->getSequence();

        return !$data || $data['current'] === count($data['steps']) - 1;
    }
}
