<?php

namespace LaraGram\Support\Finder\Iterator;

class FileTypeFilterIterator extends \FilterIterator
{
    public const ONLY_FILES = 1;
    public const ONLY_DIRECTORIES = 2;

    /**
     * @param \Iterator<string, \SplFileInfo> $iterator The Iterator to filter
     * @param int                             $mode     The mode (self::ONLY_FILES or self::ONLY_DIRECTORIES)
     */
    public function __construct(
        \Iterator $iterator,
        private int $mode,
    ) {
        parent::__construct($iterator);
    }

    /**
     * Filters the iterator values.
     */
    public function accept(): bool
    {
        $fileinfo = $this->current();
        if (self::ONLY_DIRECTORIES === (self::ONLY_DIRECTORIES & $this->mode) && $fileinfo->isFile()) {
            return false;
        } elseif (self::ONLY_FILES === (self::ONLY_FILES & $this->mode) && $fileinfo->isDir()) {
            return false;
        }

        return true;
    }
}
