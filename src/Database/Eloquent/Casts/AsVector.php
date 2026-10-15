<?php

namespace LaraGram\Database\Eloquent\Casts;

use LaraGram\Contracts\Database\Eloquent\Castable;
use LaraGram\Contracts\Database\Eloquent\CastsAttributes;
use LaraGram\Contracts\Database\Query\Expression as ExpressionContract;
use LaraGram\Contracts\Support\Arrayable;
use LaraGram\Database\Query\Expression;
use LaraGram\Database\Query\Grammars\MariaDbGrammar;
use LaraGram\Support\Str;
use InvalidArgumentException;

class AsVector implements Castable
{
    /**
     * Get the caster class to use when casting from / to this cast target.
     *
     * @param  array  $arguments
     * @return \LaraGram\Contracts\Database\Eloquent\CastsAttributes<array<int, float>, \LaraGram\Contracts\Support\Arrayable|array<int, float>>
     */
    public static function castUsing(array $arguments)
    {
        return new class implements CastsAttributes
        {
            public function get($model, $key, $value, $attributes)
            {
                if ($value === null) {
                    return null;
                }

                $grammar = $model->getConnection()->getQueryGrammar();

                // Decode a MariaDB expression assigned to the model before it is persisted...
                if ($value instanceof ExpressionContract) {
                    $value = Str::between($value->getValue($grammar), "('", "')");

                    return array_map(floatval(...), json_decode($value, true, flags: JSON_THROW_ON_ERROR));
                }

                // MariaDB vector columns return little-endian float32 bytes...
                if ($grammar instanceof MariaDbGrammar) {
                    return array_values(unpack('g*', $value));
                }

                // PostgreSQL (pgvector) returns JSON text...
                return array_map(floatval(...), json_decode($value, true, flags: JSON_THROW_ON_ERROR));
            }

            public function set($model, $key, $value, $attributes)
            {
                if ($value === null) {
                    return [$key => null];
                }

                if ($value instanceof Arrayable) {
                    $value = $value->toArray();
                }

                if (! is_array($value)) {
                    throw new InvalidArgumentException(
                        sprintf('The [%s] attribute must be an array of floats or an Arrayable instance.', $key)
                    );
                }

                $vector = json_encode(array_values(array_map(floatval(...), $value)), JSON_THROW_ON_ERROR);

                // MariaDB requires vectors to be converted from JSON text server-side...
                return [
                    $key => $model->getConnection()->getQueryGrammar() instanceof MariaDbGrammar
                        ? new Expression("vec_fromtext('{$vector}')")
                        : $vector,
                ];
            }
        };
    }
}
