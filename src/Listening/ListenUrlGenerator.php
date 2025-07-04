<?php

namespace LaraGram\Listening;

use LaraGram\Listening\Exceptions\UrlGenerationException;
use LaraGram\Support\Arr;

class ListenUrlGenerator
{
    /**
     * The URL generator instance.
     *
     * @var \LaraGram\Listening\UrlGenerator
     */
    protected $url;

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
     * Create a new Listen URL generator.
     *
     * @param  \LaraGram\Listening\UrlGenerator  $url
     * @param  \LaraGram\Request\Request  $request
     * @return void
     */
    public function __construct($url, $request)
    {
        $this->url = $url;
        $this->request = $request;
    }

    /**
     * Generate a URL for the given listen.
     *
     * @param  \LaraGram\Listening\Listen  $listen
     * @param  array  $parameters
     * @param  bool  $absolute
     * @return string
     *
     * @throws \LaraGram\Listening\Exceptions\UrlGenerationException
     */
    public function to($listen, $parameters = [], $absolute = false)
    {
        if (preg_match_all('/{(.*?)}/', $uri, $matchedMissingParameters)) {
            throw UrlGenerationException::forMissingParameters($listen, $matchedMissingParameters[1]);
        }

        // Once we have ensured that there are no missing parameters in the URI we will encode
        // the URI and prepare it for returning to the developer. If the URI is supposed to
        // be absolute, we will return it as-is. Otherwise we will remove the URL's root.
        $uri = strtr(rawurlencode($uri), $this->dontEncode);

        if (! $absolute) {
            $uri = preg_replace('#^(//|[^/?])+#', '', $uri);

            if ($base = $this->request->getBaseUrl()) {
                $uri = preg_replace('#^'.$base.'#i', '', $uri);
            }

            return '/'.ltrim($uri, '/');
        }

        return $uri;
    }

    /**
     * Replace the parameters on the root path.
     *
     * @param  \LaraGram\Listening\Listen  $listen
     * @param  string  $domain
     * @param  array  $parameters
     * @return string
     */
    protected function replaceRootParameters($listen, $domain, &$parameters)
    {
        $scheme = $this->getListenScheme($listen);

        return $this->replaceListenParameters(
            $this->url->formatRoot($scheme, $domain), $parameters
        );
    }

    /**
     * Replace all of the wildcard parameters for a listen path.
     *
     * @param  string  $path
     * @param  array  $parameters
     * @return string
     */
    protected function replaceListenParameters($path, array &$parameters)
    {
        $path = $this->replaceNamedParameters($path, $parameters);

        $path = preg_replace_callback('/\{.*?\}/', function ($match) use (&$parameters) {
            // Reset only the numeric keys...
            $parameters = array_merge($parameters);

            return (! isset($parameters[0]) && ! str_ends_with($match[0], '?}'))
                ? $match[0]
                : Arr::pull($parameters, 0);
        }, $path);

        return trim(preg_replace('/\{.*?\?\}/', '', $path), '/');
    }

    /**
     * Replace all of the named parameters in the path.
     *
     * @param  string  $path
     * @param  array  $parameters
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
     * Get the string parameters from a given list.
     *
     * @param  array  $parameters
     * @return array
     */
    protected function getStringParameters(array $parameters)
    {
        return array_filter($parameters, 'is_string', ARRAY_FILTER_USE_KEY);
    }

    /**
     * Get the numeric parameters from a given list.
     *
     * @param  array  $parameters
     * @return array
     */
    protected function getNumericParameters(array $parameters)
    {
        return array_filter($parameters, 'is_numeric', ARRAY_FILTER_USE_KEY);
    }

    /**
     * Set the default named parameters used by the URL generator.
     *
     * @param  array  $defaults
     * @return void
     */
    public function defaults(array $defaults)
    {
        $this->defaultParameters = array_merge(
            $this->defaultParameters, $defaults
        );
    }
}
