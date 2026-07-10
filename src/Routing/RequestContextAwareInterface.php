<?php

namespace LaraGram\Routing;

interface RequestContextAwareInterface
{
    /**
     * Sets the request context.
     */
    public function setContext(RequestContext $context): void;

    /**
     * Gets the request context.
     */
    public function getContext(): RequestContext;
}
