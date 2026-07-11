<?php

namespace LaraGram\Support\Facades;

/**
 * @method static bool enabled()
 * @method static array all()
 * @method static \LaraGram\Request\Proxy\Proxy add(\LaraGram\Request\Proxy\Proxy|array|string $proxy, string|null $name = null)
 * @method static bool remove(string $id)
 * @method static \LaraGram\Request\Proxy\Proxy|null get(string $id)
 * @method static array list()
 * @method static \LaraGram\Request\Proxy\Proxy|null current()
 * @method static \LaraGram\Request\Proxy\Proxy use(string $id)
 * @method static \LaraGram\Request\Proxy\Proxy|null rotate()
 * @method static void markDown(\LaraGram\Request\Proxy\Proxy $proxy)
 * @method static void markUp(\LaraGram\Request\Proxy\Proxy $proxy)
 * @method static bool isDown(\LaraGram\Request\Proxy\Proxy $proxy)
 * @method static array down()
 * @method static array up()
 * @method static void resetDown()
 * @method static float|false ping(\LaraGram\Request\Proxy\Proxy|string|null $proxy = null)
 * @method static array pingAll()
 * @method static array stats()
 * @method static mixed dispatch(string $token, string $apiServer, string $method, array $params, bool $noResponse = false, string|null $forced = null)
 *
 * @see \LaraGram\Request\Proxy\ProxyManager
 */
class Proxy extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'proxy';
    }
}
