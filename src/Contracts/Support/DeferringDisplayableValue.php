<?php

namespace LaraGram\Contracts\Support;

interface DeferringDisplayableValue
{
    /**
     * Resolve the displayable value that the class is deferring.
     *
     * @return \LaraGram\Contracts\Support\Htmlable|string
     */
    public function resolveDisplayableValue();
}
