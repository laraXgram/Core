<?php

namespace LaraGram\Database\Eloquent\Concerns;

use LaraGram\Database\Eloquent\ModelNotFoundException;

trait HasUniqueStringIds
{
    /**
     * Generate a new unique key for the model.
     *
     * @return mixed
     */
    abstract public function newUniqueId();

    /**
     * Determine if given key is valid.
     *
     * @param  mixed  $value
     * @return bool
     */
    abstract protected function isValidUniqueId($value): bool;

    /**
     * Initialize the trait.
     *
     * @return void
     */
    public function initializeHasUniqueStringIds()
    {
        $this->usesUniqueIds = true;
    }

    /**
     * Get the columns that should receive a unique identifier.
     *
     * @return array
     */
    public function uniqueIds()
    {
        return $this->usesUniqueIds() ? [$this->getKeyName()] : parent::uniqueIds();
    }

    /**
     * Retrieve the model for a bound value.
     *
     * @param  \LaraGram\Database\Eloquent\Model|\LaraGram\Database\Eloquent\Relations\Relation<*, *, *>  $query
     * @param  mixed  $value
     * @param  string|null  $field
     * @return \LaraGram\Contracts\Database\Eloquent\Builder
     *
     * @throws \LaraGram\Database\Eloquent\ModelNotFoundException
     */
    public function resolveRouteBindingQuery($query, $value, $field = null)
    {
        if ($field && in_array($field, $this->uniqueIds()) && ! $this->isValidUniqueId($value)) {
            $this->handleInvalidUniqueId($value, $field);
        }

        if (! $field && in_array($this->getRouteKeyName(), $this->uniqueIds()) && ! $this->isValidUniqueId($value)) {
            $this->handleInvalidUniqueId($value, $field);
        }

        return parent::resolveRouteBindingQuery($query, $value, $field);
    }

    /**
     * Get the auto-incrementing key type.
     *
     * @return string
     */
    public function getKeyType()
    {
        if (in_array($this->getKeyName(), $this->uniqueIds())) {
            return 'string';
        }

        return parent::getKeyType();
    }

    /**
     * Get the value indicating whether the IDs are incrementing.
     *
     * @return bool
     */
    public function getIncrementing()
    {
        if (in_array($this->getKeyName(), $this->uniqueIds())) {
            return false;
        }

        return parent::getIncrementing();
    }

    /**
     * Throw an exception for the given invalid unique ID.
     *
     * @param  mixed  $value
     * @param  string|null  $field
     * @return never
     *
     * @throws \LaraGram\Database\Eloquent\ModelNotFoundException
     */
    protected function handleInvalidUniqueId($value, $field)
    {
        throw (new ModelNotFoundException)->setModel(get_class($this), $value);
    }
}
