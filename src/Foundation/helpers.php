<?php

use LaraGram\Foundation\Application;
use LaraGram\Support\Arr;

if (!function_exists('app')) {
    /**
     * Get the available container instance.
     *
     * @param string|null $abstract
     * @param array $parameters
     * @return Application
     */
    function app(string $abstract = null, array $parameters = []): mixed
    {
        if (is_null($abstract)) {
            return Application::getInstance();
        }

        return Application::getInstance()->make($abstract, $parameters);
    }
}

if (!function_exists('config')) {
    /**
     * Get the Config repository
     *
     * @param array|string $key
     * @param mixed|null $value
     * @return mixed
     */
    function config(array|string $key = '', mixed $value = null): mixed
    {
        /**
         * @var LaraGram\Config\Repository $config
         */
        $config = app('config');

        if (is_null($key)) {
            return $config;
        } else {
            if (is_null($value)) {
                return $config->get($key);
            } else {
                $config->set($key, $value);
                return true;
            }
        }
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
                if (!is_iterable($target)) {
                    return is_callable($default) ? $default() : $default;
                }

                $result = [];

                foreach ($target as $item) {
                    $result[] = data_get($item, $key);
                }

                return in_array('*', $key) ? array_merge(...$result) : $result;
            }

            $segment = match ($segment) {
                '\*' => '*',
                '\{first}' => '{first}',
                '{first}' => is_array($target) ? array_key_first($target) : null,
                '\{last}' => '{last}',
                '{last}' => is_array($target) ? array_key_last($target) : null,
                default => $segment,
            };

            if (is_array($target) && array_key_exists($segment, $target)) {
                $target = $target[$segment];
            } elseif (is_object($target) && isset($target->{$segment})) {
                $target = $target->{$segment};
            } else {
                return is_callable($default) ? $default() : $default;
            }
        }

        return $target;
    }
}