<?php

namespace LaraGram\Listening;

use LaraGram\Contracts\Listening\UrlListenable;
use LaraGram\Database\Eloquent\ModelNotFoundException;
use LaraGram\Database\Eloquent\SoftDeletes;
use LaraGram\Listening\Exceptions\BackedEnumCaseNotFoundException;
use LaraGram\Support\Reflector;
use LaraGram\Support\Str;

class ImplicitListenBinding
{
    /**
     * Resolve the implicit listen bindings for the given listen.
     *
     * @param  \LaraGram\Container\Container  $container
     * @param  \LaraGram\Listening\Listen  $listen
     * @return void
     *
     * @throws \LaraGram\Database\Eloquent\ModelNotFoundException<\LaraGram\Database\Eloquent\Model>
     * @throws \LaraGram\Listening\Exceptions\BackedEnumCaseNotFoundException
     */
    public static function resolveForListen($container, $listen)
    {
        $parameters = $listen->parameters();

        $listen = static::resolveBackedEnumsForListen($listen, $parameters);

        foreach ($listen->signatureParameters(['subClass' => UrlListenable::class]) as $parameter) {
            if (! $parameterName = static::getParameterName($parameter->getName(), $parameters)) {
                continue;
            }

            $parameterValue = $parameters[$parameterName];

            if ($parameterValue instanceof UrlListenable) {
                continue;
            }

            $instance = $container->make(Reflector::getParameterClassName($parameter));

            $parent = $listen->parentOfParameter($parameterName);

            $listenBindingMethod = $listen->allowsTrashedBindings() && in_array(SoftDeletes::class, class_uses_recursive($instance))
                ? 'resolveSoftDeletableListenBinding'
                : 'resolveListenBinding';

            if ($parent instanceof UrlListenable &&
                ! $listen->preventsScopedBindings() &&
                ($listen->enforcesScopedBindings() || array_key_exists($parameterName, $listen->bindingFields()))) {
                $childListenBindingMethod = $listen->allowsTrashedBindings() && in_array(SoftDeletes::class, class_uses_recursive($instance))
                    ? 'resolveSoftDeletableChildListenBinding'
                    : 'resolveChildListenBinding';

                if (! $model = $parent->{$childListenBindingMethod}(
                    $parameterName, $parameterValue, $listen->bindingFieldFor($parameterName)
                )) {
                    throw (new ModelNotFoundException)->setModel(get_class($instance), [$parameterValue]);
                }
            } elseif (! $model = $instance->{$listenBindingMethod}($parameterValue, $listen->bindingFieldFor($parameterName))) {
                throw (new ModelNotFoundException)->setModel(get_class($instance), [$parameterValue]);
            }

            $listen->setParameter($parameterName, $model);
        }
    }

    /**
     * Resolve the Backed Enums listen bindings for the listen.
     *
     * @param  \LaraGram\Listening\Listen  $listen
     * @param  array  $parameters
     * @return \LaraGram\Listening\Listen
     *
     * @throws \LaraGram\Listening\Exceptions\BackedEnumCaseNotFoundException
     */
    protected static function resolveBackedEnumsForListen($listen, $parameters)
    {
        foreach ($listen->signatureParameters(['backedEnum' => true]) as $parameter) {
            if (! $parameterName = static::getParameterName($parameter->getName(), $parameters)) {
                continue;
            }

            $parameterValue = $parameters[$parameterName];

            if ($parameterValue === null) {
                continue;
            }

            $backedEnumClass = $parameter->getType()?->getName();

            $backedEnum = $parameterValue instanceof $backedEnumClass
                ? $parameterValue
                : $backedEnumClass::tryFrom((string) $parameterValue);

            if (is_null($backedEnum)) {
                throw new BackedEnumCaseNotFoundException($backedEnumClass, $parameterValue);
            }

            $listen->setParameter($parameterName, $backedEnum);
        }

        return $listen;
    }

    /**
     * Return the parameter name if it exists in the given parameters.
     *
     * @param  string  $name
     * @param  array  $parameters
     * @return string|null
     */
    protected static function getParameterName($name, $parameters)
    {
        if (array_key_exists($name, $parameters)) {
            return $name;
        }

        if (array_key_exists($snakedName = Str::snake($name), $parameters)) {
            return $snakedName;
        }
    }
}
