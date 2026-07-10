<?php

namespace LaraGram\Queue\Attributes;

use Attribute;
use UnitEnum;

use function LaraGram\Support\enum_value;

#[Attribute(Attribute::TARGET_CLASS)]
class Connection
{
    /**
     * Create a new attribute instance.
     *
     * @param  UnitEnum|string  $connection
     */
    public function __construct(public UnitEnum|string $connection)
    {
        $this->connection = enum_value($connection);
    }
}
