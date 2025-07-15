<?php

namespace LaraGram\Contracts\Listening;

interface PathListenable
{
    /**
     * Get the value of the model's listen key.
     *
     * @return mixed
     */
    public function getListenKey();

    /**
     * Get the listen key for the model.
     *
     * @return string
     */
    public function getListenKeyName();

    /**
     * Retrieve the model for a bound value.
     *
     * @param  mixed  $value
     * @param  string|null  $field
     * @return \LaraGram\Database\Eloquent\Model|null
     */
    public function resolveListenBinding($value, $field = null);

    /**
     * Retrieve the child model for a bound value.
     *
     * @param  string  $childType
     * @param  mixed  $value
     * @param  string|null  $field
     * @return \LaraGram\Database\Eloquent\Model|null
     */
    public function resolveChildListenBinding($childType, $value, $field);
}
