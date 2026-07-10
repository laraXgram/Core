<?php

namespace LaraGram\Log;

if (! function_exists('LaraGram\Log\log')) {
    /**
     * Log a debug message to the logs.
     *
     * @param  string|null  $message
     * @param  array  $context
     * @return ($message is null ? \LaraGram\Log\LoggerInterface: null)
     */
    function log($message = null, array $context = []): ?LoggerInterface
    {
        return logger($message, $context);
    }
}
