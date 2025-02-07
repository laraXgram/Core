<?php

namespace LaraGram\Console\View\Components\Mutators;

class EnsureRelativePaths
{
    /**
     * Ensures the given string only contains relative paths.
     *
     * @param  string  $string
     * @return string
     */
    public function __invoke($string)
    {
        if (function_exists('app') && app()->has('path.base')) {
            $string = str_replace(app()->basePath().'/', '', $string);
        }

        return $string;
    }
}
