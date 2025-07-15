<?php

namespace LaraGram\Listening;

use LaraGram\Listening\Exceptions\PathGenerationException;
use LaraGram\Support\Arr;

class ListenPathGenerator
{
    /**
     * The Path generator instance.
     *
     * @var \LaraGram\Listening\PathGenerator
     */
    protected $path;

    /**
     * The request instance.
     *
     * @var \LaraGram\Request\Request
     */
    protected $request;

    /**
     * The named parameter defaults.
     *
     * @var array
     */
    public $defaultParameters = [];

    /**
     * Create a new Listen Path generator.
     *
     * @param \LaraGram\Listening\PathGenerator $path
     * @param \LaraGram\Request\Request $request
     * @return void
     */
    public function __construct($path, $request)
    {
        $this->path = $path;
        $this->request = $request;
    }

    /**
     * Generate a Path for the given listen.
     *
     * @param \LaraGram\Listening\Listen $listen
     * @param array $parameters
     * @return string
     *
     * @throws \LaraGram\Listening\Exceptions\PathGenerationException
     */
    public function to($listen, $parameters = [])
    {
        $path = $this->replaceListenParameters($listen->pattern(), $parameters);

        if (preg_match_all('/{(.*?)}/', $path, $matchedMissingParameters)) {
            throw PathGenerationException::forMissingParameters($listen, $matchedMissingParameters[1]);
        }

        return $path;
    }

    /**
     * Replace all of the wildcard parameters for a route path.
     *
     * @param string $path
     * @param array $parameters
     * @return string
     */
    protected function replaceListenParameters($path, array &$parameters)
    {
        $path = $this->replaceNamedParameters($path, $parameters);

        $path = preg_replace_callback('/\{.*?\}/', function ($match) use (&$parameters) {
            // Reset only the numeric keys...
            $parameters = array_merge($parameters);

            return (!isset($parameters[0]) && !str_ends_with($match[0], '?}'))
                ? $match[0]
                : Arr::pull($parameters, 0);
        }, $path);

        return trim(preg_replace('/\{.*?\?\}/', '', $path), '/');
    }

    /**
     * Replace all of the named parameters in the path.
     *
     * @param string $path
     * @param array $parameters
     * @return string
     */
    protected function replaceNamedParameters($path, &$parameters)
    {
        return preg_replace_callback('/\{(.*?)(\?)?\}/', function ($m) use (&$parameters) {
            if (isset($parameters[$m[1]]) && $parameters[$m[1]] !== '') {
                return Arr::pull($parameters, $m[1]);
            } elseif (isset($this->defaultParameters[$m[1]])) {
                return $this->defaultParameters[$m[1]];
            } elseif (isset($parameters[$m[1]])) {
                Arr::pull($parameters, $m[1]);
            }

            return $m[0];
        }, $path);
    }

    /**
     * Set the default named parameters used by the Path generator.
     *
     * @param array $defaults
     * @return void
     */
    public function defaults(array $defaults)
    {
        $this->defaultParameters = array_merge(
            $this->defaultParameters, $defaults
        );
    }
}
