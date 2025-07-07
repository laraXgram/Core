<?php

namespace LaraGram\Support\Finder\Iterator;

use LaraGram\Support\Finder\Comparator\NumberComparator;

class SizeRangeFilterIterator extends \FilterIterator
{
    private array $comparators = [];

    /**
     * @param \Iterator<string, \SplFileInfo> $iterator
     * @param NumberComparator[]              $comparators
     */
    public function __construct(\Iterator $iterator, array $comparators)
    {
        $this->comparators = $comparators;

        parent::__construct($iterator);
    }

    /**
     * Filters the iterator values.
     */
    public function accept(): bool
    {
        $fileinfo = $this->current();
        if (!$fileinfo->isFile()) {
            return true;
        }

        $filesize = $fileinfo->getSize();
        foreach ($this->comparators as $compare) {
            if (!$compare->test($filesize)) {
                return false;
            }
        }

        return true;
    }
}
