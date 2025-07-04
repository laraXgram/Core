<?php

declare(strict_types=1);

namespace LaraGram\Support\Env\Parser;

use LaraGram\Support\Env\Util\Option;

final class Entry
{
    /**
     * The entry name.
     *
     * @var string
     */
    private $name;

    /**
     * The entry value.
     *
     * @var \LaraGram\Support\Env\Parser\Value|null
     */
    private $value;

    /**
     * Create a new entry instance.
     *
     * @param string                    $name
     * @param \LaraGram\Support\Env\Parser\Value|null $value
     *
     * @return void
     */
    public function __construct(string $name, ?Value $value = null)
    {
        $this->name = $name;
        $this->value = $value;
    }

    /**
     * Get the entry name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get the entry value.
     *
     * @return \LaraGram\Support\Env\Util\Option<\LaraGram\Support\Env\Parser\Value>
     */
    public function getValue()
    {
        /** @var \LaraGram\Support\Env\Util\Option<\LaraGram\Support\Env\Parser\Value> */
        return Option::fromValue($this->value);
    }
}
