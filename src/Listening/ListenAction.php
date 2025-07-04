<?php

namespace LaraGram\Listening;

use LaraGram\Support\Arr;
use LaraGram\Support\Reflector;
use LaraGram\Support\Str;
use LogicException;
use UnexpectedValueException;

class ListenAction
{
    /**
     * Parse the given action into an array.
     *
     * @param  string  $pattern
     * @param  mixed  $action
     * @return array
     */
    public static function parse($pattern, $action)
    {
        if (is_null($action)) {
            return static::missingAction($pattern);
        }
        
        if (Reflector::isCallable($action, true)) {
            return ! is_array($action) ? ['uses' => $action] : [
                'uses' => $action[0].'@'.$action[1],
                'controller' => $action[0].'@'.$action[1],
            ];
        }
        
        elseif (! isset($action['uses'])) {
            $action['uses'] = static::findCallable($action);
        }

        if (! static::containsSerializedClosure($action) && is_string($action['uses']) && ! str_contains($action['uses'], '@')) {
            $action['uses'] = static::makeInvokable($action['uses']);
        }

        return $action;
    }

    /**
     * Get an action for a listen that has no action.
     *
     * @param  string  $pattern
     * @return array
     *
     * @throws \LogicException
     */
    protected static function missingAction($pattern)
    {
        return ['uses' => function () use ($pattern) {
            throw new LogicException("Listen for [{$pattern}] has no action.");
        }];
    }

    /**
     * Find the callable in an action array.
     *
     * @param  array  $action
     * @return callable
     */
    protected static function findCallable(array $action)
    {
        return Arr::first($action, function ($value, $key) {
            return Reflector::isCallable($value) && is_numeric($key);
        });
    }

    /**
     * Make an action for an invokable controller.
     *
     * @param  string  $action
     * @return string
     *
     * @throws \UnexpectedValueException
     */
    protected static function makeInvokable($action)
    {
        if (! method_exists($action, '__invoke')) {
            throw new UnexpectedValueException("Invalid listen action: [{$action}].");
        }

        return $action.'@__invoke';
    }

    /**
     * Determine if the given array actions contain a serialized Closure.
     *
     * @param  array  $action
     * @return bool
     */
    public static function containsSerializedClosure(array $action)
    {
        return is_string($action['uses']) && Str::startsWith($action['uses'], [
                'O:56:"LaraGram\\Support\\SerializableClosure\\SerializableClosure',
                'O:64:"LaraGram\\Support\\SerializableClosure\\UnsignedSerializableClosure',
            ]) !== false;
    }
}
