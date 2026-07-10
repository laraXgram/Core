<?php

namespace LaraGram\Console\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Signature
{
    /**
     * Create a new attribute instance.
     *
     * @param  string  $signature
     * @param  string[]|null  $aliases
     */
    public function __construct(public string $signature, public ?array $aliases = null)
    {
        //
    }
}
