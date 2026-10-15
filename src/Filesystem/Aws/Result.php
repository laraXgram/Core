<?php

namespace LaraGram\Filesystem\Aws;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

class Result implements ArrayAccess, Countable, IteratorAggregate
{
    /**
     * Create a new result instance.
     *
     * @param  array<string, mixed>  $data
     */
    public function __construct(protected array $data = [])
    {
        //
    }

    /**
     * Get a value from the result.
     *
     * @param  string  $key
     * @return mixed
     */
    public function get(string $key): mixed
    {
        return $this->data[$key] ?? null;
    }

    /**
     * Determine if the result contains the given key.
     *
     * @param  string  $key
     * @return bool
     */
    public function hasKey(string $key): bool
    {
        return isset($this->data[$key]);
    }

    /**
     * Get the result as an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->data;
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->data[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->data[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->data[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->data[$offset]);
    }

    public function count(): int
    {
        return count($this->data);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->data);
    }
}
