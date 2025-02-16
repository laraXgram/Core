<?php

namespace LaraGram\Contracts\Database\Eloquent;

interface DeviatesCastableAttributes
{
    /**
     * Increment the attribute.
     *
     * @param  \LaraGram\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return mixed
     */
    public function increment($model, string $key, $value, array $attributes);

    /**
     * Decrement the attribute.
     *
     * @param  \LaraGram\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return mixed
     */
    public function decrement($model, string $key, $value, array $attributes);
}
