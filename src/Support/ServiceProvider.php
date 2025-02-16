<?php

namespace LaraGram\Support;

use Closure;
use LaraGram\Contracts\Foundation\Application;
use LaraGram\Contracts\Foundation\CachesConfiguration;
use LaraGram\Contracts\Support\DeferrableProvider;
use LaraGram\Console\Application as Commander;

abstract class ServiceProvider
{
    protected Application $app;
    protected array $bootingCallbacks = [];
    protected array $bootedCallbacks = [];
    public static $publishes = [];
    public static $publishGroups = [];
    public static array $optimizeCommands = [];
    public static array $optimizeClearCommands = [];
    protected static $publishableMigrationPaths = [];

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function register()
    {
        //
    }

    public function booting(Closure $callback): void
    {
        $this->bootingCallbacks[] = $callback;
    }

    public function booted(Closure $callback): void
    {
        $this->bootedCallbacks[] = $callback;
    }

    public function callBootingCallbacks(): void
    {
        $index = 0;

        while ($index < count($this->bootingCallbacks)) {
            $this->app->call($this->bootingCallbacks[$index]);

            $index++;
        }
    }

    public function callBootedCallbacks(): void
    {
        $index = 0;

        while ($index < count($this->bootedCallbacks)) {
            $this->app->call($this->bootedCallbacks[$index]);

            $index++;
        }
    }

    protected function mergeConfigFrom($path, $key)
    {
        if (!($this->app instanceof CachesConfiguration && $this->app->configurationIsCached())) {
            $config = $this->app->make('config');

            $config->set($key, array_merge(
                require $path, $config->get($key, [])
            ));
        }
    }

    protected function replaceConfigRecursivelyFrom($path, $key)
    {
        if (!($this->app instanceof CachesConfiguration && $this->app->configurationIsCached())) {
            $config = $this->app->make('config');

            $config->set($key, array_replace_recursive(
                require $path, $config->get($key, [])
            ));
        }
    }

    protected function callAfterResolving($name, $callback): void
    {
        $this->app->afterResolving($name, $callback);

        if ($this->app->resolved($name)) {
            $callback($this->app->make($name), $this->app);
        }
    }

    protected function publishesMigrations(array $paths, $groups = null)
    {
        $this->publishes($paths, $groups);

        if ($this->app['config']->get('database.migrations.update_date_on_publish', false)) {
            static::$publishableMigrationPaths = array_unique(array_merge(static::$publishableMigrationPaths, array_keys($paths)));
        }
    }

    protected function publishes(array $paths, $groups = null): void
    {
        $this->ensurePublishArrayInitialized($class = static::class);

        static::$publishes[$class] = array_merge(static::$publishes[$class], $paths);

        foreach ((array)$groups as $group) {
            $this->addPublishGroup($group, $paths);
        }
    }

    protected function ensurePublishArrayInitialized($class): void
    {
        if (!array_key_exists($class, static::$publishes)) {
            static::$publishes[$class] = [];
        }
    }

    protected function addPublishGroup($group, $paths): void
    {
        if (!array_key_exists($group, static::$publishGroups)) {
            static::$publishGroups[$group] = [];
        }

        static::$publishGroups[$group] = array_merge(
            static::$publishGroups[$group], $paths
        );
    }

    public static function pathsToPublish($provider = null, $group = null)
    {
        if (!is_null($paths = static::pathsForProviderOrGroup($provider, $group))) {
            return $paths;
        }

        return array_reduce(static::$publishes, function ($paths, $p) {
            return array_merge($paths, $p);
        }, []);
    }

    protected static function pathsForProviderOrGroup($provider, $group)
    {
        if ($provider && $group) {
            return static::pathsForProviderAndGroup($provider, $group);
        } elseif ($group && array_key_exists($group, static::$publishGroups)) {
            return static::$publishGroups[$group];
        } elseif ($provider && array_key_exists($provider, static::$publishes)) {
            return static::$publishes[$provider];
        } elseif ($group || $provider) {
            return [];
        }
    }

    protected static function pathsForProviderAndGroup($provider, $group): array
    {
        if (!empty(static::$publishes[$provider]) && !empty(static::$publishGroups[$group])) {
            return array_intersect_key(static::$publishes[$provider], static::$publishGroups[$group]);
        }

        return [];
    }

    public static function publishableProviders(): array
    {
        return array_keys(static::$publishes);
    }

    public static function publishableMigrationPaths()
    {
        return static::$publishableMigrationPaths;
    }

    public static function publishableGroups(): array
    {
        return array_keys(static::$publishGroups);
    }

    public function commands($commands): void
    {
        $commands = is_array($commands) ? $commands : func_get_args();

        Commander::starting(function ($commander) use ($commands) {
            $commander->resolveCommands($commands);
        });
    }

    protected function optimizes(?string $optimize = null, ?string $clear = null, ?string $key = null)
    {
        $key ??= strtolower(trim(preg_replace('/(?<!^)[A-Z]/', '-$0', str_replace('ServiceProvider', '', basename(str_replace('\\', '/', get_class($this)))))));

        if (empty($key)) {
            $key = class_basename(get_class($this));
        }

        if ($optimize) {
            static::$optimizeCommands[$key] = $optimize;
        }

        if ($clear) {
            static::$optimizeClearCommands[$key] = $clear;
        }
    }


    public function provides(): array
    {
        return [];
    }

    public function when(): array
    {
        return [];
    }

    public function isDeferred(): bool
    {
        return $this instanceof DeferrableProvider;
    }

    public static function defaultProviders()
    {
        return new DefaultProviders;
    }

    public static function addProviderToBootstrapFile(string $provider, ?string $path = null)
    {
        $path = $path ?? app()->getBootstrapProvidersPath();

        if (!file_exists($path)) {
            return false;
        }

        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($path, true);
        }

        $providers = require $path;

        if (!in_array($provider, $providers)) {
            $providers[] = $provider;
        }

        sort($providers);

        $formattedProviders = '';
        foreach ($providers as $p) {
            $formattedProviders .= '    ' . $p . '::class,' . PHP_EOL;
        }

        $content = '<?php

return [
' . $formattedProviders . '
];';

        file_put_contents($path, $content . PHP_EOL);

        return true;
    }
}
