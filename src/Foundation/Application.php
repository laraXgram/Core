<?php

namespace LaraGram\Foundation;

use Composer\Autoload\ClassLoader;
use LaraGram\Config\Repository;
use LaraGram\Console\Kernel;
use LaraGram\Container\Container;
use LaraGram\Contracts\Application as ApplicationContract;
use Illuminate\Database\Capsule\Manager as Capsule;
use LaraGram\Support\Facades\Config;
use LaraGram\Support\Facades\Console;
use Swoole\Http\Server;
use Swoole\Http\Request;
use Swoole\Http\Response;
use LaraGram\Support\Facades\Facade;

class Application extends Container implements ApplicationContract
{
    protected string|null $basePath;
    protected array $serviceProviders = [];
    protected array $loadedProviders = [];
    protected array $deferredServices = [];
    protected array $bootedCallbacks = [];
    protected array $bootingCallbacks = [];
    protected array $registeredCallbacks = [];
    protected array $terminatingCallbacks = [];
    protected bool $booted = false;
    private string $appPath = '';
    private string $storagePath = '';
    private string $configPath = '';
    private string $databasePath = '';
    private string $assetsPath = '';

    public function __construct(string $basePath = null)
    {
        static::setInstance($this);
        Facade::setFacadeApplication($this);

        $this->setBasePath($basePath)
            ->registerConfig()
            ->registerBaseBindings()
            ->registerBaseServiceProviders()
            ->registerCoreContainerAliases();

        Config::set('app.APP_BASE_PATH', $this->basePath);

        if (Config::get('DB_POWER') == 'on') {
            $this->registerEloquent();
        }

        return $this;
    }

    public function setBasePath($basePath): static
    {
        $basePath = match (true) {
            $basePath !== null => $basePath,
            isset($_ENV['APP_BASE_PATH']) => $_ENV['APP_BASE_PATH'],
            default => dirname(array_keys(ClassLoader::getRegisteredLoaders())[0]),
        };

        $this->basePath = rtrim($basePath, '\/');

        $_ENV['APP_BASE_PATH'] = $this->basePath;

        $this->bindPathsInContainer();

        return $this;
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

    public function basePath($path = ''): string
    {
        return $this->joinPaths($this->basePath, $path);
    }

    public function appPath($path = ''): string
    {
        return $this->joinPaths($this->appPath ?: $this->basePath('app'), $path);
    }

    public function storagePath($path = ''): string
    {
        return $this->joinPaths($this->storagePath ?: $this->basePath('storage'), $path);
    }

    public function configPath($path = ''): string
    {
        return $this->joinPaths($this->configPath ?: $this->basePath('config'), $path);
    }

    public function databasePath($path = ''): string
    {
        return $this->joinPaths($this->databasePath ?: $this->basePath('database'), $path);
    }

    public function assetsPath($path = ''): string
    {
        return $this->joinPaths($this->assetsPath ?: $this->basePath('assets'), $path);
    }

    protected function bindPathsInContainer(): void
    {
        $this->instance('path.laragram', dirname(__DIR__));
        $this->instance('path.base', $this->basePath());
        $this->instance('path.app', $this->appPath());
        $this->instance('path.storage', $this->storagePath());
        $this->instance('path.config', $this->configPath());
        $this->instance('path.database', $this->databasePath());
        $this->instance('path.asset', $this->assetsPath());
    }

    public function registerKernel(): static
    {
        $this->singleton(CoreCommand::class);
        $this->alias(CoreCommand::class, 'kernel.core_command');
        $this->singleton(Kernel::class);
        $this->alias(Kernel::class, 'kernel');

        return $this;
    }

    public function commands(): array
    {
        /**
         * @var CoreCommand $core_command
         */
        $core_command = app('kernel.core_command');

        return array_merge($core_command->getCoreCommands(), Config::get('app.COMMANDS'));
    }

    public function registerCommands(): static
    {
        $commands = $this->commands();
        foreach ($commands as $command) {
            $this['kernel']->addCommand(new $command);
        }

        $this['kernel']->run();

        return $this;
    }

    protected function registerBaseBindings(): static
    {
        $this->instance('app', $this);
        $this->instance(Container::class, $this);

        return $this;
    }

    protected function baseServiceProviders(): array
    {
        $providers = [
            \LaraGram\Listener\ListenerServiceProvider::class,
            \LaraGram\Request\RequestServiceProvider::class,
            \LaraGram\Database\DatabaseServiceProvider::class,
            \LaraGram\Keyboard\KeyboardServiceProvider::class,
            \LaraGram\Auth\AuthServiceProvider::class,
            \LaraGram\Console\ConsoleServiceProvider::class,
        ];

        return array_merge($providers, Config::get('app.SERVICE_PROVIDERS'));
    }

    protected function registerBaseServiceProviders(): static
    {
        foreach ($this->baseServiceProviders() as $provider) {
            $this->register(new $provider($this));
        }

        return $this;
    }

    protected function coreContainerAliases(): array
    {
        return [
            'app' => [self::class, Container::class],
            'listener' => [\LaraGram\Listener\Listener::class, \LaraGram\Listener\Group::class],
            'request' => [\LaraGram\Request\Request::class],
            'db.schema' => [\LaraGram\Database\Migrations\Schema::class],
            'auth' => [\LaraGram\Auth\Auth::class],
            'auth.level' => [\LaraGram\Auth\Level::class],
            'auth.role' => [\LaraGram\Auth\Role::class],
            'console.output' => [\LaraGram\Console\Output::class],
        ];
    }

    protected function registerCoreContainerAliases(): static
    {
        foreach ($this->coreContainerAliases() as $key => $aliases) {
            foreach ($aliases as $alias) {
                $this->alias($key, $alias);
            }
        }

        return $this;
    }

    protected function registerEloquent(): static
    {
        $capsule = new Capsule();
        $capsule->addConnection([
            'driver' => Config::get('database.DB_DRIVER'),
            'host' => Config::get('database.DB_HOST'),
            'port' => Config::get('database.DB_PORT'),
            'database' => Config::get('database.DB_DATABASE'),
            'username' => Config::get('database.DB_USERNAME'),
            'password' => Config::get('database.DB_PASSWORD'),
            'charset' => Config::get('database.DB_CHARSET'),
            'collation' => Config::get('database.DB_COLLATION'),
            'prefix' => Config::get('database.DB_PREFIX'),
        ]);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        return $this;
    }

    public function registered($callback): void
    {
        $this->registeredCallbacks[] = $callback;
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
        return array_values($this->getProviders($provider))[0] ?? null;
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
        $this->serviceProviders[] = $provider;

        $this->loadedProviders[get_class($provider)] = true;
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

        if (!$this->isBooted()) {
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

        if (method_exists($provider, 'boot')) {
            $provider->boot();
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

    public function addDeferredServices(array $services): void
    {
        $this->deferredServices = array_merge($this->deferredServices, $services);
    }

    public function isDeferredService($service): bool
    {
        return isset($this->deferredServices[$service]);
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
    }

    public function handleRequests()
    {
        $update_type = Config::get('bot.UPDATE_TYPE');
        if ($update_type == 'openswoole') {
            if (!extension_loaded('openswoole') && !extension_loaded('swoole')) {
                Console::output()->failed('Extension Openswoole/Swoole not loaded!');
            }

            $ip = Config::get('server.OPENSWOOLE_IP');
            $port = Config::get('server.OPENSWOOLE_PORT');
            $server = new Server($ip, $port);
            $server->on("start", function () use ($ip, $port) {
                Console::output()->success("Server Started! [ {$ip}:{$port} ]");
            });

            $server->on("request", function (Request $swooleRequest, Response $swooleResponse) {
                $swooleResponse->end();
                global $swoole;
                $swoole = json_decode($swooleRequest->getContent());

                app()->loadResources(false);
            });

            $server->start();
        } else {
            $this->loadResources();
        }
    }

    private function registerConfig(): static
    {
        $this->singleton('config', function (){
            $configurations = [];
            foreach (glob(app('path.config') . '/*.php') as $file) {
                $key = basename($file, '.php');
                $configurations[$key] = require $file;
            }

            return new Repository($configurations);
        });

        return $this;
    }
}