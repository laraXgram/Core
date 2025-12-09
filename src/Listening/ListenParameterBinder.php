<?php

namespace LaraGram\Listening;

use LaraGram\Support\Arr;
use LaraGram\Support\Str;

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
     * @param  \LaraGram\Request\Request $request
     * @return array
     */
    public function parameters($request)
    {
        $parameters = $this->bindPathParameters($request);

        return $this->replaceDefaults($parameters);
    }

    /**
     * Get the parameter matches for the path portion of the URI.
     *
     * @param \LaraGram\Request\Request $request
     * @return array
     */
    protected function bindPathParameters($request)
    {
        $path = $this->extractUpdate($request);

        preg_match($this->listen->compiled->getRegex(), $path, $matches);

        return $this->matchToKeys(array_slice($matches, 1));
    }

    /**
     * Get the parameter matches for the path portion of the URI.
     *
     * @param \LaraGram\Request\Request $request
     * @return string|null
     */
    private function extractUpdate($request)
    {
        if (in_array('COMMAND', $this->listen->methods())) {
            $text = Str::replaceFirst('/', '', text());
        } elseif (in_array('REFERRAL', $this->listen->methods())) {
            $text = Str::replaceFirst('/start ', '', text());
        } else {
            $text = text();
        }

        return match (true){
            $text !== null => $text,
            isset($request->callback_query->data) => $request->callback_query->data,
            isset($request->inline_query->query) => $request->inline_query->query,
            isset($request->chosen_inline_result->query) => $request->chosen_inline_result->query,
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
