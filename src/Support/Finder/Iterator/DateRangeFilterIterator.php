<?php

namespace LaraGram\Support\Finder\Iterator;

use LaraGram\Support\Finder\Comparator\DateComparator;

class DateRangeFilterIterator extends \FilterIterator
{
    private array $comparators = [];

    /**
     * @param \Iterator<string, \SplFileInfo> $iterator
     * @param DateComparator[]                $comparators
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

        if (!file_exists($fileinfo->getPathname())) {
            return false;
        }

        $filedate = $fileinfo->getMTime();
        foreach ($this->comparators as $compare) {
            if (!$compare->test($filedate)) {
                return false;
            }
        }

        return true;
    }
}
