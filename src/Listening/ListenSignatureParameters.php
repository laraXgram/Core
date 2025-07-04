<?php

namespace LaraGram\Listening;

use LaraGram\Support\Reflector;
use LaraGram\Support\Str;
use ReflectionFunction;
use ReflectionMethod;

class ListenSignatureParameters
{
    /**
     * Extract the listen action's signature parameters.
     *
     * @param  array  $action
     * @param  array  $conditions
     * @return array
     */
    public static function fromAction(array $action, $conditions = [])
    {
        $callback = ListenAction::containsSerializedClosure($action)
            ? unserialize($action['uses'])->getClosure()
            : $action['uses'];

        $parameters = is_string($callback)
            ? static::fromClassMethodString($callback)
            : (new ReflectionFunction($callback))->getParameters();

        return match (true) {
            ! empty($conditions['subClass']) => array_filter($parameters, fn ($p) => Reflector::isParameterSubclassOf($p, $conditions['subClass'])),
            ! empty($conditions['backedEnum']) => array_filter($parameters, fn ($p) => Reflector::isParameterBackedEnumWithStringBackingType($p)),
            default => $parameters,
        };
    }

    /**
     * Get the parameters for the given class / method by string.
     *
     * @param string $uses
     * @return array
     * @throws \ReflectionException
     */
    protected static function fromClassMethodString($uses)
    {
        [$class, $method] = Str::parseCallback($uses);

        if (! method_exists($class, $method) && Reflector::isCallable($class, $method)) {
            return [];
        }

        return (new ReflectionMethod($class, $method))->getParameters();
    }
}
