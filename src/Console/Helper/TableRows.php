<?php

namespace LaraGram\Console\Helper;

class TableRows implements \IteratorAggregate
{
    public function __construct(
        private \Closure $generator,
    ) {
    }

    public function getIterator(): \Traversable
    {
        return ($this->generator)();
    }
}
