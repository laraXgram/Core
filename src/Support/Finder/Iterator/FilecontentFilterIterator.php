<?php

namespace LaraGram\Support\Finder\Iterator;

class FilecontentFilterIterator extends MultiplePcreFilterIterator
{
    /**
     * Filters the iterator values.
     */
    public function accept(): bool
    {
        if (!$this->matchRegexps && !$this->noMatchRegexps) {
            return true;
        }

        $fileinfo = $this->current();

        if ($fileinfo->isDir() || !$fileinfo->isReadable()) {
            return false;
        }

        $content = $fileinfo->getContents();
        if (!$content) {
            return false;
        }

        return $this->isAccepted($content);
    }

    /**
     * Converts string to regexp if necessary.
     *
     * @param string $str Pattern: string or regexp
     */
    protected function toRegex(string $str): string
    {
        return $this->isRegex($str) ? $str : '/'.preg_quote($str, '/').'/';
    }
}
