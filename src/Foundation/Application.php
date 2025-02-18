<?php

namespace LaraGram\Foundation;

use Closure;
use Composer\Autoload\ClassLoader;
use LaraGram\Console\Input\InputInterface;
use LaraGram\Console\Output\ConsoleOutput;
use LaraGram\Container\Container;
use LaraGram\Contracts\Foundation\Application as ApplicationContract;
use LaraGram\Contracts\Console\Kernel as ConsoleKernelContract;
use LaraGram\Contracts\Foundation\CachesConfiguration;
use LaraGram\Conversation\ConversationListener;
use LaraGram\Events\EventServiceProvider;
use LaraGram\Filesystem\Filesystem;
use LaraGram\Filesystem\FilesystemServiceProvider;
use LaraGram\Laraquest\Laraquest;
use LaraGram\Support\Traits\Macroable;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;

class Application extends Container implements ApplicationContract, CachesConfiguration
{
    use Macroable;

    const VERSION = "2.5.0";

    protected string|null $basePath;
    protected array $registeredCallbacks = [];
    protected bool $hasBeenBootstrapped = false;
    protected bool $booted = false;
    protected array $bootingCallbacks = [];
    protected array $bootedCallbacks = [];
    protected array $terminatingCallbacks = [];
    protected array $serviceProviders = [];
    protected array $loadedProviders = [];
    protected array $deferredServices = [];
    protected string $bootstrapPath = '';
    protected string $appPath = '';
    protected string $configPath = '';
    protected string $databasePath = '';
    protected string $langPath = '';
    protected string $assetsPath = '';
    protected string $storagePath = '';
    protected ?bool $isRunningInConsole = null;
    protected string $namespace;
    protected array $absoluteCachePathPrefixes = ['/', '\\'];
    protected bool $mergeFrameworkConfiguration = true;

    public function __construct(string $basePath = null)
    {
        if ($basePath) {
            $this->setBasePath($basePath);
        }

        $this->registerBaseBindings();
        $this->registerBaseServiceProviders();
        $this->registerCoreContainerAliases();

        return $this;
    }

    public static function configure(?string $basePath = null): Configuration\ApplicationBuilder
    {
        $basePath = match (true) {
            is_string($basePath) => $basePath,
            default => static::inferBasePath(),
        };

        return (new Configuration\ApplicationBuilder(new static($basePath)))
            ->withKernels()
            ->withEvents()
            ->withCommands()
            ->withProviders();
    }

    public static function inferBasePath()
    {
        return match (true) {
            config('base_path') !== null => config('base_path'),
            default => dirname(array_keys(ClassLoader::getRegisteredLoaders())[0]),
        };
    }

    public function version(): string
    {
        return static::VERSION;
    }

    protected function registerBaseBindings()
    {
        static::setInstance($this);

        $this->instance('app', $this);
        $this->instance(Container::class, $this);

        $this->singleton(PackageManifest::class, fn() => new PackageManifest(
            new Filesystem, $this->basePath(), $this->getCachedPackagesPath()
        ));
    }


    protected function registerBaseServiceProviders()
    {
        $this->register(new EventServiceProvider($this));
        $this->register(new FilesystemServiceProvider($this));
    }

    public function bootstrapWith(array $bootstrappers)
    {
        $this->hasBeenBootstrapped = true;

        foreach ($bootstrappers as $bootstrapper) {
            $this['events']->dispatch('bootstrapping: ' . $bootstrapper, [$this]);

            $this->make($bootstrapper)->bootstrap($this);

            $this['events']->dispatch('bootstrapped: ' . $bootstrapper, [$this]);
        }
    }

    public function beforeBootstrapping($bootstrapper, Closure $callback)
    {
        $this['events']->listen('bootstrapping: ' . $bootstrapper, $callback);
    }

    public function afterBootstrapping($bootstrapper, Closure $callback)
    {
        $this['events']->listen('bootstrapped: ' . $bootstrapper, $callback);
    }

    public function hasBeenBootstrapped(): bool
    {
        return $this->hasBeenBootstrapped;
    }

    public function setBasePath($basePath): static
    {
        $this->basePath = rtrim($basePath, '\/');

        $this->bindPathsInContainer();

        return $this;
    }

    protected function bindPathsInContainer(): void
    {
        $this->instance('path.laragram', dirname(__DIR__));
        $this->instance('path.base', $this->basePath());
        $this->instance('path.app', $this->appPath());
        $this->instance('path.storage', $this->storagePath());
        $this->instance('path.config', $this->configPath());
        $this->instance('path.database', $this->databasePath());
        $this->instance('path.assets', $this->assetsPath());
        $this->instance('path.bootstrap', $this->bootstrapPath());

        $this->useLangPath(
            is_dir($this->assetsPath('lang'))
                ? $this->assetsPath('lang')
                : $this->basePath('lang')
        );
    }

    public function basePath($path = ''): string
    {
        return $this->joinPaths($this->basePath, $path);
    }

    public function appPath($path = ''): string
    {
        return $this->joinPaths($this->appPath ?: $this->basePath('app'), $path);
    }

    public function useAppPath($path)
    {
        $this->appPath = $path;

        $this->instance('path', $path);

        return $this;
    }

    public function storagePath($path = ''): string
    {
        return $this->joinPaths($this->storagePath ?: $this->basePath('storage'), $path);
    }

    public function useStoragePath($path)
    {
        $this->storagePath = $path;

        $this->instance('path.storage', $path);

        return $this;
    }

    public function configPath($path = ''): string
    {
        return $this->joinPaths($this->configPath ?: $this->basePath('config'), $path);
    }

    public function useConfigPath($path)
    {
        $this->configPath = $path;

        $this->instance('path.config', $path);

        return $this;
    }

    public function databasePath($path = ''): string
    {
        return $this->joinPaths($this->databasePath ?: $this->basePath('database'), $path);
    }

    public function useDatabasePath($path)
    {
        $this->databasePath = $path;

        $this->instance('path.database', $path);

        return $this;
    }

    public function assetsPath($path = ''): string
    {
        return $this->joinPaths($this->assetsPath ?: $this->basePath('assets'), $path);
    }

    public function useAssetesPath($path)
    {
        $this->publicPath = $path;

        $this->instance('path.assets', $path);

        return $this;
    }

    public function bootstrapPath($path = ''): string
    {
        return $this->joinPaths($this->bootstrapPath ?: $this->basePath('bootstrap'), $path);
    }

    public function useBootstrapPath($path)
    {
        $this->bootstrapPath = $path;

        $this->instance('path.bootstrap', $path);

        return $this;
    }

    public function langPath($path = ''): string
    {
        return $this->joinPaths($this->langPath ?: $this->basePath('lang'), $path);
    }

    public function useLangPath($path)
    {
        $this->langPath = $path;

        $this->instance('path.lang', $path);

        return $this;
    }

    public function getBootstrapProvidersPath(): string
    {
        return $this->bootstrapPath('providers.php');
    }

    public function joinPaths($basePath, ...$paths): string
    {
        foreach ($paths as $index => $path) {
            if (empty($path)) {
                unset($paths[$index]);
            } else {
                $paths[$index] = DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
            }
        }

        return $basePath . implode('', $paths);
    }

    public function runningInConsole()
    {
        if ($this->isRunningInConsole === null) {
            $this->isRunningInConsole = (\PHP_SAPI === 'cli' || \PHP_SAPI === 'phpdbg');
        }

        return $this->isRunningInConsole;
    }

    public function runningConsoleCommand(...$commands)
    {
        if (!$this->runningInConsole()) {
            return false;
        }

        return in_array(
            $_SERVER['argv'][1] ?? null,
            is_array($commands[0]) ? $commands[0] : $commands
        );
    }

    public function registered($callback): void
    {
        $this->registeredCallbacks[] = $callback;
    }

    public function registerConfiguredProviders(): void
    {
        $config = $this->make('config')->get('app.providers');

        $providersLaraGram = [];
        $otherProviders = [];

        foreach ($config as $provider) {
            if (str_starts_with($provider, 'LaraGram\\')) {
                $providersLaraGram[] = $provider;
            } else {
                $otherProviders[] = $provider;
            }
        }

        $packageManifestProviders = $this->make(PackageManifest::class)->providers();
        array_splice($otherProviders, 0, 0, $packageManifestProviders);

        $allProviders = array_merge($providersLaraGram, $otherProviders);

        $providerRepository = new ProviderRepository($this, new Filesystem, $this->getCachedServicesPath());
        $providerRepository->load($allProviders);

        $this->fireAppCallbacks($this->registeredCallbacks);
    }

    public function register($provider, $force = false)
    {
        if (($registered = $this->getProvider($provider)) && !$force) {
            return $registered;
        }

        if (is_string($provider)) {
            $provider = $this->resolveProvider($provider);
        }

        $provider->register();

        if (property_exists($provider, 'bindings')) {
            foreach ($provider->bindings as $key => $value) {
                $this->bind($key, $value);
            }
        }

        if (property_exists($provider, 'singletons')) {
            foreach ($provider->singletons as $key => $value) {
                $key = is_int($key) ? $value : $key;

                $this->singleton($key, $value);
            }
        }

        $this->markAsRegistered($provider);

        if ($this->isBooted()) {
            $this->bootProvider($provider);
        }

        return $provider;
    }

    public function getProvider($provider)
    {
        $name = is_string($provider) ? $provider : get_class($provider);

        return $this->serviceProviders[$name] ?? null;
    }

    public function getProviders($provider): array
    {
        $name = is_string($provider) ? $provider : get_class($provider);
        return array_filter($this->serviceProviders, function ($value) use ($name) {
            return $value instanceof $name;
        });
    }

    public function resolveProvider($provider)
    {
        return new $provider($this);
    }

    protected function markAsRegistered($provider): void
    {
        $class = get_class($provider);

        $this->serviceProviders[$class] = $provider;

        $this->loadedProviders[$class] = true;
    }

    public function loadDeferredProviders(): void
    {
        foreach ($this->deferredServices as $service => $provider) {
            $this->loadDeferredProvider($service);
        }

        $this->deferredServices = [];
    }

    public function loadDeferredProvider($service): void
    {
        if (!$this->isDeferredService($service)) {
            return;
        }

        $provider = $this->deferredServices[$service];

        if (!isset($this->loadedProviders[$provider])) {
            $this->registerDeferredProvider($provider, $service);
        }
    }

    public function registerDeferredProvider($provider, $service = null): void
    {
        if ($service) {
            unset($this->deferredServices[$service]);
        }

        $this->register($instance = new $provider($this));

        if ($this->isBooted()) {
            $this->booting(function () use ($instance) {
                $this->bootProvider($instance);
            });
        }
    }

    public function make($abstract, array $parameters = [])
    {
        $this->loadDeferredProviderIfNeeded($abstract = $this->getAlias($abstract));

        return parent::make($abstract, $parameters);
    }

    protected function resolve($abstract, $parameters = [], $raiseEvents = true)
    {
        $this->loadDeferredProviderIfNeeded($abstract = $this->getAlias($abstract));

        return parent::resolve($abstract, $parameters, $raiseEvents);
    }

    protected function loadDeferredProviderIfNeeded($abstract)
    {
        if ($this->isDeferredService($abstract) && !isset($this->instances[$abstract])) {
            $this->loadDeferredProvider($abstract);
        }
    }

    public function bound($abstract): bool
    {
        return $this->isDeferredService($abstract) || parent::bound($abstract);
    }

    public function isBooted(): bool
    {
        return $this->booted;
    }

    public function boot(): void
    {
        if ($this->isBooted()) {
            return;
        }

        $this->fireAppCallbacks($this->bootingCallbacks);

        array_walk($this->serviceProviders, function ($p) {
            $this->bootProvider($p);
        });

        $this->booted = true;

        $this->fireAppCallbacks($this->bootedCallbacks);
    }

    protected function bootProvider($provider): void
    {
        $provider->callBootingCallbacks();

        if (method_exists($provider, 'boot')) {
            $this->call([$provider, 'boot']);
        }

        $provider->callBootedCallbacks();
    }

    public function booting($callback): void
    {
        $this->bootingCallbacks[] = $callback;
    }

    public function booted($callback): void
    {
        $this->bootedCallbacks[] = $callback;

        if ($this->isBooted()) {
            $callback($this);
        }
    }

    protected function fireAppCallbacks(array &$callbacks): void
    {
        $index = 0;

        while ($index < count($callbacks)) {
            $callbacks[$index]($this);

            $index++;
        }
    }

    public function handleCommand(InputInterface $input)
    {
        $kernel = $this->make(ConsoleKernelContract::class);

        $status = $kernel->handle(
            $input,
            new ConsoleOutput
        );

        $kernel->terminate($input, $status);

        return $status;
    }

    public function shouldMergeFrameworkConfiguration()
    {
        return $this->mergeFrameworkConfiguration;
    }

    public function getCachedServicesPath(): string
    {
        return $this->bootstrapPath('cache/services.php');
    }

    public function getCachedPackagesPath(): string
    {
        return $this->bootstrapPath('cache/packages.php');
    }

    public function configurationIsCached(): bool
    {
        return is_file($this->getCachedConfigPath());
    }

    public function getCachedConfigPath(): string
    {
        return $this->bootstrapPath('cache/config.php');
    }

    public function eventsAreCached()
    {
        return $this['files']->exists($this->getCachedEventsPath());
    }

    public function getCachedEventsPath()
    {
        return $this->bootstrapPath('cache/events.php');
    }

    public function addAbsoluteCachePathPrefix($prefix)
    {
        $this->absoluteCachePathPrefixes[] = $prefix;

        return $this;
    }

    public function terminating($callback)
    {
        $this->terminatingCallbacks[] = $callback;

        return $this;
    }

    public function terminate()
    {
        $index = 0;

        while ($index < count($this->terminatingCallbacks)) {
            $this->call($this->terminatingCallbacks[$index]);

            $index++;
        }
    }

    public function getLoadedProviders(): array
    {
        return $this->loadedProviders;
    }

    public function providerIsLoaded(string $provider): bool
    {
        return isset($this->loadedProviders[$provider]);
    }

    public function getDeferredServices(): array
    {
        return $this->deferredServices;
    }

    public function setDeferredServices(array $services): void
    {
        $this->deferredServices = $services;
    }

    public function isDeferredService($service): bool
    {
        return isset($this->deferredServices[$service]);
    }

    public function addDeferredServices(array $services): void
    {
        $this->deferredServices = array_merge($this->deferredServices, $services);
    }

    public function removeDeferredServices(array $services)
    {
        foreach ($services as $service) {
            unset($this->deferredServices[$service]);
        }
    }

    public function provideFacades($namespace)
    {
        AliasLoader::setFacadeNamespace($namespace);
    }

    protected function registerCoreContainerAliases(): static
    {
        foreach ([
                     'app' => [self::class, Container::class],
                     'auth' => [\LaraGram\Auth\Auth::class],
                     'auth.level' => [\LaraGram\Auth\Level::class],
                     'auth.role' => [\LaraGram\Auth\Role::class],
                     'cache' => [\LaraGram\Cache\CacheManager::class, \LaraGram\Contracts\Cache\Factory::class],
                     'cache.store' => [\LaraGram\Cache\Repository::class, \LaraGram\Contracts\Cache\Repository::class],
                     'config' => [\LaraGram\Config\Repository::class, \LaraGram\Contracts\Config\Repository::class],
                     'db' => [\LaraGram\Database\DatabaseManager::class, \LaraGram\Database\ConnectionResolverInterface::class],
                     'db.connection' => [\LaraGram\Database\Connection::class, \LaraGram\Database\ConnectionInterface::class],
                     'db.schema' => [\LaraGram\Database\Schema\Builder::class],
                     'encrypter' => [\LaraGram\Encryption\Encrypter::class, \LaraGram\Contracts\Encryption\Encrypter::class, \LaraGram\Contracts\Encryption\StringEncrypter::class],
                     'events' => [\LaraGram\Events\Dispatcher::class, \LaraGram\Contracts\Events\Dispatcher::class],
                     'files' => [\LaraGram\Filesystem\Filesystem::class],
                     'filesystem' => [\LaraGram\Filesystem\FilesystemManager::class, \LaraGram\Contracts\Filesystem\Factory::class],
                     'filesystem.disk' => [\LaraGram\Contracts\Filesystem\Filesystem::class],
                     'hash' => [\LaraGram\Hashing\HashManager::class],
                     'hash.driver' => [\LaraGram\Contracts\Hashing\Hasher::class],
                     'translator' => [\LaraGram\Translation\Translator::class, \LaraGram\Contracts\Translation\Translator::class],
                     'queue' => [\LaraGram\Queue\QueueManager::class, \LaraGram\Contracts\Queue\Factory::class, \LaraGram\Contracts\Queue\Monitor::class],
                     'queue.connection' => [\LaraGram\Contracts\Queue\Queue::class],
                     'queue.failer' => [\LaraGram\Queue\Failed\FailedJobProviderInterface::class],
                     'redis' => [\LaraGram\Redis\RedisManager::class, \LaraGram\Contracts\Redis\Factory::class],
                     'redis.connection' => [\LaraGram\Redis\Connections\Connection::class, \LaraGram\Contracts\Redis\Connection::class],
                     'request' => [\LaraGram\Request\Request::class],
                     'listener' => [\LaraGram\Listener\Listener::class, \LaraGram\Listener\Group::class],
                     'conversation' => [\LaraGram\Conversation\Conversation::class],
                     'keyboard' => [\LaraGram\Keyboard\Keyboard::class],
                 ] as $key => $aliases) {
            foreach ($aliases as $alias) {
                $this->alias($key, $alias);
            }
        }

        return $this;
    }

    public function flush(): void
    {
        parent::flush();

        $this->buildStack = [];
        $this->loadedProviders = [];
        $this->bootedCallbacks = [];
        $this->bootingCallbacks = [];
        $this->deferredServices = [];
        $this->reboundCallbacks = [];
        $this->serviceProviders = [];
        $this->resolvingCallbacks = [];
        $this->terminatingCallbacks = [];
        $this->beforeResolvingCallbacks = [];
        $this->afterResolvingCallbacks = [];
        $this->globalBeforeResolvingCallbacks = [];
        $this->globalResolvingCallbacks = [];
        $this->globalAfterResolvingCallbacks = [];
    }

    public function loadResources($once = true): void
    {
        $directory = app('path.app') . DIRECTORY_SEPARATOR . 'Resources';
        $files = [];
        $iterator = new \FilesystemIterator($directory, \FilesystemIterator::SKIP_DOTS);
        foreach ($iterator as $fileinfo) {
            if ($fileinfo->isFile()) {
                $files[] = $fileinfo->getFilename();
            }
        }

        foreach ($files as $file) {
            if ($once) {
                require_once $directory . DIRECTORY_SEPARATOR . $file;
            } else {
                require $directory . DIRECTORY_SEPARATOR . $file;
            }
        }

        if (file_exists($this->storagePath('app/cache/conversation/' . md5("conversation." . (user()->id ?? callback_query()->from->id ?? '')) . '.cache'))) {
            $this->make(ConversationListener::class);
        }
    }

    public function handleRequest()
    {
        $this[ConsoleKernelContract::class]->bootstrap();

        $update_type = config('laraquest.update_type');
        if ($update_type == 'openswoole') {
            $ip = config('server.openswoole.ip');
            $port = config('server.openswoole.port');
            $server = new Server($ip, $port);

            $server->on("request", function (Request $swooleRequest, Response $swooleResponse) {
                $swooleResponse->end();
                global $swoole;
                $swoole = json_decode($swooleRequest->getContent());

                app()->loadResources(false);
            });

            $server->start();
        } elseif ($update_type == 'polling') {
            Laraquest::polling(function () {
                $this->loadResources(false);
            });
        } else {
            global $data;
            global $argv;
            $data = json_decode($argv[1] ?? '{}');

            $this->loadResources();
        }
    }

    public function getNamespace()
    {
        if (!empty($this->namespace)) {
            return $this->namespace;
        }

        $composer = json_decode(file_get_contents($this->basePath('composer.json')), true);

        foreach ((array)data_get($composer, 'autoload.psr-4') as $namespace => $path) {
            foreach ((array)$path as $pathChoice) {
                if (realpath($this->appPath()) === realpath($this->basePath($pathChoice))) {
                    return $this->namespace = $namespace;
                }
            }
        }

        throw new \RuntimeException('Unable to detect application namespace.');
    }
}