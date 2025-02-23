<?php

namespace LaraGram\Support\Facades;

/**
 * @method static \LaraGram\Log\LoggerInterface build(array $config)
 * @method static \LaraGram\Log\LoggerInterface stack(array $channels, string|null $channel = null)
 * @method static \LaraGram\Log\LoggerInterface channel(string|null $channel = null)
 * @method static \LaraGram\Log\LoggerInterface driver(string|null $driver = null)
 * @method static \LaraGram\Log\LogManager shareContext(array $context)
 * @method static array sharedContext()
 * @method static \LaraGram\Log\LogManager withoutContext()
 * @method static \LaraGram\Log\LogManager flushSharedContext()
 * @method static string|null getDefaultDriver()
 * @method static void setDefaultDriver(string $name)
 * @method static \LaraGram\Log\LogManager extend(string $driver, \Closure $callback)
 * @method static void forgetChannel(string|null $driver = null)
 * @method static array getChannels()
 * @method static void emergency(string|\Stringable $message, array $context = [])
 * @method static void alert(string|\Stringable $message, array $context = [])
 * @method static void critical(string|\Stringable $message, array $context = [])
 * @method static void error(string|\Stringable $message, array $context = [])
 * @method static void warning(string|\Stringable $message, array $context = [])
 * @method static void notice(string|\Stringable $message, array $context = [])
 * @method static void info(string|\Stringable $message, array $context = [])
 * @method static void debug(string|\Stringable $message, array $context = [])
 * @method static void log(mixed $level, string|\Stringable $message, array $context = [])
 * @method static \LaraGram\Log\LogManager setApplication(\LaraGram\Contracts\Foundation\Application $app)
 * @method static void write(string $level, \LaraGram\Contracts\Support\Arrayable|\LaraGram\Contracts\Support\Jsonable|\LaraGram\Support\Stringable|array|string $message, array $context = [])
 * @method static \LaraGram\Log\Logger withContext(array $context = [])
 * @method static void listen(\Closure $callback)
 * @method static \LaraGram\Log\LoggerInterface getLogger()
 * @method static \LaraGram\Contracts\Events\Dispatcher getEventDispatcher()
 * @method static void setEventDispatcher(\LaraGram\Contracts\Events\Dispatcher $dispatcher)
 * @method static \LaraGram\Log\Logger|mixed when(\Closure|mixed|null $value = null, callable|null $callback = null, callable|null $default = null)
 * @method static \LaraGram\Log\Logger|mixed unless(\Closure|mixed|null $value = null, callable|null $callback = null, callable|null $default = null)
 *
 * @see \LaraGram\Log\LogManager
 */
class Log extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'log';
    }
}
