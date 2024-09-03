<?php

use LaraGram\Container\Container;

if (! function_exists('app')) {
    /**
     * Get the available container instance.
     *
     * @param string|null $abstract
     * @param  array  $parameters
     * @return Container
     */
    function app(string $abstract = null, array $parameters = []): mixed
    {
        if (is_null($abstract)) {
            return Container::getInstance();
        }

        return Container::getInstance()->make($abstract, $parameters);
    }
}

if (! function_exists('config')) {
    /**
     * Get the Config repository
     *
     * @return LaraGram\Config\Repository
     */
    function config(): mixed
    {
        return app('config');
    }
}