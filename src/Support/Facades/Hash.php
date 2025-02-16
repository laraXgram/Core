<?php

namespace LaraGram\Support\Facades;

/**
 * @method static \LaraGram\Hashing\BcryptHasher createBcryptDriver()
 * @method static \LaraGram\Hashing\ArgonHasher createArgonDriver()
 * @method static \LaraGram\Hashing\Argon2IdHasher createArgon2idDriver()
 * @method static array info(string $hashedValue)
 * @method static string make(string $value, array $options = [])
 * @method static bool check(string $value, string $hashedValue, array $options = [])
 * @method static bool needsRehash(string $hashedValue, array $options = [])
 * @method static bool isHashed(string $value)
 * @method static string getDefaultDriver()
 * @method static mixed driver(string|null $driver = null)
 * @method static \LaraGram\Hashing\HashManager extend(string $driver, \Closure $callback)
 * @method static array getDrivers()
 * @method static \LaraGram\Contracts\Container\Container getContainer()
 * @method static \LaraGram\Hashing\HashManager setContainer(\LaraGram\Contracts\Container\Container $container)
 * @method static \LaraGram\Hashing\HashManager forgetDrivers()
 *
 * @see \LaraGram\Hashing\HashManager
 * @see \LaraGram\Hashing\AbstractHasher
 */
class Hash extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'hash';
    }
}
