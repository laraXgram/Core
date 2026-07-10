<?php

namespace LaraGram\Support\Facades;

/**
 * @method static \LaraGram\Request\RedirectResponse listen(\BackedEnum|string $listen, mixed $parameters = [])
 * @method static \LaraGram\Listening\PathGenerator getPathGenerator()
 * @method static void setCache(\LaraGram\Cache\CacheManager $cache)
 *
 * @method static \LaraGram\Http\RedirectResponse back(int $status = 302, array $headers = [], mixed $fallback = false)
 * @method static \LaraGram\Http\RedirectResponse refresh(int $status = 302, array $headers = [])
 * @method static \LaraGram\Http\RedirectResponse guest(string $path, int $status = 302, array $headers = [], bool|null $secure = null)
 * @method static \LaraGram\Http\RedirectResponse intended(mixed $default = '/', int $status = 302, array $headers = [], bool|null $secure = null)
 * @method static \LaraGram\Http\RedirectResponse to(string $path, int $status = 302, array $headers = [], bool|null $secure = null)
 * @method static \LaraGram\Http\RedirectResponse away(string $path, int $status = 302, array $headers = [])
 * @method static \LaraGram\Http\RedirectResponse secure(string $path, int $status = 302, array $headers = [])
 * @method static \LaraGram\Http\RedirectResponse route(\BackedEnum|string $route, mixed $parameters = [], int $status = 302, array $headers = [])
 * @method static \LaraGram\Http\RedirectResponse signedRoute(\BackedEnum|string $route, mixed $parameters = [], \DateTimeInterface|\DateInterval|int|null $expiration = null, int $status = 302, array $headers = [])
 * @method static \LaraGram\Http\RedirectResponse temporarySignedRoute(\BackedEnum|string $route, \DateTimeInterface|\DateInterval|int|null $expiration, mixed $parameters = [], int $status = 302, array $headers = [])
 * @method static string|null getIntendedUrl()
 * @method static \LaraGram\Routing\Redirector setIntendedUrl(string $url)
 * @method static void setSession(\LaraGram\Session\Store $session)
 *
 * @method static \LaraGram\Http\RedirectResponse|\LaraGram\Request\RedirectResponse action(string|array $action, mixed $parameters = [])
 * @method static mixed getUrlGenerator()
 * @method static void macro(string $name, object|callable $macro)
 * @method static void mixin(object $mixin, bool $replace = true)
 * @method static bool hasMacro(string $name)
 * @method static void flushMacros()
 *
 * @see \LaraGram\Listening\Redirector
 * @see \LaraGram\Routing\Redirector
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
