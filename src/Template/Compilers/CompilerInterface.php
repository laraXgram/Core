<?php

namespace LaraGram\Template\Compilers;

interface CompilerInterface
{
    /**
     * Get the path to the compiled version of a template.
     *
     * @param  string  $path
     * @return string
     */
    public function getCompiledPath($path);

    /**
     * Determine if the given template is expired.
     *
     * @param  string  $path
     * @return bool
     */
    public function isExpired($path);

    /**
     * Compile the template at the given path.
     *
     * @param  string  $path
     * @return void
     */
    public function compile($path);
}
