<?php

namespace LaraGram\Support\Facades;

/**
 * @method static \LaraGram\Request\RedirectResponse listen(\BackedEnum|string $listen, mixed $parameters = [])
 * @method static \LaraGram\Request\RedirectResponse action(string|array $action, mixed $parameters = [])
 * @method static \LaraGram\Listening\PathGenerator getPathGenerator()
 * @method static void setCache(\LaraGram\Cache\CacheManager $cache)
 * @method static void macro(string $name, object|callable $macro)
 * @method static void mixin(object $mixin, bool $replace = true)
 * @method static bool hasMacro(string $name)
 * @method static void flushMacros()
 *
 * @see \LaraGram\Listening\Redirector
 */
class Redirect extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'redirect';
    }
}
