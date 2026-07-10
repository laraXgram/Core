<?php

namespace LaraGram\Database\Eloquent;

use ArrayAccess;
use LaraGram\Contracts\Support\Arrayable;
use InvalidArgumentException;
use LogicException;

/**
 * @implements Arrayable<string, mixed>
 *
 * @internal
 */
class ModelInfo implements Arrayable, ArrayAccess
{
    /**
     * @template TModel of \LaraGram\Database\Eloquent\Model
     *
     * @param  class-string<TModel>  $class  The model's fully-qualified class.
     * @param  string  $database  The database connection name.
     * @param  string  $table  The database table name.
     * @param  class-string|null  $policy  The policy that applies to the model.
     * @param  \LaraGram\Support\Collection<int, array<string, mixed>>  $attributes  The attributes available on the model.
     * @param  \LaraGram\Support\Collection<int, array{name: string, type: string, related: class-string<\LaraGram\Database\Eloquent\Model>}>  $relations  The relations defined on the model.
     * @param  \LaraGram\Support\Collection<int, array{event: string, class: string}>  $events  The events that the model dispatches.
     * @param  \LaraGram\Support\Collection<int, array{event: string, observer: array<int, string>}>  $observers  The observers registered for the model.
     * @param  class-string<\LaraGram\Database\Eloquent\Collection<TModel>>  $collection  The Collection class that collects the models.
     * @param  class-string<\LaraGram\Database\Eloquent\Builder<TModel>>  $builder  The Builder class registered for the model.
     * @param  \LaraGram\Http\Resources\Json\JsonResource|null  $resource  The JSON resource that represents the model.
     */
    public function __construct(
        public $class,
        public $database,
        public $table,
        public $policy,
        public $attributes,
        public $relations,
        public $events,
        public $observers,
        public $collection,
        public $builder,
        public $resource
    ) {
    }

    /**
     * Convert the model info to an array.
     *
     * @return array{
     *     "class": class-string<\LaraGram\Database\Eloquent\Model>,
     *     database: string,
     *     table: string,
     *     policy: class-string|null,
     *     attributes: \LaraGram\Support\Collection<int, array<string, mixed>>,
     *     relations: \LaraGram\Support\Collection<int, array{name: string, type: string, related: class-string<\LaraGram\Database\Eloquent\Model>}>,
     *     events: \LaraGram\Support\Collection<int, array{event: string, class: string}>,
     *     observers: \LaraGram\Support\Collection<int, array{event: string, observer: array<int, string>}>, collection: class-string<\LaraGram\Database\Eloquent\Collection<\LaraGram\Database\Eloquent\Model>>,
     *     builder: class-string<\LaraGram\Database\Eloquent\Builder<\LaraGram\Database\Eloquent\Model>>,
     *     resource: \LaraGram\Http\Resources\Json\JsonResource|null
     * }
     */
    public function toArray()
    {
        return [
            'class' => $this->class,
            'database' => $this->database,
            'table' => $this->table,
            'policy' => $this->policy,
            'attributes' => $this->attributes,
            'relations' => $this->relations,
            'events' => $this->events,
            'observers' => $this->observers,
            'collection' => $this->collection,
            'builder' => $this->builder,
            'resource' => $this->resource,
        ];
    }

    /**
     * Determine if the given offset exists.
     */
    public function offsetExists(mixed $offset): bool
    {
        return property_exists($this, $offset);
    }

    /**
     * Get the value for a given offset.
     *
     * @throws \InvalidArgumentException
     */
    public function offsetGet(mixed $offset): mixed
    {
        return property_exists($this, $offset) ? $this->{$offset} : throw new InvalidArgumentException("Property {$offset} does not exist.");
    }

    /**
     * Set the value at the given offset.
     *
     * @throws \LogicException
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new LogicException(self::class.' may not be mutated using array access.');
    }

    /**
     * Unset the value at the given offset.
     *
     * @throws \LogicException
     */
    public function offsetUnset(mixed $offset): void
    {
        throw new LogicException(self::class.' may not be mutated using array access.');
    }
}
