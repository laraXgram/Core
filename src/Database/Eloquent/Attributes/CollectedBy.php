<?php

namespace LaraGram\Database\Eloquent\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class CollectedBy
{
    /**
     * Create a new attribute instance.
     *
     * @param  class-string<\LaraGram\Database\Eloquent\Collection< $collectionClass *, *>>  $collectionClass
     * @return void
     */
    public function __construct(public string $collectionClass)
    {
    }
}
