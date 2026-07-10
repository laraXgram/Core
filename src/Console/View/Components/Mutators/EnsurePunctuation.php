<?php

namespace LaraGram\Console\View\Components\Mutators;

use LaraGram\Support\Stringable;

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
        if (! (new Stringable($string))->endsWith(['.', '?', '!', ':'])) {
            return "$string.";
        }

        return $string;
    }
}
