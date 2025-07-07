<?php

namespace LaraGram\Support\Finder\Iterator;

class CustomFilterIterator extends \FilterIterator
{
    private array $filters = [];

    /**
     * @param \Iterator<string, \SplFileInfo> $iterator The Iterator to filter
     * @param callable[]                      $filters  An array of PHP callbacks
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(\Iterator $iterator, array $filters)
    {
        foreach ($filters as $filter) {
            if (!\is_callable($filter)) {
                throw new \InvalidArgumentException('Invalid PHP callback.');
            }
        }
        $this->filters = $filters;

        parent::__construct($iterator);
    }

    /**
     * Filters the iterator values.
     */
    public function accept(): bool
    {
        $fileinfo = $this->current();

        foreach ($this->filters as $filter) {
            if (false === $filter($fileinfo)) {
                return false;
            }
        }

        return true;
    }
}
