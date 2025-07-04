<?php

namespace LaraGram\Contracts\Template;

interface Factory
{
    /**
     * Determine if a given template exists.
     *
     * @param  string  $template
     * @return bool
     */
    public function exists($template);

    /**
     * Get the evaluated template contents for the given path.
     *
     * @param  string  $path
     * @param  \LaraGram\Contracts\Support\Arrayable|array  $data
     * @param  array  $mergeData
     * @return \LaraGram\Contracts\Template\Template
     */
    public function file($path, $data = [], $mergeData = []);

    /**
     * Get the evaluated template contents for the given template.
     *
     * @param  string  $template
     * @param  \LaraGram\Contracts\Support\Arrayable|array  $data
     * @param  array  $mergeData
     * @return \LaraGram\Contracts\Template\Template
     */
    public function make($template, $data = [], $mergeData = []);

    /**
     * Add a piece of shared data to the environment.
     *
     * @param  array|string  $key
     * @param  mixed  $value
     * @return mixed
     */
    public function share($key, $value = null);

    /**
     * Register a template composer event.
     *
     * @param  array|string  $templates
     * @param  \Closure|string  $callback
     * @return array
     */
    public function composer($templates, $callback);

    /**
     * Register a template creator event.
     *
     * @param  array|string  $templates
     * @param  \Closure|string  $callback
     * @return array
     */
    public function creator($templates, $callback);

    /**
     * Add a new namespace to the loader.
     *
     * @param  string  $namespace
     * @param  string|array  $hints
     * @return $this
     */
    public function addNamespace($namespace, $hints);

    /**
     * Replace the namespace hints for the given namespace.
     *
     * @param  string  $namespace
     * @param  string|array  $hints
     * @return $this
     */
    public function replaceNamespace($namespace, $hints);
}
