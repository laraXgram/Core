<?php

/*
 * Polyfills for PHP 8.4 / 8.5 functions used by LaraGram, so it can run on PHP 8.3.
 * Ported from symfony/polyfill-php84 and symfony/polyfill-php85.
 */

if (\PHP_VERSION_ID >= 80500) {
    return;
}

if (! function_exists('array_first')) {
    function array_first(array $array): mixed
    {
        foreach ($array as $value) {
            return $value;
        }

        return null;
    }
}

if (! function_exists('array_last')) {
    function array_last(array $array): mixed
    {
        return $array ? current(array_slice($array, -1)) : null;
    }
}

if (! function_exists('get_error_handler')) {
    function get_error_handler(): ?callable
    {
        $handler = set_error_handler(null);
        restore_error_handler();

        return $handler;
    }
}

if (! function_exists('get_exception_handler')) {
    function get_exception_handler(): ?callable
    {
        $handler = set_exception_handler(null);
        restore_exception_handler();

        return $handler;
    }
}

if (\PHP_VERSION_ID >= 80400) {
    return;
}

if (! function_exists('array_find')) {
    function array_find(array $array, callable $callback): mixed
    {
        foreach ($array as $key => $value) {
            if ($callback($value, $key)) {
                return $value;
            }
        }

        return null;
    }
}

if (! function_exists('array_find_key')) {
    function array_find_key(array $array, callable $callback): mixed
    {
        foreach ($array as $key => $value) {
            if ($callback($value, $key)) {
                return $key;
            }
        }

        return null;
    }
}

if (! function_exists('array_any')) {
    function array_any(array $array, callable $callback): bool
    {
        foreach ($array as $key => $value) {
            if ($callback($value, $key)) {
                return true;
            }
        }

        return false;
    }
}

if (! function_exists('array_all')) {
    function array_all(array $array, callable $callback): bool
    {
        foreach ($array as $key => $value) {
            if (! $callback($value, $key)) {
                return false;
            }
        }

        return true;
    }
}
