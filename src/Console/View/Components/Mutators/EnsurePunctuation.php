<?php

namespace LaraGram\Console\View\Components\Mutators;

class EnsurePunctuation
{
    /**
     * Ensures the given string ends with punctuation.
     *
     * @param  string  $string
     * @return string
     */
    public function __invoke($string)
    {
        if (!in_array(substr($string, -1), ['.', '?', '!', ':'])) {
            return "$string.";
        }


        return $string;
    }
}
