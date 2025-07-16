<?php

namespace LaraGram\Support\Facades;

use LaraGram\Contracts\Auth\Access\Gate as GateContract;

/**
 * @method static bool has(string|array $ability)
 * @method static \LaraGram\Auth\Access\Response allowIf(\LaraGram\Auth\Access\Response|\Closure|bool $condition, string|null $message = null, string|null $code = null)
 * @method static \LaraGram\Auth\Access\Response denyIf(\LaraGram\Auth\Access\Response|\Closure|bool $condition, string|null $message = null, string|null $code = null)
 * @method static \LaraGram\Auth\Access\Gate define(\UnitEnum|string $ability, callable|array|string $callback)
 * @method static \LaraGram\Auth\Access\Gate policy(string $class, string $policy)
 * @method static \LaraGram\Auth\Access\Gate before(callable $callback)
 * @method static \LaraGram\Auth\Access\Gate after(callable $callback)
 * @method static bool allows(iterable|\UnitEnum|string $ability, array|mixed $arguments = [])
 * @method static bool denies(iterable|\UnitEnum|string $ability, array|mixed $arguments = [])
 * @method static bool check(iterable|\UnitEnum|string $abilities, array|mixed $arguments = [])
 * @method static bool any(iterable|\UnitEnum|string $abilities, array|mixed $arguments = [])
 * @method static bool none(iterable|\UnitEnum|string $abilities, array|mixed $arguments = [])
 * @method static \LaraGram\Auth\Access\Response authorize(\UnitEnum|string $ability, array|mixed $arguments = [])
 * @method static \LaraGram\Auth\Access\Response inspect(\UnitEnum|string $ability, array|mixed $arguments = [])
 * @method static mixed raw(string $ability, array|mixed $arguments = [])
 * @method static mixed getPolicyFor(object|string $class)
 * @method static \LaraGram\Auth\Access\Gate guessPolicyNamesUsing(callable $callback)
 * @method static mixed resolvePolicy(object|string $class)
 * @method static \LaraGram\Auth\Access\Gate forUser(\LaraGram\Contracts\Auth\Authenticatable|mixed $user)
 * @method static array abilities()
 * @method static array policies()
 * @method static \LaraGram\Auth\Access\Gate defaultDenialResponse(\LaraGram\Auth\Access\Response $response)
 * @method static \LaraGram\Auth\Access\Gate setContainer(\LaraGram\Contracts\Container\Container $container)
 * @method static \LaraGram\Auth\Access\Response denyWithStatus(int $status, string|null $message = null, int|null $code = null)
 * @method static \LaraGram\Auth\Access\Response denyAsNotFound(string|null $message = null, int|null $code = null)
 *
 * @see \LaraGram\Auth\Access\Gate
 */
class Gate extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return GateContract::class;
    }
}
