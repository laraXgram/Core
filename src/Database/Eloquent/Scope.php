<?php

namespace LaraGram\Database\Eloquent;

interface Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  \LaraGram\Database\Eloquent\Builder  $builder
     * @param  \LaraGram\Database\Eloquent\Model  $model
     * @return void
     */
    public function apply(Builder $builder, Model $model);
}
