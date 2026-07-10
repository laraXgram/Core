<?php

namespace LaraGram\Validation\Rules;

use LaraGram\Contracts\Support\Arrayable;
use Stringable;

use function LaraGram\Support\enum_value;

class DoesntContain implements Stringable
{
    /**
     * The values that should not be contained in the attribute.
     *
     * @var array
     */
    protected $values;

    /**
     * Create a new doesntContain rule instance.
     *
     * @param  \LaraGram\Contracts\Support\Arrayable|\UnitEnum|array|string  $values
     */
    public function __construct($values)
    {
        if ($values instanceof Arrayable) {
            $values = $values->toArray();
        }

        $this->values = is_array($values) ? $values : func_get_args();
    }

    /**
     * Convert the rule to a validation string.
     *
     * @return string
     */
    public function __toString(): string
    {
        $values = array_map(function ($value) {
            $value = enum_value($value);

            return '"'.str_replace('"', '""', (string) $value).'"';
        }, $this->values);

        return 'doesnt_contain:'.implode(',', $values);
    }
}
