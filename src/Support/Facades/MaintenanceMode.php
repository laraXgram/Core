<?php

namespace LaraGram\Support\Facades;

use LaraGram\Foundation\MaintenanceModeManager;
use LaraGram\Support\Facades\Facade;

/**
 * @method static string getDefaultDriver()
 * @method static mixed driver(\UnitEnum|string|null $driver = null)
 * @method static \LaraGram\Foundation\MaintenanceModeManager extend(string $driver, \Closure $callback)
 * @method static array getDrivers()
 * @method static \LaraGram\Contracts\Container\Container getContainer()
 * @method static \LaraGram\Foundation\MaintenanceModeManager setContainer(\LaraGram\Contracts\Container\Container $container)
 * @method static \LaraGram\Foundation\MaintenanceModeManager forgetDrivers()
 *
 * @see \LaraGram\Foundation\MaintenanceModeManager
 */
class MaintenanceMode extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return MaintenanceModeManager::class;
    }
}
