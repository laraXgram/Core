<?php

namespace LaraGram\Console\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Aliases
{
    /**
     * Create a new attribute instance.
     *
     * @param  string[]  $aliases
     */
    public function __construct(public array $aliases)
    {
        //
    }
}
