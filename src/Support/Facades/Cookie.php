<?php

namespace LaraGram\Support\Facades;

/**
 * @method static \LaraGram\Cookie\Cookie make(string $name, string $value, int $minutes = 0, string|null $path = null, string|null $domain = null, bool|null $secure = null, bool $httpOnly = true, bool $raw = false, string|null $sameSite = null)
 * @method static \LaraGram\Cookie\Cookie forever(string $name, string $value, string|null $path = null, string|null $domain = null, bool|null $secure = null, bool $httpOnly = true, bool $raw = false, string|null $sameSite = null)
 * @method static \LaraGram\Cookie\Cookie forget(string $name, string|null $path = null, string|null $domain = null)
 * @method static bool hasQueued(string $key, string|null $path = null)
 * @method static \LaraGram\Cookie\Cookie|null queued(string $key, mixed $default = null, string|null $path = null)
 * @method static void queue(mixed ...$parameters)
 * @method static void expire(string $name, string|null $path = null, string|null $domain = null)
 * @method static void unqueue(string $name, string|null $path = null)
 * @method static \LaraGram\Cookie\CookieJar setDefaultPathAndDomain(string $path, string|null $domain, bool|null $secure = false, string|null $sameSite = null)
 * @method static \LaraGram\Cookie\Cookie[] getQueuedCookies()
 * @method static \LaraGram\Cookie\CookieJar flushQueuedCookies()
 * @method static void macro(string $name, object|callable $macro)
 * @method static void mixin(object $mixin, bool $replace = true)
 * @method static bool hasMacro(string $name)
 * @method static void flushMacros()
 *
 * @see \LaraGram\Cookie\CookieJar
 */
class Cookie extends Facade
{
    /**
     * Determine if a cookie exists on the request.
     *
     * @param  string  $key
     * @return bool
     */
    public static function has($key)
    {
        return ! is_null(static::$app['http.request']->cookie($key, null));
    }

    /**
     * Retrieve a cookie from the request.
     *
     * @param  string|null  $key
     * @param  mixed  $default
     * @return string|array|null
     */
    public static function get($key = null, $default = null)
    {
        return static::$app['http.request']->cookie($key, $default);
    }

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'cookie';
    }
}
