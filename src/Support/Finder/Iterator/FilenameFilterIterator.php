<?php

namespace LaraGram\Support\Finder\Iterator;

use LaraGram\Support\Finder\Glob;

class FilenameFilterIterator extends MultiplePcreFilterIterator
{
    /**
     * Filters the iterator values.
     */
    public function accept(): bool
    {
        return $this->isAccepted($this->current()->getFilename());
    }

    /**
     * Converts glob to regexp.
     *
     * PCRE patterns are left unchanged.
     * Glob strings are transformed with Glob::toRegex().
     *
     * @param string $str Pattern: glob or regexp
     */
    protected function toRegex(string $str): string
    {
        return $this->isRegex($str) ? $str : Glob::toRegex($str);
    }
}
