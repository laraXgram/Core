<?php

namespace LaraGram\Foundation;

use Composer\Autoload\ClassLoader;
use LaraGram\Console\Kernel;
use LaraGram\Console\Output;
use LaraGram\Container\Container;
use LaraGram\Contracts\Application as ApplicationContract;
use Illuminate\Database\Capsule\Manager as Capsule;
use LaraGram\Laraquest\Mode;
use LaraGram\Support\Facades\Console;
use OpenSwoole\Core\Psr7Test\Tests\RequestTest;
use OpenSwoole\Http\Server;
use OpenSwoole\Http\Request;
use OpenSwoole\Http\Response;
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
            ->loadEnv();

        $this->registerBaseBindings()
            ->registerBaseServiceProviders()
            ->registerCoreContainerAliases();

        if (debug_backtrace()[0]['file'] != $this->basePath . DIRECTORY_SEPARATOR . 'laragram') {
            if ($_ENV['DB_POWER'] == 'on') {
                $this->registerEloquent();
            }
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
        $this->singleton(Kernel::class);
        $this->alias(Kernel::class, 'kernel');

        return $this;
    }

    public function commands(): array
    {
        $commands = [
            \LaraGram\Console\GenerateCommand::class,
            \LaraGram\Database\Factories\GenerateFactory::class,
            \LaraGram\Database\Migrations\GenerateMigration::class,
            \LaraGram\Database\Models\GenerateModel::class,
            \LaraGram\Database\Seeders\GenerateSeeder::class,
            \LaraGram\Database\Migrations\Migrator\MigrateCommand::class,
            \LaraGram\Database\Seeders\SeederCommand::class,
            \LaraGram\Foundation\Provider\GenerateProvider::class,
            \LaraGram\Foundation\Resource\GenerateResource::class,
            \LaraGram\Foundation\Webhook\SetWebhookCommand::class,
            \LaraGram\Foundation\Webhook\DeleteWebhookCommand::class,
            \LaraGram\Foundation\Webhook\DropWebhookCommand::class,
            \LaraGram\Foundation\Webhook\WebhookInfoCommand::class,
            \LaraGram\Foundation\Objects\Facade\GenerateFacade::class,
            \LaraGram\Foundation\Objects\Class\GenerateClass::class,
            \LaraGram\Foundation\Objects\Enum\GenerateEnum::class,
            \LaraGram\Foundation\Server\ServeCommand::class,
            \LaraGram\Foundation\Server\APIServeCommand::class,
            \LaraGram\JsonDatabase\Migrations\GenerateMigration::class,
            \LaraGram\JsonDatabase\Models\GenerateModel::class,
            \LaraGram\JsonDatabase\MigrateCommand::class,
            \LaraGram\Foundation\Objects\Controller\GenerateController::class,
        ];

        return array_merge($commands, $_ENV['COMMANDS']);
    }

    public function registerCommands(): static
    {
        foreach ($this->commands() as $command) {
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
            \LaraGram\Keyboard\KeyboardServiceProvider::class,
            \LaraGram\Database\DatabaseServiceProvider::class,
            \LaraGram\Auth\AuthServiceProvider::class,
            \LaraGram\Console\ConsoleServiceProvider::class,
        ];

        return array_merge($providers, $_ENV['SERVICE_PROVIDERS']);
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
            'driver' => $_ENV['DB_DRIVER'],
            'host' => $_ENV['DB_HOST'],
            'port' => $_ENV['DB_PORT'],
            'database' => $_ENV['DB_DATABASE'],
            'username' => $_ENV['DB_USERNAME'],
            'password' => $_ENV['DB_PASSWORD'],
            'charset' => $_ENV['DB_CHARSET'],
            'collation' => $_ENV['DB_COLLATION'],
            'prefix' => $_ENV['DB_PREFIX'],
        ]);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        return $this;
    }

    private function loadEnv(): static
    {
        $configPath = $this->basePath . DIRECTORY_SEPARATOR . 'Config';
        if (!is_dir($configPath)) {
            throw new \InvalidArgumentException("Config path $configPath is not a directory");
        }

        $configFiles = glob($configPath . '/*.php');
        foreach ($configFiles as $file) {
            $config = require $file;
            if (is_array($config)) {
                foreach ($config as $key => $value) {
                    $_ENV[strtoupper($key)] = $value;
                }
            }
        }

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
        $update_type = $_ENV['UPDATE_TYPE'];
        if ($update_type == 'openswoole') {
            if (!extension_loaded('openswoole')){
                Console::output()->failed('Extension Openswoole not loaded!');
            }

            $server = new Server($_ENV['OPENSWOOLE_IP'], $_ENV['OPENSWOOLE_PORT']);
            $server->on("start", function () {
                Console::output()->success("Server Started! [ {$_ENV['OPENSWOOLE_IP']}:{$_ENV['OPENSWOOLE_PORT']} ]");
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
}