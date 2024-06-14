<?php

namespace LaraGram\Container;

class ContextualBindingBuilder
{
    protected $container;
    protected $concrete;
    protected $abstract;

    public function __construct(Container $container, $concrete)
    {
        $this->container = $container;
        $this->concrete = $concrete;
    }

    public function needs($abstract): static
    {
        $this->abstract = $abstract;
        return $this;
    }

    public function give($implementation): void
    {
        $this->container->addContextualBinding($this->concrete, $this->abstract, $implementation);
    }
}