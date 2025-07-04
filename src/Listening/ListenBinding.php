<?php

namespace LaraGram\Listening;

use Closure;
use LaraGram\Database\Eloquent\ModelNotFoundException;
use LaraGram\Database\Eloquent\SoftDeletes;
use LaraGram\Support\Str;

class ListenBinding
{
    /**
     * Create a Listen model binding for a given callback.
     *
     * @param  \LaraGram\Container\Container  $container
     * @param  \Closure|string  $binder
     * @return \Closure
     */
    public static function forCallback($container, $binder)
    {
        if (is_string($binder)) {
            return static::createClassBinding($container, $binder);
        }

        return $binder;
    }

    /**
     * Create a class based binding using the IoC container.
     *
     * @param  \LaraGram\Container\Container  $container
     * @param  string  $binding
     * @return \Closure
     */
    protected static function createClassBinding($container, $binding)
    {
        return function ($value, $listen) use ($container, $binding) {
            // If the binding has an @ sign, we will assume it's being used to delimit
            // the class name from the bind method name. This allows for bindings
            // to run multiple bind methods in a single class for convenience.
            [$class, $method] = Str::parseCallback($binding, 'bind');

            $callable = [$container->make($class), $method];

            return $callable($value, $listen);
        };
    }

    /**
     * Create a Listen model binding for a model.
     *
     * @param  \LaraGram\Container\Container  $container
     * @param  string  $class
     * @param  \Closure|null  $callback
     * @return \Closure
     *
     * @throws \LaraGram\Database\Eloquent\ModelNotFoundException<\LaraGram\Database\Eloquent\Model>
     */
    public static function forModel($container, $class, $callback = null)
    {
        return function ($value, $listen = null) use ($container, $class, $callback) {
            if (is_null($value)) {
                return;
            }

            // For model binders, we will attempt to retrieve the models using the first
            // method on the model instance. If we cannot retrieve the models we'll
            // throw a not found exception otherwise we will return the instance.
            $instance = $container->make($class);

            $listenBindingMethod = $listen?->allowsTrashedBindings() && in_array(SoftDeletes::class, class_uses_recursive($instance))
                ? 'resolveSoftDeletableListenBinding'
                : 'resolveListenBinding';

            if ($model = $instance->{$listenBindingMethod}($value)) {
                return $model;
            }

            // If a callback was supplied to the method we will call that to determine
            // what we should do when the model is not found. This just gives these
            // developer a little greater flexibility to decide what will happen.
            if ($callback instanceof Closure) {
                return $callback($value);
            }

            throw (new ModelNotFoundException)->setModel($class);
        };
    }
}
