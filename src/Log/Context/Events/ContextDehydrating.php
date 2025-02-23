<?php

namespace LaraGram\Log\Context\Events;

class ContextDehydrating
{
    /**
     * The context instance.
     *
     * @var \LaraGram\Log\Context\Repository
     */
    public $context;

    /**
     * Create a new event instance.
     *
     * @param  \LaraGram\Log\Context\Repository  $context
     */
    public function __construct($context)
    {
        $this->context = $context;
    }
}
