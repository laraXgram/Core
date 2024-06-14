<?php

namespace LaraGram\Support\Facades;

abstract class Facade
{
    protected static $app;

    public static function setFacadeApplication($app)
    {
        static::$app = $app;
    }

    protected static function getFacadeRoot()
    {
        return static::$app->make(static::getFacadeAccessor());
    }

    protected static function getFacadeAccessor()
    {
        throw new \RuntimeException('Facade does not implement getFacadeAccessor method.');
    }

    public static function __callStatic($method, $args)
    {
        $instance = static::getFacadeRoot();

        if (!$instance) {
            throw new \RuntimeException('A facade root has not been set.');
        }

        return $instance->$method(...$args);
    }
}