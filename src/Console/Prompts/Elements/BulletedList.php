<?php

namespace LaraGram\Console\Prompts\Elements;

class BulletedList implements ElementContract
{
    /**
     * @param  array<int, string>  $items
     */
    public function __construct(
        public readonly array $items,
        public readonly bool $spaced = false,
    ) {
        //
    }
}
