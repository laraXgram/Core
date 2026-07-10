<?php

namespace LaraGram\Console\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class Usage
{
    /**
     * Create a new attribute instance.
     *
     * @param  string  $usage
     */
    public function __construct(public string $usage)
    {
        //
    }
}
