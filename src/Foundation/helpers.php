<?php

use LaraGram\Foundation\Application;

if (!function_exists('app')) {
    /**
     * Get the available container instance.
     *
     * @param string|null $abstract
     * @param array $parameters
     * @return Application
     */
    function app(string $abstract = null, array $parameters = []): mixed
    {
        if (is_null($abstract)) {
            return Application::getInstance();
        }

        return Application::getInstance()->make($abstract, $parameters);
    }
}

if (!function_exists('config')) {
    /**
     * Get the Config repository
     *
     * @param array|string $key
     * @param mixed|null $value
     * @return mixed
     */
    function config(array|string $key = '', mixed $value = null): mixed
    {
        /**
         * @var LaraGram\Config\Repository $config
         */
        $config = app('config');

        if (is_null($key)) {
            return $config;
        } else {
            if (is_null($value)) {
                return $config->get($key);
            } else {
                $config->set($key, $value);
                return true;
            }
        }
    }
}