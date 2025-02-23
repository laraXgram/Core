<?php

namespace LaraGram\Log;

if (! function_exists('LaraGram\Log')) {
    /**
     * Log a debug message to the logs.
     *
     * @param  string|null  $message
     * @param  array  $context
     * @return ($message is null ? \LaraGram\Log\LogManager : null)
     */
    function log($message = null, array $context = [])
    {
        return logger($message, $context);
    }
}
