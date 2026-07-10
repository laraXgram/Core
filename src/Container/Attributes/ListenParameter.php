<?php

namespace LaraGram\Container\Attributes;

use Attribute;
use LaraGram\Contracts\Container\Container;
use LaraGram\Contracts\Container\ContextualAttribute;
use ReflectionParameter;

#[Attribute(Attribute::TARGET_PARAMETER)]
class ListenParameter implements ContextualAttribute
{
    /**
     * Create a new class instance.
     */
    public function __construct(public ?string $parameter = null)
    {
    }

    /**
     * Resolve the route parameter.
     *
     * @param  self  $attribute
     * @param  \LaraGram\Contracts\Container\Container  $container
     * @return mixed
     */
    public static function resolve(self $attribute, Container $container, ReflectionParameter $parameter)
    {
        return $container->make('request')->route($attribute->parameter ?? $parameter->getName());
    }
}
