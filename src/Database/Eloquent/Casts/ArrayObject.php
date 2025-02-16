<?php

namespace LaraGram\Database\Eloquent\Casts;

use ArrayObject as BaseArrayObject;
use LaraGram\Contracts\Support\Arrayable;
use LaraGram\Support\Collection;
use JsonSerializable;

/**
 * @template TKey of array-key
 * @template TItem
 *
 * @extends  \ArrayObject<TKey, TItem>
 */
class ArrayObject extends BaseArrayObject implements Arrayable, JsonSerializable
{
    /**
     * Get a collection containing the underlying array.
     *
     * @return \LaraGram\Support\Collection
     */
    public function collect()
    {
        return new Collection($this->getArrayCopy());
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->getArrayCopy();
    }

    /**
     * Get the array that should be JSON serialized.
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->getArrayCopy();
    }
}
