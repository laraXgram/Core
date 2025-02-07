<?php

namespace LaraGram\Console\View\Components\Mutators;

class EnsureNoPunctuation
{
    /**
     * Ensures the given string does not end with punctuation.
     *
     * @param  string  $string
     * @return string
     */
    public function __invoke($string)
    {
        if (in_array(substr($string, -1), ['.', '?', '!', ':'])) {
            return substr($string, 0, -1);
        }

        return $string;
    }
}
