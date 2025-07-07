<?php

namespace LaraGram\Support\Finder\Iterator;

class PathFilterIterator extends MultiplePcreFilterIterator
{
    /**
     * Filters the iterator values.
     */
    public function accept(): bool
    {
        $filename = $this->current()->getRelativePathname();

        if ('\\' === \DIRECTORY_SEPARATOR) {
            $filename = str_replace('\\', '/', $filename);
        }

        return $this->isAccepted($filename);
    }

    /**
     * Converts strings to regexp.
     *
     * PCRE patterns are left unchanged.
     *
     * Default conversion:
     *     'lorem/ipsum/dolor' ==>  'lorem\/ipsum\/dolor/'
     *
     * Use only / as directory separator (on Windows also).
     *
     * @param string $str Pattern: regexp or dirname
     */
    protected function toRegex(string $str): string
    {
        return $this->isRegex($str) ? $str : '/'.preg_quote($str, '/').'/';
    }
}
