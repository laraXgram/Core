<?php

namespace LaraGram\Support;

use LaraGram\Support\Defer\DeferredCallback;
use LaraGram\Support\Defer\DeferredCallbackCollection;
use LaraGram\Support\Process\PhpExecutableFinder;

if (! function_exists('LaraGram\Support\defer')) {
    /**
     * Defer execution of the given callback.
     *
     * @param  callable|null  $callback
     * @param  string|null  $name
     * @param  bool  $always
     * @return \LaraGram\Support\Defer\DeferredCallback
     */
    function defer(?callable $callback = null, ?string $name = null, bool $always = false)
    {
        if ($callback === null) {
            return app(DeferredCallbackCollection::class);
        }

        return tap(
            new DeferredCallback($callback, $name, $always),
            fn ($deferred) => app(DeferredCallbackCollection::class)[] = $deferred
        );
    }
}

if (! function_exists('LaraGram\Support\php_binary')) {
    /**
     * Determine the PHP Binary.
     *
     * @return string
     */
    function php_binary()
    {
        return (new PhpExecutableFinder)->find(false) ?: 'php';
    }
}

if (! function_exists('LaraGram\Support\commander_binary')) {
    /**
     * Determine the proper Commander executable.
     *
     * @return string
     */
    function commander_binary()
    {
        return defined('COMMANDER_BINARY') ? COMMANDER_BINARY : 'laragram';
    }
}
