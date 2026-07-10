<?php

namespace LaraGram\Database\Eloquent\Casts;

use LaraGram\Contracts\Database\Eloquent\Castable;
use LaraGram\Contracts\Database\Eloquent\CastsAttributes;
use LaraGram\Support\HtmlString;

class AsHtmlString implements Castable
{
    /**
     * Get the caster class to use when casting from / to this cast target.
     *
     * @param  array  $arguments
     * @return \LaraGram\Contracts\Database\Eloquent\CastsAttributes<\LaraGram\Support\HtmlString, string|HtmlString>
     */
    public static function castUsing(array $arguments)
    {
        return new class implements CastsAttributes
        {
            public function get($model, $key, $value, $attributes)
            {
                return isset($value) ? new HtmlString($value) : null;
            }

            public function set($model, $key, $value, $attributes)
            {
                return isset($value) ? (string) $value : null;
            }
        };
    }
}
