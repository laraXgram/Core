<?php

namespace LaraGram\Database\Eloquent;

use LaraGram\Database\Eloquent\Attributes\CollectedBy;
use ReflectionClass;

/**
 * @template TCollection of \LaraGram\Database\Eloquent\Collection
 */
trait HasCollection
{
    /**
     * The resolved collection class names by model.
     *
     * @var array<class-string<static>, class-string<TCollection>>
     */
    protected static array $resolvedCollectionClasses = [];

    /**
     * Create a new Eloquent Collection instance.
     *
     * @param  array<array-key, \LaraGram\Database\Eloquent\Model>  $models
     * @return TCollection
     */
    public function newCollection(array $models = [])
    {
        static::$resolvedCollectionClasses[static::class] ??= ($this->resolveCollectionFromAttribute() ?? static::$collectionClass);

        return new static::$resolvedCollectionClasses[static::class]($models);
    }

    /**
     * Resolve the collection class name from the CollectedBy attribute.
     *
     * @return class-string<TCollection>|null
     */
    public function resolveCollectionFromAttribute()
    {
        $reflectionClass = new ReflectionClass(static::class);

        $attributes = $reflectionClass->getAttributes(CollectedBy::class);

        if (! isset($attributes[0]) || ! isset($attributes[0]->getArguments()[0])) {
            return;
        }

        return $attributes[0]->getArguments()[0];
    }
}
