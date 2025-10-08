<?php

namespace LaraGram\Support\Facades;

/**
 * @method static \Closure userResolver()
 * @method static \LaraGram\Auth\AuthManager resolveUsersUsing(\Closure $userResolver)
 * @method static \LaraGram\Auth\AuthManager provider(string $name, \Closure $callback)
 * @method static \LaraGram\Auth\AuthManager setApplication(\LaraGram\Contracts\Foundation\Application $app)
 * @method static \LaraGram\Contracts\Auth\UserProvider|null createUserProvider(string|null $provider = null)
 * @method static string getDefaultUserProvider()
 * @method static bool check()
 * @method static \LaraGram\Contracts\Auth\Authenticatable|null user()
 * @method static void macro(string $name, object|callable $macro)
 * @method static void mixin(object $mixin, bool $replace = true)
 * @method static bool hasMacro(string $name)
 * @method static void flushMacros()
 *
 * @see \LaraGram\Auth\AuthManager
 */
class Auth extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'auth';
    }
}
