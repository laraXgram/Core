<?php

namespace LaraGram\Listening\Exceptions;

use Exception;
use LaraGram\Listening\Listen;
use LaraGram\Support\Str;

class PathGenerationException extends Exception
{
    /**
     * Create a new exception for missing listen parameters.
     *
     * @param  \LaraGram\Listening\Listen  $listen
     * @param  array  $parameters
     * @return static
     */
    public static function forMissingParameters(Listen $listen, array $parameters = [])
    {
        $parameterLabel = Str::plural('parameter', count($parameters));

        $message = sprintf(
            'Missing required %s for [Listen: %s] [Pattern: %s]',
            $parameterLabel,
            $listen->getName(),
            $listen->pattern()
        );

        if (count($parameters) > 0) {
            $message .= sprintf(' [Missing %s: %s]', $parameterLabel, implode(', ', $parameters));
        }

        $message .= '.';

        return new static($message);
    }
}
