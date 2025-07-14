<?php

namespace LaraGram\Listening;

use LaraGram\Support\Arr;
use LaraGram\Support\Facades\Log;

class ListenParameterBinder
{
    /**
     * The listen instance.
     *
     * @var \LaraGram\Listening\Listen
     */
    protected $listen;

    /**
     * Create a new Listen parameter binder instance.
     *
     * @param  \LaraGram\Listening\Listen $listen
     * @return void
     */
    public function __construct($listen)
    {
        $this->listen = $listen;
    }

    /**
     * Get the parameters for the listen.
     *
     * @return array
     */
    public function parameters()
    {
        $parameters = $this->bindPathParameters();

        return $this->replaceDefaults($parameters);
    }

    /**
     * Get the parameter matches for the path portion of the URI.
     *
     * @return array
     */
    protected function bindPathParameters()
    {
        $path = $this->extractUpdate();

        preg_match($this->listen->compiled->getRegex(), $path, $matches);

        return $this->matchToKeys(array_slice($matches, 1));
    }

    private function extractUpdate()
    {
        return match (true){
            text() !== null => text(),
            isset(callback_query()->data) => callback_query()->data,
            isset(inline_query()->query) => inline_query()->query,
            isset(chosen_inline_result()->query) => chosen_inline_result()->query,
            default => null
        };
    }

    /**
     * Combine a set of parameter matches with the listen's keys.
     *
     * @param  array  $matches
     * @return array
     */
    protected function matchToKeys(array $matches)
    {
        if (empty($parameterNames = $this->listen->parameterNames())) {
            return [];
        }

        $parameters = array_intersect_key($matches, array_flip($parameterNames));

        return array_filter($parameters, function ($value) {
            return is_string($value) && strlen($value) > 0;
        });
    }

    /**
     * Replace null parameters with their defaults.
     *
     * @param  array  $parameters
     * @return array
     */
    protected function replaceDefaults(array $parameters)
    {
        foreach ($parameters as $key => $value) {
            $parameters[$key] = $value ?? Arr::get($this->listen->defaults, $key);
        }

        foreach ($this->listen->defaults as $key => $value) {
            if (! isset($parameters[$key])) {
                $parameters[$key] = $value;
            }
        }

        return $parameters;
    }
}
