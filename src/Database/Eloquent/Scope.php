<?php

namespace LaraGram\Database\Eloquent;

/**
 * @template TModel of \LaraGram\Database\Eloquent\Model
 */
interface Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  \LaraGram\Database\Eloquent\Builder<covariant TModel>  $builder
     * @param  TModel  $model
     * @return void
     */
    public function apply(Builder $builder, Model $model);
}
