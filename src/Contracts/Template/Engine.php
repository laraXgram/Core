<?php

namespace LaraGram\Contracts\Template;

interface Engine
{
    /**
     * Get the evaluated contents of the template.
     *
     * @param  string  $path
     * @param  array  $data
     * @return string
     */
    public function get($path, array $data = []);
}
