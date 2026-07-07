<?php

namespace LaraGram\Foundation\Bootstrap;

use Closure;
use LaraGram\Config\Repository;
use LaraGram\Contracts\Config\Repository as RepositoryContract;
use LaraGram\Contracts\Foundation\Application;
use LaraGram\Support\Collection;
use LaraGram\Support\Finder\Finder;
use SplFileInfo;

class LoadConfiguration
{
    /**
     * The closure that resolves the permanent, static configuration if applicable.
     *
     * @var (Closure(\LaraGram\Contracts\Foundation\Application): array<array-key, mixed>)|null
     */
    protected static ?Closure $alwaysUseConfig = null;

    public function bootstrap(Application $app)
    {
        $items = [];

        $loadedFromCache = false;

        if (self::$alwaysUseConfig !== null) {
            $items = $app->call(self::$alwaysUseConfig);

            $loadedFromCache = true;
        } elseif (file_exists($cached = $app->getCachedConfigPath())) {
            $items = require $cached;

            $loadedFromCache = true;
        }

        $app->instance('config_loaded_from_cache', false);

        $app->instance('config', $config = new Repository($items));

        if (! $loadedFromCache) {
            $this->loadConfigurationFiles($app, $config);
        }

        $app->detectEnvironment(fn () => $config->get('app.env', 'production'));

        $app->resolveEnvironmentUsing($app->environment(...));

        date_default_timezone_set($config->get('app.timezone', 'UTC'));

        mb_internal_encoding('UTF-8');
    }

    protected function loadConfigurationFiles(Application $app, RepositoryContract $repository)
    {
        $files = $this->getConfigurationFiles($app);

        $shouldMerge = method_exists($app, 'shouldMergeFrameworkConfiguration')
            ? $app->shouldMergeFrameworkConfiguration()
            : true;

        $base = $shouldMerge
            ? $this->getBaseConfiguration()
            : [];

        foreach ((new Collection($base))->diffKeys($files) as $name => $config) {
            $repository->set($name, $config);
        }

        foreach ($files as $name => $path) {
            $base = $this->loadConfigurationFile($repository, $name, $path, $base);
        }

        foreach ($base as $name => $config) {
            $repository->set($name, $config);
        }
    }

    protected function loadConfigurationFile(RepositoryContract $repository, $name, $path, array $base)
    {
        $config = (fn () => require $path)();

        if (isset($base[$name])) {
            $config = array_merge($base[$name], $config);

            foreach ($this->mergeableOptions($name) as $option) {
                if (isset($config[$option])) {
                    $config[$option] = array_merge($base[$name][$option], $config[$option]);
                }
            }

            unset($base[$name]);
        }

        $repository->set($name, $config);

        return $base;
    }
    protected function mergeableOptions($name)
    {
        return [
            'auth' => ['providers'],
            'cache' => ['stores'],
            'database' => ['connections'],
            'filesystems' => ['disks'],
            'logging' => ['channels'],
            'queue' => ['connections'],
        ][$name] ?? [];
    }

    protected function getConfigurationFiles(Application $app)
    {
        $files = [];

        $configPath = realpath($app->configPath());

        if (! $configPath) {
            return [];
        }

        foreach (Finder::create()->files()->name('*.php')->in($configPath) as $file) {
            $directory = $this->getNestedDirectory($file, $configPath);

            $files[$directory.basename($file->getRealPath(), '.php')] = $file->getRealPath();
        }

        ksort($files, SORT_NATURAL);

        return $files;
    }

    protected function getNestedDirectory(SplFileInfo $file, $configPath)
    {
        $directory = $file->getPath();

        if ($nested = trim(str_replace($configPath, '', $directory), DIRECTORY_SEPARATOR)) {
            $nested = str_replace(DIRECTORY_SEPARATOR, '.', $nested).'.';
        }

        return $nested;
    }

    protected function getBaseConfiguration()
    {
        $config = [];

        foreach (Finder::create()->files()->name('*.php')->in(__DIR__.'/../../../config') as $file) {
            $config[basename($file->getRealPath(), '.php')] = require $file->getRealPath();
        }

        return $config;
    }

    /**
     * Set a callback to return the permanent, static configuration values.
     *
     * @param  (Closure(Application): array<array-key, mixed>)|null  $alwaysUseConfig
     * @return void
     */
    public static function alwaysUse(?Closure $alwaysUseConfig): void
    {
        static::$alwaysUseConfig = $alwaysUseConfig;
    }
}
