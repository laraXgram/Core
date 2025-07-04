<?php

namespace LaraGram\Contracts\Template;

use LaraGram\Contracts\Support\Renderable;

interface Template extends Renderable
{
    /**
     * Get the name of the template.
     *
     * @return string
     */
    public function name();

    /**
     * Add a piece of data to the template.
     *
     * @param  string|array  $key
     * @param  mixed  $value
     * @return $this
     */
    public function with($key, $value = null);

    /**
     * Get the array of template data.
     *
     * @return array
     */
    public function getData();
}
