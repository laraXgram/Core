<?php

namespace LaraGram\Support\Finder\Iterator;

class DepthRangeFilterIterator extends \FilterIterator
{
    private int $minDepth = 0;

    /**
     * @param \RecursiveIteratorIterator<\RecursiveIterator<TKey, TValue>> $iterator The Iterator to filter
     * @param int                                                          $minDepth The min depth
     * @param int                                                          $maxDepth The max depth
     */
    public function __construct(\RecursiveIteratorIterator $iterator, int $minDepth = 0, int $maxDepth = \PHP_INT_MAX)
    {
        $this->minDepth = $minDepth;
        $iterator->setMaxDepth(\PHP_INT_MAX === $maxDepth ? -1 : $maxDepth);

        parent::__construct($iterator);
    }

    /**
     * Filters the iterator values.
     */
    public function accept(): bool
    {
        return $this->getInnerIterator()->getDepth() >= $this->minDepth;
    }
}
