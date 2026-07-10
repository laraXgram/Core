<?php

namespace LaraGram\Foundation;

use LaraGram\Support\Manager;

class MaintenanceModeManager extends Manager
{
    /**
     * Create an instance of the file based maintenance driver.
     *
     * @return \LaraGram\Foundation\FileBasedMaintenanceMode
     */
    protected function createFileDriver(): FileBasedMaintenanceMode
    {
        return new FileBasedMaintenanceMode();
    }

    /**
     * Create an instance of the array based maintenance driver.
     *
     * @return \LaraGram\Foundation\ArrayMaintenanceMode
     */
    protected function createArrayDriver(): ArrayMaintenanceMode
    {
        return new ArrayMaintenanceMode();
    }

    /**
     * Create an instance of the cache based maintenance driver.
     *
     * @return \LaraGram\Foundation\CacheBasedMaintenanceMode
     *
     * @throws \LaraGram\Contracts\Container\BindingResolutionException
     */
    protected function createCacheDriver(): CacheBasedMaintenanceMode
    {
        return new CacheBasedMaintenanceMode(
            $this->container->make('cache'),
            $this->config->get('app.maintenance.store') ?: $this->config->get('cache.default'),
            'laragram:foundation:down'
        );
    }

    /**
     * Get the default driver name.
     *
     * @return string
     */
    public function getDefaultDriver(): string
    {
        return $this->config->get('app.maintenance.driver', 'file');
    }
}
