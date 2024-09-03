<?php

namespace LaraGram\Support\Trait;

use BadMethodCallException;
use Closure;

trait Macroable
{
    protected static array $macros = [];

    public static function macro($name, callable $macro): void
    {
        static::$macros[$name] = $macro;
    }

    public static function hasMacro($name): bool
    {
        return isset(static::$macros[$name]);
    }

    public function __call($method, $parameters)
    {
        if (! static::hasMacro($method)) {
            throw new BadMethodCallException("Method {$method} does not exist.");
        }

        if (static::$macros[$method] instanceof Closure) {
            return call_user_func_array(static::$macros[$method]->bindTo($this, static::class), $parameters);
        }

        return call_user_func_array(static::$macros[$method], $parameters);
    }

    public static function __callStatic($method, $parameters)
    {
        if (! static::hasMacro($method)) {
            throw new BadMethodCallException("Method {$method} does not exist.");
        }

        return call_user_func_array(static::$macros[$method], $parameters);
    }
}