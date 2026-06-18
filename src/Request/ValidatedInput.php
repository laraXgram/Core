<?php

namespace LaraGram\Request;

use ArrayAccess;
use Countable;
use IteratorAggregate;
use ArrayIterator;
use Traversable;
use LaraGram\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * A recursive container for validated update data.
 *
 * @implements \ArrayAccess<string, mixed>
 * @implements \IteratorAggregate<string, mixed>
 */
class ValidatedInput implements ArrayAccess, Arrayable, Countable, IteratorAggregate, JsonSerializable
{
    /**
     * The underlying input data.
     *
     * @var array
     */
    protected $input;

    /**
     * Create a new validated input container.
     *
     * @param  array  $input
     * @return void
     */
    public function __construct(array $input = [])
    {
        $this->input = $input;
    }

    /**
     * Wrap a value, recursing into arrays so nested access stays fluent.
     *
     * @param  mixed  $value
     * @return mixed
     */
    protected function wrap($value)
    {
        return is_array($value) ? new static($value) : $value;
    }

    /**
     * Get an item using "dot" notation.
     *
     * @param  string|null  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function get($key = null, $default = null)
    {
        if (is_null($key)) {
            return $this;
        }

        return $this->wrap(data_get($this->input, $key, $default));
    }

    /**
     * Determine if the container holds a given key (dot notation supported).
     *
     * @param  string  $key
     * @return bool
     */
    public function has($key)
    {
        return data_get($this->input, $key) !== null;
    }

    /**
     * Get the raw underlying array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->input;
    }

    /**
     * Get the data which should be JSON serialized.
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->input;
    }

    /**
     * Convert the container to JSON.
     *
     * @param  int  $options
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * Get an iterator for the items.
     *
     * @return \Traversable
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->input);
    }

    /**
     * Count the items.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->input);
    }

    /**
     * Determine if the given offset exists.
     *
     * @param  mixed  $offset
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return array_key_exists($offset, $this->input);
    }

    /**
     * Get the value at the given offset.
     *
     * @param  mixed  $offset
     * @return mixed
     */
    public function offsetGet($offset): mixed
    {
        return $this->wrap($this->input[$offset] ?? null);
    }

    /**
     * Set the value at the given offset.
     *
     * @param  mixed  $offset
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet($offset, $value): void
    {
        if (is_null($offset)) {
            $this->input[] = $value;
        } else {
            $this->input[$offset] = $value;
        }
    }

    /**
     * Unset the value at the given offset.
     *
     * @param  mixed  $offset
     * @return void
     */
    public function offsetUnset($offset): void
    {
        unset($this->input[$offset]);
    }

    /**
     * Dynamically retrieve an attribute as an object.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->wrap($this->input[$key] ?? null);
    }

    /**
     * Dynamically set an attribute.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function __set($key, $value)
    {
        $this->input[$key] = $value;
    }

    /**
     * Determine if an attribute is set.
     *
     * @param  string  $key
     * @return bool
     */
    public function __isset($key)
    {
        return isset($this->input[$key]);
    }

    /**
     * Unset an attribute.
     *
     * @param  string  $key
     * @return void
     */
    public function __unset($key)
    {
        unset($this->input[$key]);
    }

    /**
     * Get the string representation (JSON).
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toJson();
    }
}
