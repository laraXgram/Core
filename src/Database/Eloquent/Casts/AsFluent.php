<?php

namespace LaraGram\Database\Eloquent\Casts;

use LaraGram\Contracts\Database\Eloquent\Castable;
use LaraGram\Contracts\Database\Eloquent\CastsAttributes;
use LaraGram\Support\Fluent;

class AsFluent implements Castable
{
    /**
     * Get the caster class to use when casting from / to this cast target.
     *
     * @param  array  $arguments
     * @return \LaraGram\Contracts\Database\Eloquent\CastsAttributes<\LaraGram\Support\Fluent, string>
     */
    public static function castUsing(array $arguments)
    {
        return new class implements CastsAttributes
        {
            public function get($model, $key, $value, $attributes)
            {
                return isset($value) ? new Fluent(Json::decode($value)) : null;
            }

            public function set($model, $key, $value, $attributes)
            {
                return isset($value) ? [$key => Json::encode($value)] : null;
            }
        };
    }
}
