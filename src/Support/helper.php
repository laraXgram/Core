<?php

use LaraGram\Database\Eloquent\Model;
use LaraGram\Support\Arr;
use LaraGram\Support\Collection;
use LaraGram\Support\Fluent;
use LaraGram\Support\HigherOrderTapProxy;
use LaraGram\Support\Once;
use LaraGram\Support\Onceable;
use LaraGram\Support\Sleep;
use LaraGram\Support\Str;
use LaraGram\Support\Stringable as SupportStringable;


if (! function_exists('append_config')) {
    /**
     * Assign high numeric IDs to a config item to force appending.
     *
     * @param  array  $array
     * @return array
     */
    function append_config(array $array)
    {
        $start = 9999;

        foreach ($array as $key => $value) {
            if (is_numeric($key)) {
                $start++;

                $array[$start] = Arr::pull($array, $key);
            }
        }

        return $array;
    }
}

if (! function_exists('blank')) {
    /**
     * Determine if the given value is "blank".
     *
     * @phpstan-assert-if-false !=null|'' $value
     *
     * @phpstan-assert-if-true !=numeric|bool $value
     *
     * @param  mixed  $value
     * @return bool
     */
    function blank($value)
    {
        if (is_null($value)) {
            return true;
        }

        if (is_string($value)) {
            return trim($value) === '';
        }

        if (is_numeric($value) || is_bool($value)) {
            return false;
        }

        if ($value instanceof Model) {
            return false;
        }

        if ($value instanceof Countable) {
            return count($value) === 0;
        }

        if ($value instanceof Stringable) {
            return trim((string) $value) === '';
        }

        return empty($value);
    }
}

if (! function_exists('class_basename')) {
    /**
     * Get the class "basename" of the given object / class.
     *
     * @param  string|object  $class
     * @return string
     */
    function class_basename($class)
    {
        $class = is_object($class) ? get_class($class) : $class;

        return basename(str_replace('\\', '/', $class));
    }
}

if (! function_exists('filled')) {
    /**
     * Determine if a value is "filled".
     *
     * @phpstan-assert-if-true !=null|'' $value
     *
     * @phpstan-assert-if-false !=numeric|bool $value
     *
     * @param  mixed  $value
     * @return bool
     */
    function filled($value)
    {
        return ! blank($value);
    }
}

if (! function_exists('fluent')) {
    /**
     * Create a Fluent object from the given value.
     *
     * @param  object|array  $value
     * @return Fluent
     */
    function fluent($value)
    {
        return new Fluent($value);
    }
}

if (! function_exists('literal')) {
    /**
     * Return a new literal or anonymous object using named arguments.
     *
     * @return \stdClass
     */
    function literal(...$arguments)
    {
        if (count($arguments) === 1 && array_is_list($arguments)) {
            return $arguments[0];
        }

        return (object) $arguments;
    }
}

if (! function_exists('object_get')) {
    /**
     * Get an item from an object using "dot" notation.
     *
     * @template TValue of object
     *
     * @param  TValue  $object
     * @param  string|null  $key
     * @param  mixed  $default
     * @return ($key is empty ? TValue : mixed)
     */
    function object_get($object, $key, $default = null)
    {
        if (is_null($key) || trim($key) === '') {
            return $object;
        }

        foreach (explode('.', $key) as $segment) {
            if (! is_object($object) || ! isset($object->{$segment})) {
                return value($default);
            }

            $object = $object->{$segment};
        }

        return $object;
    }
}

if (! function_exists('class_uses_recursive')) {
    /**
     * Returns all traits used by a class, its parent classes and trait of their traits.
     *
     * @param  object|string  $class
     * @return array
     */
    function class_uses_recursive($class)
    {
        if (is_object($class)) {
            $class = get_class($class);
        }

        $results = [];

        foreach (array_reverse(class_parents($class) ?: []) + [$class => $class] as $class) {
            $results += trait_uses_recursive($class);
        }

        return array_unique($results);
    }
}

if (! function_exists('once')) {
    /**
     * Ensures a callable is only called once, and returns the result on subsequent calls.
     *
     * @template  TReturnType
     *
     * @param  callable(): TReturnType  $callback
     * @return TReturnType
     */
    function once(callable $callback)
    {
        $onceable = Onceable::tryFromTrace(
            debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2),
            $callback,
        );

        return $onceable ? Once::instance()->value($onceable) : call_user_func($callback);
    }
}

if (! function_exists('preg_replace_array')) {
    /**
     * Replace a given pattern with each value in the array in sequentially.
     *
     * @param  string  $pattern
     * @param  array  $replacements
     * @param  string  $subject
     * @return string
     */
    function preg_replace_array($pattern, array $replacements, $subject)
    {
        return preg_replace_callback($pattern, function () use (&$replacements) {
            foreach ($replacements as $value) {
                return array_shift($replacements);
            }
        }, $subject);
    }
}

if (! function_exists('retry')) {
    /**
     * Retry an operation a given number of times.
     *
     * @template TValue
     *
     * @param  int|array<int, int>  $times
     * @param  callable(int): TValue  $callback
     * @param  int|\Closure(int, \Throwable): int  $sleepMilliseconds
     * @param  (callable(\Throwable): bool)|null  $when
     * @return TValue
     *
     * @throws \Throwable
     */
    function retry($times, callable $callback, $sleepMilliseconds = 0, $when = null)
    {
        $attempts = 0;

        $backoff = [];

        if (is_array($times)) {
            $backoff = $times;

            $times = count($times) + 1;
        }

        beginning:
        $attempts++;
        $times--;

        try {
            return $callback($attempts);
        } catch (Throwable $e) {
            if ($times < 1 || ($when && ! $when($e))) {
                throw $e;
            }

            $sleepMilliseconds = $backoff[$attempts - 1] ?? $sleepMilliseconds;

            if ($sleepMilliseconds) {
                Sleep::usleep(value($sleepMilliseconds, $attempts, $e) * 1000);
            }

            goto beginning;
        }
    }
}

if (! function_exists('str')) {
    /**
     * Get a new stringable object from the given string.
     *
     * @param  string|null  $string
     * @return ($string is null ? object : \LaraGram\Support\Stringable)
     */
    function str($string = null)
    {
        if (func_num_args() === 0) {
            return new class
            {
                public function __call($method, $parameters)
                {
                    return Str::$method(...$parameters);
                }

                public function __toString()
                {
                    return '';
                }
            };
        }

        return new SupportStringable($string);
    }
}

if (! function_exists('tap')) {
    /**
     * Call the given Closure with the given value then return the value.
     *
     * @template TValue
     *
     * @param  TValue  $value
     * @param  (callable(TValue): mixed)|null  $callback
     * @return HigherOrderTapProxy
     */
    function tap($value, $callback = null)
    {
        if (is_null($callback)) {
            return new HigherOrderTapProxy($value);
        }

        $callback($value);

        return $value;
    }
}

if (! function_exists('collect')) {
    /**
     * Create a collection from the given value.
     *
     * @template TKey of array-key
     * @template TValue
     *
     * @param  \LaraGram\Contracts\Support\Arrayable<TKey, TValue>|iterable<TKey, TValue>|null  $value
     * @return \LaraGram\Support\Collection<TKey, TValue>
     */
    function collect($value = [])
    {
        return new Collection($value);
    }
}

if (! function_exists('throw_if')) {
    /**
     * Throw the given exception if the given condition is true.
     *
     * @template TValue
     * @template TException of \Throwable
     *
     * @param  TValue  $condition
     * @param  TException|class-string<TException>|string  $exception
     * @param  mixed  ...$parameters
     * @return ($condition is true ? never : ($condition is non-empty-mixed ? never : TValue))
     *
     * @throws TException
     */
    function throw_if($condition, $exception = 'RuntimeException', ...$parameters)
    {
        if ($condition) {
            if (is_string($exception) && class_exists($exception)) {
                $exception = new $exception(...$parameters);
            }

            throw is_string($exception) ? new RuntimeException($exception) : $exception;
        }

        return $condition;
    }
}

if (! function_exists('throw_unless')) {
    /**
     * Throw the given exception unless the given condition is true.
     *
     * @template TValue
     * @template TException of \Throwable
     *
     * @param  TValue  $condition
     * @param  TException|class-string<TException>|string  $exception
     * @param  mixed  ...$parameters
     * @return ($condition is false ? never : ($condition is non-empty-mixed ? TValue : never))
     *
     * @throws TException
     */
    function throw_unless($condition, $exception = 'RuntimeException', ...$parameters)
    {
        throw_if(! $condition, $exception, ...$parameters);

        return $condition;
    }
}

if (! function_exists('trait_uses_recursive')) {
    /**
     * Returns all traits used by a trait and its traits.
     *
     * @param  object|string  $trait
     * @return array
     */
    function trait_uses_recursive($trait)
    {
        $traits = class_uses($trait) ?: [];

        foreach ($traits as $trait) {
            $traits += trait_uses_recursive($trait);
        }

        return $traits;
    }
}

if (! function_exists('transform')) {
    /**
     * Transform the given value if it is present.
     *
     * @template TValue
     * @template TReturn
     * @template TDefault
     *
     * @param  TValue  $value
     * @param  callable(TValue): TReturn  $callback
     * @param  TDefault|callable(TValue): TDefault  $default
     * @return ($value is empty ? TDefault : TReturn)
     */
    function transform($value, callable $callback, $default = null)
    {
        if (filled($value)) {
            return $callback($value);
        }

        if (is_callable($default)) {
            return $default($value);
        }

        return $default;
    }
}

if (! function_exists('data_fill')) {
    /**
     * Fill in data where it's missing.
     *
     * @param  mixed  $target
     * @param  string|array  $key
     * @param  mixed  $value
     * @return mixed
     */
    function data_fill(&$target, $key, $value)
    {
        return data_set($target, $key, $value, false);
    }
}

if (! function_exists('data_get')) {
    /**
     * Get an item from an array or object using "dot" notation.
     *
     * @param  mixed  $target
     * @param  string|array|int|null  $key
     * @param  mixed  $default
     * @return mixed
     */
    function data_get($target, $key, $default = null)
    {
        if (is_null($key)) {
            return $target;
        }

        $key = is_array($key) ? $key : explode('.', $key);

        foreach ($key as $i => $segment) {
            unset($key[$i]);

            if (is_null($segment)) {
                return $target;
            }

            if ($segment === '*') {
                if ($target instanceof Collection) {
                    $target = $target->all();
                } elseif (! is_iterable($target)) {
                    return value($default);
                }

                $result = [];

                foreach ($target as $item) {
                    $result[] = data_get($item, $key);
                }

                return in_array('*', $key) ? Arr::collapse($result) : $result;
            }

            $segment = match ($segment) {
                '\*' => '*',
                '\{first}' => '{first}',
                '{first}' => array_key_first(is_array($target) ? $target : (new Collection($target))->all()),
                '\{last}' => '{last}',
                '{last}' => array_key_last(is_array($target) ? $target : (new Collection($target))->all()),
                default => $segment,
            };

            if (Arr::accessible($target) && Arr::exists($target, $segment)) {
                $target = $target[$segment];
            } elseif (is_object($target) && isset($target->{$segment})) {
                $target = $target->{$segment};
            } else {
                return value($default);
            }
        }

        return $target;
    }
}

if (! function_exists('data_set')) {
    /**
     * Set an item on an array or object using dot notation.
     *
     * @param  mixed  $target
     * @param  string|array  $key
     * @param  mixed  $value
     * @param  bool  $overwrite
     * @return mixed
     */
    function data_set(&$target, $key, $value, $overwrite = true)
    {
        $segments = is_array($key) ? $key : explode('.', $key);

        if (($segment = array_shift($segments)) === '*') {
            if (! Arr::accessible($target)) {
                $target = [];
            }

            if ($segments) {
                foreach ($target as &$inner) {
                    data_set($inner, $segments, $value, $overwrite);
                }
            } elseif ($overwrite) {
                foreach ($target as &$inner) {
                    $inner = $value;
                }
            }
        } elseif (Arr::accessible($target)) {
            if ($segments) {
                if (! Arr::exists($target, $segment)) {
                    $target[$segment] = [];
                }

                data_set($target[$segment], $segments, $value, $overwrite);
            } elseif ($overwrite || ! Arr::exists($target, $segment)) {
                $target[$segment] = $value;
            }
        } elseif (is_object($target)) {
            if ($segments) {
                if (! isset($target->{$segment})) {
                    $target->{$segment} = [];
                }

                data_set($target->{$segment}, $segments, $value, $overwrite);
            } elseif ($overwrite || ! isset($target->{$segment})) {
                $target->{$segment} = $value;
            }
        } else {
            $target = [];

            if ($segments) {
                data_set($target[$segment], $segments, $value, $overwrite);
            } elseif ($overwrite) {
                $target[$segment] = $value;
            }
        }

        return $target;
    }
}

if (! function_exists('data_forget')) {
    /**
     * Remove / unset an item from an array or object using "dot" notation.
     *
     * @param  mixed  $target
     * @param  string|array|int|null  $key
     * @return mixed
     */
    function data_forget(&$target, $key)
    {
        $segments = is_array($key) ? $key : explode('.', $key);

        if (($segment = array_shift($segments)) === '*' && Arr::accessible($target)) {
            if ($segments) {
                foreach ($target as &$inner) {
                    data_forget($inner, $segments);
                }
            }
        } elseif (Arr::accessible($target)) {
            if ($segments && Arr::exists($target, $segment)) {
                data_forget($target[$segment], $segments);
            } else {
                Arr::forget($target, $segment);
            }
        } elseif (is_object($target)) {
            if ($segments && isset($target->{$segment})) {
                data_forget($target->{$segment}, $segments);
            } elseif (isset($target->{$segment})) {
                unset($target->{$segment});
            }
        }

        return $target;
    }
}

if (! function_exists('head')) {
    /**
     * Get the first element of an array. Useful for method chaining.
     *
     * @param  array  $array
     * @return mixed
     */
    function head($array)
    {
        return reset($array);
    }
}

if (! function_exists('last')) {
    /**
     * Get the last element from an array.
     *
     * @param  array  $array
     * @return mixed
     */
    function last($array)
    {
        return end($array);
    }
}

if (! function_exists('value')) {
    /**
     * Return the default value of the given value.
     *
     * @template TValue
     * @template TArgs
     *
     * @param  TValue|\Closure(TArgs): TValue  $value
     * @param  TArgs  ...$args
     * @return TValue
     */
    function value($value, ...$args)
    {
        return $value instanceof Closure ? $value(...$args) : $value;
    }
}

if (! function_exists('when')) {
    /**
     * Return a value if the given condition is true.
     *
     * @param  mixed  $condition
     * @param  \Closure|mixed  $value
     * @param  \Closure|mixed  $default
     * @return mixed
     */
    function when($condition, $value, $default = null)
    {
        if ($condition) {
            return value($value, $condition);
        }

        return value($default, $condition);
    }
}

if (! function_exists('windows_os')) {
    /**
     * Determine whether the current environment is Windows based.
     *
     * @return bool
     */
    function windows_os()
    {
        return PHP_OS_FAMILY === 'Windows';
    }
}

if (! function_exists('with')) {
    /**
     * Return the given value, optionally passed through the given callback.
     *
     * @template TValue
     * @template TReturn
     *
     * @param  TValue  $value
     * @param  (callable(TValue): (TReturn))|null  $callback
     * @return ($callback is null ? TValue : TReturn)
     */
    function with($value, ?callable $callback = null)
    {
        return is_null($callback) ? $value : $callback($value);
    }
}

if (! function_exists('once')) {
    /**
     * Ensures a callable is only called once, and returns the result on subsequent calls.
     *
     * @template  TReturnType
     *
     * @param  callable(): TReturnType  $callback
     * @return TReturnType
     */
    function once(callable $callback)
    {
        $onceable = Onceable::tryFromTrace(
            debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2),
            $callback,
        );

        return $onceable ? Once::instance()->value($onceable) : call_user_func($callback);
    }
}