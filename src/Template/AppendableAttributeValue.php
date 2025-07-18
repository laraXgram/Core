<?php

namespace LaraGram\Template;

use Stringable;

class AppendableAttributeValue implements Stringable
{
    /**
     * The attribute value.
     *
     * @var mixed
     */
    public $value;

    /**
     * Create a new appendable attribute value.
     *
     * @param  mixed  $value
     * @return void
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * Get the string value.
     *
     * @return string
     */
    public function __toString(): string
    {
        return (string) $this->value;
    }
}
