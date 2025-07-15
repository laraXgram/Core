<?php

namespace LaraGram\Contracts\Listening;

interface PathGenerator
{
    /**
     * Get the Path to a named listen.
     *
     * @param  string  $name
     * @param  mixed  $parameters
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    public function listen($name, $parameters = []);

    /**
     * Get the Path to a controller action.
     *
     * @param  string|array  $action
     * @param  mixed  $parameters
     * @return string
     */
    public function action($action, $parameters = []);

    /**
     * Get the root controller namespace.
     *
     * @return string
     */
    public function getRootControllerNamespace();

    /**
     * Set the root controller namespace.
     *
     * @param  string  $rootNamespace
     * @return $this
     */
    public function setRootControllerNamespace($rootNamespace);
}
