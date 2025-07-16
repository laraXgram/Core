<?php

namespace LaraGram\Foundation;

use Closure;
use Composer\Autoload\ClassLoader;
use LaraGram\Console\Input\InputInterface;
use LaraGram\Console\Output\ConsoleOutput;
use LaraGram\Container\Container;
use LaraGram\Contracts\Foundation\Application as ApplicationContract;
use LaraGram\Contracts\Console\Kernel as ConsoleKernelContract;
use LaraGram\Contracts\Bot\Kernel as BotKernelContract;
use LaraGram\Foundation\Bootstrap\LoadEnvironmentVariables;
use LaraGram\Request\Request;
use LaraGram\Contracts\Foundation\CachesConfiguration;
use LaraGram\Events\EventServiceProvider;
use LaraGram\Filesystem\Filesystem;
use LaraGram\Foundation\Events\LocaleUpdated;
use LaraGram\Log\Context\ContextServiceProvider;
use LaraGram\Log\LogServiceProvider;
use LaraGram\Listening\ListeningServiceProvider;
use LaraGram\Support\Arr;
use LaraGram\Support\Collection;
use LaraGram\Support\Env;
use LaraGram\Support\Str;
use LaraGram\Support\Traits\Macroable;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;
use Swoole\Http\Server as SwooleServer;

use function LaraGram\Filesystem\join_paths;

class Application extends Container implements ApplicationContract, CachesConfiguration
{
    use Macroable;

    /**
     * The LaraGram framework version.
     *
     * @var string
     */
    const VERSION = "3.0.0";

    /**
     * The base path for the LaraGram installation.
     *
     * @var string
     */
    protected $basePath;

    /**
     * The array of registered callbacks.
     *
     * @var callable[]
     */
    protected $registeredCallbacks = [];

    /**
     * Indicates if the application has been bootstrapped before.
     *
     * @var bool
     */
    protected $hasBeenBootstrapped = false;

    /**
     * Indicates if the application has "booted".
     *
     * @var bool
     */
    protected $booted = false;

    /**
     * The array of booting callbacks.
     *
     * @var callable[]
     */
    protected $bootingCallbacks = [];

    /**
     * The array of booted callbacks.
     *
     * @var callable[]
     */
    protected $bootedCallbacks = [];

    /**
     * The array of terminating callbacks.
     *
     * @var callable[]
     */
    protected $terminatingCallbacks = [];

    /**
     * All of the registered service providers.
     *
     * @var array<string, \LaraGram\Support\ServiceProvider>
     */
    protected $serviceProviders = [];

    /**
     * The names of the loaded service providers.
     *
     * @var array
     */
    protected $loadedProviders = [];

    /**
     * The deferred services and their providers.
     *
     * @var array
     */
    protected $deferredServices = [];

    /**
     * The custom bootstrap path defined by the developer.
     *
     * @var string
     */
    protected $bootstrapPath;

    /**
     * The custom application path defined by the developer.
     *
     * @var string
     */
    protected $appPath;

    /**
     * The custom configuration path defined by the developer.
     *
     * @var string
     */
    protected $configPath;

    /**
     * The custom database path defined by the developer.
     *
     * @var string
     */
    protected $databasePath;

    /**
     * The custom language file path defined by the developer.
     *
     * @var string
     */
    protected $langPath;

    /**
     * The custom public / web path defined by the developer.
     *
     * @var string
     */
    protected $publicPath;

    /**
     * The custom storage path defined by the developer.
     *
     * @var string
     */
    protected $storagePath;

    /**
     * The custom environment path defined by the developer.
     *
     * @var string
     */
    protected $environmentPath;

    /**
     * The environment file to load during bootstrapping.
     *
     * @var string
     */
    protected $environmentFile = '.env';

    /**
     * Indicates if the application is running in the console.
     *
     * @var bool|null
     */
    protected $isRunningInConsole;

    /**
     * The application namespace.
     *
     * @var string
     */
    protected $namespace;

    /**
     * Indicates if the framework's base configuration should be merged.
     *
     * @var bool
     */
    protected $mergeFrameworkConfiguration = true;

    /**
     * The prefixes of absolute cache paths for use during normalization.
     *
     * @var string[]
     */
    protected $absoluteCachePathPrefixes = ['/', '\\'];

    /**
     * Create a new LaraGram application instance.
     *
     * @param  string|null  $basePath
     * @return void
     */
    public function __construct(string $basePath = null)
    {
        if ($basePath) {
            $this->setBasePath($basePath);
        }

        $this->registerBaseBindings();
        $this->registerBaseServiceProviders();
        $this->registerCoreContainerAliases();
    }

    /**
     * Begin configuring a new LaraGram application instance.
     *
     * @param  string|null  $basePath
     * @return \LaraGram\Foundation\Configuration\ApplicationBuilder
     */
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

    /**
     * Infer the application's base directory from the environment.
     *
     * @return string
     */
    public static function inferBasePath()
    {
        return match (true) {
            isset($_ENV['APP_BASE_PATH']) => $_ENV['APP_BASE_PATH'],
            default => dirname(array_values(array_filter(
                array_keys(ClassLoader::getRegisteredLoaders()),
                fn ($path) => ! str_starts_with($path, 'phar://'),
            ))[0]),
        };
    }

    /**
     * Get the version number of the application.
     *
     * @return string
     */
    public function version()
    {
        return static::VERSION;
    }

    /**
     * Register the basic bindings into the container.
     *
     * @return void
     */
    protected function registerBaseBindings()
    {
        static::setInstance($this);

        $this->instance('app', $this);

        $this->instance(Container::class, $this);

        $this->singleton(PackageManifest::class, fn() => new PackageManifest(
            new Filesystem, $this->basePath(), $this->getCachedPackagesPath()
        ));
    }

    /**
     * Register all of the base service providers.
     *
     * @return void
     */
    protected function registerBaseServiceProviders()
    {
        $this->register(new EventServiceProvider($this));
        $this->register(new LogServiceProvider($this));
        $this->register(new ContextServiceProvider($this));
        $this->register(new ListeningServiceProvider($this));
    }

    /**
     * Run the given array of bootstrap classes.
     *
     * @param  string[]  $bootstrappers
     * @return void
     */
    public function bootstrapWith(array $bootstrappers)
    {
        $this->hasBeenBootstrapped = true;

        foreach ($bootstrappers as $bootstrapper) {
            $this['events']->dispatch('bootstrapping: ' . $bootstrapper, [$this]);

            $this->make($bootstrapper)->bootstrap($this);

            $this['events']->dispatch('bootstrapped: ' . $bootstrapper, [$this]);
        }
    }

    /**
     * Register a callback to run after loading the environment.
     *
     * @param  \Closure  $callback
     * @return void
     */
    public function afterLoadingEnvironment(Closure $callback)
    {
        $this->afterBootstrapping(
            LoadEnvironmentVariables::class, $callback
        );
    }

    /**
     * Register a callback to run before a bootstrapper.
     *
     * @param  string  $bootstrapper
     * @param  \Closure  $callback
     * @return void
     */
    public function beforeBootstrapping($bootstrapper, Closure $callback)
    {
        $this['events']->listen('bootstrapping: ' . $bootstrapper, $callback);
    }

    /**
     * Register a callback to run after a bootstrapper.
     *
     * @param  string  $bootstrapper
     * @param  \Closure  $callback
     * @return void
     */
    public function afterBootstrapping($bootstrapper, Closure $callback)
    {
        $this['events']->listen('bootstrapped: ' . $bootstrapper, $callback);
    }

    /**
     * Determine if the application has been bootstrapped before.
     *
     * @return bool
     */
    public function hasBeenBootstrapped(): bool
    {
        return $this->hasBeenBootstrapped;
    }

    /**
     * Set the base path for the application.
     *
     * @param  string  $basePath
     * @return $this
     */
    public function setBasePath($basePath): static
    {
        $this->basePath = rtrim($basePath, '\/');

        $this->bindPathsInContainer();

        return $this;
    }

    /**
     * Bind all of the application paths in the container.
     *
     * @return void
     */
    protected function bindPathsInContainer(): void
    {
        $this->instance('path', $this->path());
        $this->instance('path.base', $this->basePath());
        $this->instance('path.config', $this->configPath());
        $this->instance('path.database', $this->databasePath());
        $this->instance('path.public', $this->publicPath());
        $this->instance('path.storage', $this->storagePath());
        $this->instance('path.laragram', dirname(__DIR__));

        $this->useBootstrapPath(value(function () {
            return is_dir($directory = $this->basePath('.laragram'))
                ? $directory
                : $this->basePath('bootstrap');
        }));

        $this->useLangPath(value(function () {
            return is_dir($directory = $this->publicPath('lang'))
                ? $directory
                : $this->basePath('lang');
        }));
    }

    /**
     * Get the path to the application "app" directory.
     *
     * @param  string  $path
     * @return string
     */
    public function path($path = ''): string
    {
        return $this->joinPaths($this->appPath ?: $this->basePath('app'), $path);
    }

    /**
     * Set the application directory.
     *
     * @param  string  $path
     * @return $this
     */
    public function useAppPath($path)
    {
        $this->appPath = $path;

        $this->instance('path', $path);

        return $this;
    }

    /**
     * Get the base path of the LaraGram installation.
     *
     * @param  string  $path
     * @return string
     */
    public function basePath($path = ''): string
    {
        return $this->joinPaths($this->basePath, $path);
    }

    /**
     * Get the path to the bootstrap directory.
     *
     * @param  string  $path
     * @return string
     */
    public function bootstrapPath($path = '')
    {
        return $this->joinPaths($this->bootstrapPath, $path);
    }

    /**
     * Get the path to the service provider list in the bootstrap directory.
     *
     * @return string
     */
    public function getBootstrapProvidersPath()
    {
        return $this->bootstrapPath('providers.php');
    }

    /**
     * Set the bootstrap file directory.
     *
     * @param  string  $path
     * @return $this
     */
    public function useBootstrapPath($path)
    {
        $this->bootstrapPath = $path;

        $this->instance('path.bootstrap', $path);

        return $this;
    }

    /**
     * Get the path to the application configuration files.
     *
     * @param  string  $path
     * @return string
     */
    public function configPath($path = '')
    {
        return $this->joinPaths($this->configPath ?: $this->basePath('config'), $path);
    }

    /**
     * Set the configuration directory.
     *
     * @param  string  $path
     * @return $this
     */
    public function useConfigPath($path)
    {
        $this->configPath = $path;

        $this->instance('path.config', $path);

        return $this;
    }

    /**
     * Get the path to the database directory.
     *
     * @param  string  $path
     * @return string
     */
    public function databasePath($path = '')
    {
        return $this->joinPaths($this->databasePath ?: $this->basePath('database'), $path);
    }

    /**
     * Set the database directory.
     *
     * @param  string  $path
     * @return $this
     */
    public function useDatabasePath($path)
    {
        $this->databasePath = $path;

        $this->instance('path.database', $path);

        return $this;
    }

    /**
     * Get the path to the language files.
     *
     * @param  string  $path
     * @return string
     */
    public function langPath($path = '')
    {
        return $this->joinPaths($this->langPath, $path);
    }

    /**
     * Set the language file directory.
     *
     * @param  string  $path
     * @return $this
     */
    public function useLangPath($path)
    {
        $this->langPath = $path;

        $this->instance('path.lang', $path);

        return $this;
    }

    /**
     * Get the path to the public directory.
     *
     * @param  string  $path
     * @return string
     */
    public function publicPath($path = '')
    {
        return $this->joinPaths($this->publicPath ?: $this->basePath('public'), $path);
    }

    /**
     * Set the public directory.
     *
     * @param  string  $path
     * @return $this
     */
    public function usePublicPath($path)
    {
        $this->publicPath = $path;

        $this->instance('path.public', $path);

        return $this;
    }

    /**
     * Get the path to the storage directory.
     *
     * @param  string  $path
     * @return string
     */
    public function storagePath($path = ''): string
    {
        if (isset($_ENV['LARAGRAM_STORAGE_PATH'])) {
            return $this->joinPaths($this->storagePath ?: $_ENV['LARAGRAM_STORAGE_PATH'], $path);
        }

        return $this->joinPaths($this->storagePath ?: $this->basePath('storage'), $path);
    }

    /**
     * Set the storage directory.
     *
     * @param  string  $path
     * @return $this
     */
    public function useStoragePath($path)
    {
        $this->storagePath = $path;

        $this->instance('path.storage', $path);

        return $this;
    }

    /**
     * Get the path to the templates directory.
     *
     * This method returns the first configured path in the array of template paths.
     *
     * @param  string  $path
     * @return string
     */
    public function templatePath($path = '')
    {
        $templatePath = rtrim($this['config']->get('template.paths')[0], DIRECTORY_SEPARATOR);

        return $this->joinPaths($templatePath, $path);
    }

    /**
     * Join the given paths together.
     *
     * @param  string  $basePath
     * @param  string  $path
     * @return string
     */
    public function joinPaths($basePath, $path = '')
    {
        return join_paths($basePath, $path);
    }

    /**
     * Get the path to the environment file directory.
     *
     * @return string
     */
    public function environmentPath()
    {
        return $this->environmentPath ?: $this->basePath;
    }

    /**
     * Set the directory for the environment file.
     *
     * @param  string  $path
     * @return $this
     */
    public function useEnvironmentPath($path)
    {
        $this->environmentPath = $path;

        return $this;
    }

    /**
     * Set the environment file to be loaded during bootstrapping.
     *
     * @param  string  $file
     * @return $this
     */
    public function loadEnvironmentFrom($file)
    {
        $this->environmentFile = $file;

        return $this;
    }

    /**
     * Get the environment file the application is using.
     *
     * @return string
     */
    public function environmentFile()
    {
        return $this->environmentFile ?: '.env';
    }

    /**
     * Get the fully qualified path to the environment file.
     *
     * @return string
     */
    public function environmentFilePath()
    {
        return $this->environmentPath().DIRECTORY_SEPARATOR.$this->environmentFile();
    }

    /**
     * Get or check the current application environment.
     *
     * @param  string|array  ...$environments
     * @return string|bool
     */
    public function environment(...$environments)
    {
        if (count($environments) > 0) {
            $patterns = is_array($environments[0]) ? $environments[0] : $environments;

            return Str::is($patterns, $this['env']);
        }

        return $this['env'];
    }

    /**
     * Determine if the application is in the local environment.
     *
     * @return bool
     */
    public function isLocal()
    {
        return $this['env'] === 'local';
    }

    /**
     * Determine if the application is in the production environment.
     *
     * @return bool
     */
    public function isProduction()
    {
        return $this['env'] === 'production';
    }

    /**
     * Detect the application's current environment.
     *
     * @param  \Closure  $callback
     * @return string
     */
    public function detectEnvironment(Closure $callback)
    {
        $args = $this->runningInConsole() && isset($_SERVER['argv'])
            ? $_SERVER['argv']
            : null;

        return $this['env'] = (new EnvironmentDetector)->detect($callback, $args);
    }

    /**
     * Determine if the application is running in the console.
     *
     * @return bool
     */
    public function runningInConsole()
    {
        if ($this->isRunningInConsole === null) {
            $this->isRunningInConsole = Env::get('APP_RUNNING_IN_CONSOLE') ?? (\PHP_SAPI === 'cli' || \PHP_SAPI === 'phpdbg');
        }

        return $this->isRunningInConsole;
    }

    /**
     * Determine if the application is running any of the given console commands.
     *
     * @param  string|array  ...$commands
     * @return bool
     */
    public function runningConsoleCommand(...$commands)
    {
        if (! $this->runningInConsole()) {
            return false;
        }

        return in_array(
            $_SERVER['argv'][1] ?? null,
            is_array($commands[0]) ? $commands[0] : $commands
        );
    }

    /**
     * Determine if the application is running with debug mode enabled.
     *
     * @return bool
     */
    public function hasDebugModeEnabled()
    {
        return (bool) $this['config']->get('app.debug');
    }

    /**
     * Register a new registered listener.
     *
     * @param  callable  $callback
     * @return void
     */
    public function registered($callback): void
    {
        $this->registeredCallbacks[] = $callback;
    }

    /**
     * Register all of the configured providers.
     *
     * @return void
     */
    public function registerConfiguredProviders()
    {
        $providers = (new Collection($this->make('config')->get('app.providers')))
            ->partition(fn ($provider) => str_starts_with($provider, 'LaraGram\\'));

        $providers->splice(1, 0, [$this->make(PackageManifest::class)->providers()]);

        (new ProviderRepository($this, new Filesystem, $this->getCachedServicesPath()))
            ->load($providers->collapse()->toArray());

        $this->fireAppCallbacks($this->registeredCallbacks);
    }

    /**
     * Register a service provider with the application.
     *
     * @param  \LaraGram\Support\ServiceProvider|string  $provider
     * @param  bool  $force
     * @return \LaraGram\Support\ServiceProvider
     */
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

    /**
     * Get the registered service provider instance if it exists.
     *
     * @param  \LaraGram\Support\ServiceProvider|string  $provider
     * @return \LaraGram\Support\ServiceProvider|null
     */
    public function getProvider($provider)
    {
        $name = is_string($provider) ? $provider : get_class($provider);

        return $this->serviceProviders[$name] ?? null;
    }

    /**
     * Get the registered service provider instances if any exist.
     *
     * @param  \LaraGram\Support\ServiceProvider|string  $provider
     * @return array
     */
    public function getProviders($provider)
    {
        $name = is_string($provider) ? $provider : get_class($provider);

        return Arr::where($this->serviceProviders, fn ($value) => $value instanceof $name);
    }

    /**
     * Resolve a service provider instance from the class name.
     *
     * @param  string  $provider
     * @return \LaraGram\Support\ServiceProvider
     */
    public function resolveProvider($provider)
    {
        return new $provider($this);
    }

    /**
     * Mark the given provider as registered.
     *
     * @param  \LaraGram\Support\ServiceProvider  $provider
     * @return void
     */
    protected function markAsRegistered($provider): void
    {
        $class = get_class($provider);

        $this->serviceProviders[$class] = $provider;

        $this->loadedProviders[$class] = true;
    }

    /**
     * Load and boot all of the remaining deferred providers.
     *
     * @return void
     */
    public function loadDeferredProviders(): void
    {
        foreach ($this->deferredServices as $service => $provider) {
            $this->loadDeferredProvider($service);
        }

        $this->deferredServices = [];
    }

    /**
     * Load the provider for a deferred service.
     *
     * @param  string  $service
     * @return void
     */
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

    /**
     * Register a deferred provider and service.
     *
     * @param  string  $provider
     * @param  string|null  $service
     * @return void
     */
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

    /**
     * Resolve the given type from the container.
     *
     * @template TClass of object
     *
     * @param  string|class-string<TClass>  $abstract
     * @param  array  $parameters
     * @return ($abstract is class-string<TClass> ? TClass : mixed)
     *
     * @throws \LaraGram\Contracts\Container\BindingResolutionException
     */
    public function make($abstract, array $parameters = [])
    {
        $this->loadDeferredProviderIfNeeded($abstract = $this->getAlias($abstract));

        return parent::make($abstract, $parameters);
    }

    /**
     * Resolve the given type from the container.
     *
     * @template TClass of object
     *
     * @param  string|class-string<TClass>|callable  $abstract
     * @param  array  $parameters
     * @param  bool  $raiseEvents
     * @return ($abstract is class-string<TClass> ? TClass : mixed)
     *
     * @throws \LaraGram\Contracts\Container\BindingResolutionException
     * @throws \LaraGram\Contracts\Container\CircularDependencyException
     */
    protected function resolve($abstract, $parameters = [], $raiseEvents = true)
    {
        $this->loadDeferredProviderIfNeeded($abstract = $this->getAlias($abstract));

        return parent::resolve($abstract, $parameters, $raiseEvents);
    }

    /**
     * Load the deferred provider if the given type is a deferred service and the instance has not been loaded.
     *
     * @param  string  $abstract
     * @return void
     */
    protected function loadDeferredProviderIfNeeded($abstract)
    {
        if ($this->isDeferredService($abstract) && !isset($this->instances[$abstract])) {
            $this->loadDeferredProvider($abstract);
        }
    }

    /**
     * Determine if the given abstract type has been bound.
     *
     * @param  string  $abstract
     * @return bool
     */
    public function bound($abstract): bool
    {
        return $this->isDeferredService($abstract) || parent::bound($abstract);
    }

    /**
     * Determine if the application has booted.
     *
     * @return bool
     */
    public function isBooted(): bool
    {
        return $this->booted;
    }

    /**
     * Boot the application's service providers.
     *
     * @return void
     */
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

    /**
     * Boot the given service provider.
     *
     * @param  \LaraGram\Support\ServiceProvider  $provider
     * @return void
     */
    protected function bootProvider($provider): void
    {
        $provider->callBootingCallbacks();

        if (method_exists($provider, 'boot')) {
            $this->call([$provider, 'boot']);
        }

        $provider->callBootedCallbacks();
    }

    /**
     * Register a new boot listener.
     *
     * @param  callable  $callback
     * @return void
     */
    public function booting($callback): void
    {
        $this->bootingCallbacks[] = $callback;
    }

    /**
     * Register a new "booted" listener.
     *
     * @param  callable  $callback
     * @return void
     */
    public function booted($callback): void
    {
        $this->bootedCallbacks[] = $callback;

        if ($this->isBooted()) {
            $callback($this);
        }
    }

    /**
     * Call the booting callbacks for the application.
     *
     * @param  callable[]  $callbacks
     * @return void
     */
    protected function fireAppCallbacks(array &$callbacks): void
    {
        $index = 0;

        while ($index < count($callbacks)) {
            $callbacks[$index]($this);

            $index++;
        }
    }

    /**
     * Handle the incoming Bot request and send the response to the client.
     *
     * @param  \LaraGram\Request\Request  $request
     * @return void
     */
    public function handleRequest(Request $request)
    {
        $kernel = $this->make(BotKernelContract::class);

        $response = $kernel->handle($request)->send();

        $kernel->terminate($request, $response);
    }

    /**
     * Handle the incoming Commander command.
     *
     * @param  \LaraGram\Console\Input\InputInterface  $input
     * @return int
     */
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

    /**
     * Determine if the framework's base configuration should be merged.
     *
     * @return bool
     */
    public function shouldMergeFrameworkConfiguration()
    {
        return $this->mergeFrameworkConfiguration;
    }

    /**
     * Indicate that the framework's base configuration should not be merged.
     *
     * @return $this
     */
    public function dontMergeFrameworkConfiguration()
    {
        $this->mergeFrameworkConfiguration = false;

        return $this;
    }

    /**
     * Determine if middleware has been disabled for the application.
     *
     * @return bool
     */
    public function shouldSkipMiddleware()
    {
        return $this->bound('middleware.disable') &&
            $this->make('middleware.disable') === true;
    }

    /**
     * Get the path to the cached services.php file.
     *
     * @return string
     */
    public function getCachedServicesPath(): string
    {
        return $this->normalizeCachePath('APP_SERVICES_CACHE', 'cache/services.php');
    }

    /**
     * Get the path to the cached packages.php file.
     *
     * @return string
     */
    public function getCachedPackagesPath(): string
    {
        return $this->normalizeCachePath('APP_PACKAGES_CACHE', 'cache/packages.php');
    }

    /**
     * Determine if the application configuration is cached.
     *
     * @return bool
     */
    public function configurationIsCached(): bool
    {
        return is_file($this->getCachedConfigPath());
    }

    /**
     * Get the path to the configuration cache file.
     *
     * @return string
     */
    public function getCachedConfigPath(): string
    {
        return $this->normalizeCachePath('APP_CONFIG_CACHE', 'cache/config.php');
    }

    /**
     * Determine if the application listens are cached.
     *
     * @return bool
     */
    public function listensAreCached()
    {
        return $this['files']->exists($this->getCachedListensPath());
    }

    /**
     * Get the path to the listens cache file.
     *
     * @return string
     */
    public function getCachedListensPath()
    {
        return $this->normalizeCachePath('APP_LISTENS_CACHE', 'cache/listens.php');
    }

    /**
     * Determine if the application events are cached.
     *
     * @return bool
     */
    public function eventsAreCached()
    {
        return $this['files']->exists($this->getCachedEventsPath());
    }

    /**
     * Get the path to the events cache file.
     *
     * @return string
     */
    public function getCachedEventsPath()
    {
        return $this->normalizeCachePath('APP_EVENTS_CACHE', 'cache/events.php');
    }

    /**
     * Normalize a relative or absolute path to a cache file.
     *
     * @param  string  $key
     * @param  string  $default
     * @return string
     */
    protected function normalizeCachePath($key, $default)
    {
        if (is_null($env = Env::get($key))) {
            return $this->bootstrapPath($default);
        }

        return Str::startsWith($env, $this->absoluteCachePathPrefixes)
            ? $env
            : $this->basePath($env);
    }

    /**
     * Add new prefix to list of absolute path prefixes.
     *
     * @param  string  $prefix
     * @return $this
     */
    public function addAbsoluteCachePathPrefix($prefix)
    {
        $this->absoluteCachePathPrefixes[] = $prefix;

        return $this;
    }

    /**
     * Register a terminating callback with the application.
     *
     * @param  callable|string  $callback
     * @return $this
     */
    public function terminating($callback)
    {
        $this->terminatingCallbacks[] = $callback;

        return $this;
    }

    /**
     * Terminate the application.
     *
     * @return void
     */
    public function terminate()
    {
        $index = 0;

        while ($index < count($this->terminatingCallbacks)) {
            $this->call($this->terminatingCallbacks[$index]);

            $index++;
        }
    }

    /**
     * Get the service providers that have been loaded.
     *
     * @return array<string, bool>
     */
    public function getLoadedProviders(): array
    {
        return $this->loadedProviders;
    }

    /**
     * Determine if the given service provider is loaded.
     *
     * @param  string  $provider
     * @return bool
     */
    public function providerIsLoaded(string $provider): bool
    {
        return isset($this->loadedProviders[$provider]);
    }

    /**
     * Get the application's deferred services.
     *
     * @return array
     */
    public function getDeferredServices(): array
    {
        return $this->deferredServices;
    }

    /**
     * Set the application's deferred services.
     *
     * @param  array  $services
     * @return void
     */
    public function setDeferredServices(array $services): void
    {
        $this->deferredServices = $services;
    }

    /**
     * Determine if the given service is a deferred service.
     *
     * @param  string  $service
     * @return bool
     */
    public function isDeferredService($service): bool
    {
        return isset($this->deferredServices[$service]);
    }

    /**
     * Add an array of services to the application's deferred services.
     *
     * @param  array  $services
     * @return void
     */
    public function addDeferredServices(array $services): void
    {
        $this->deferredServices = array_merge($this->deferredServices, $services);
    }

    /**
     * Remove an array of services from the application's deferred services.
     *
     * @param  array  $services
     * @return void
     */
    public function removeDeferredServices(array $services)
    {
        foreach ($services as $service) {
            unset($this->deferredServices[$service]);
        }
    }

    /**
     * Configure the real-time facade namespace.
     *
     * @param  string  $namespace
     * @return void
     */
    public function provideFacades($namespace)
    {
        AliasLoader::setFacadeNamespace($namespace);
    }

    /**
     * Get the current application locale.
     *
     * @return string
     */
    public function getLocale()
    {
        return $this['config']->get('app.locale');
    }

    /**
     * Get the current application locale.
     *
     * @return string
     */
    public function currentLocale()
    {
        return $this->getLocale();
    }

    /**
     * Get the current application fallback locale.
     *
     * @return string
     */
    public function getFallbackLocale()
    {
        return $this['config']->get('app.fallback_locale');
    }

    /**
     * Set the current application locale.
     *
     * @param  string  $locale
     * @return void
     */
    public function setLocale($locale)
    {
        $this['config']->set('app.locale', $locale);

        $this['translator']->setLocale($locale);

        $this['events']->dispatch(new LocaleUpdated($locale));
    }

    /**
     * Set the current application fallback locale.
     *
     * @param  string  $fallbackLocale
     * @return void
     */
    public function setFallbackLocale($fallbackLocale)
    {
        $this['config']->set('app.fallback_locale', $fallbackLocale);

        $this['translator']->setFallback($fallbackLocale);
    }

    /**
     * Determine if the application locale is the given locale.
     *
     * @param  string  $locale
     * @return bool
     */
    public function isLocale($locale)
    {
        return $this->getLocale() == $locale;
    }

    /**
     * Register the core class aliases in the container.
     *
     * @return void
     */
    protected function registerCoreContainerAliases(): static
    {
        foreach ([
                     'app' => [self::class, \LaraGram\Contracts\Container\Container::class, \LaraGram\Contracts\Foundation\Application::class],
                     'auth' => [\LaraGram\Auth\AuthManager::class],
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
                     'log' => [\LaraGram\Log\LogManager::class, \LaraGram\Log\LoggerInterface::class],
                     'queue' => [\LaraGram\Queue\QueueManager::class, \LaraGram\Contracts\Queue\Factory::class, \LaraGram\Contracts\Queue\Monitor::class],
                     'queue.connection' => [\LaraGram\Contracts\Queue\Queue::class],
                     'queue.failer' => [\LaraGram\Queue\Failed\FailedJobProviderInterface::class],
                     'redirect' => [\LaraGram\Listening\Redirector::class],
                     'redis' => [\LaraGram\Redis\RedisManager::class, \LaraGram\Contracts\Redis\Factory::class],
                     'redis.connection' => [\LaraGram\Redis\Connections\Connection::class, \LaraGram\Contracts\Redis\Connection::class],
                     'request' => [\LaraGram\Request\Request::class],
                     'listener' => [\LaraGram\Listening\Listener::class, \LaraGram\Contracts\Listening\Registrar::class, \LaraGram\Contracts\Listening\BindingRegistrar::class],
                     't8.compiler' => [\LaraGram\Template\Compilers\Temple8Compiler::class],
                     'template' => [\LaraGram\Template\Factory::class, \LaraGram\Contracts\Template\Factory::class],
                     'conversation' => [\LaraGram\Conversation\Conversation::class],
                     'keyboard' => [\LaraGram\Keyboard\Keyboard::class],
                 ] as $key => $aliases) {
            foreach ($aliases as $alias) {
                $this->alias($key, $alias);
            }
        }

        return $this;
    }

    /**
     * Flush the container of all bindings and resolved instances.
     *
     * @return void
     */
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

    /**
     * Get the application namespace.
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    public function getNamespace()
    {
        if (!empty($this->namespace)) {
            return $this->namespace;
        }

        $composer = json_decode(file_get_contents($this->basePath('composer.json')), true);

        foreach ((array)data_get($composer, 'autoload.psr-4') as $namespace => $path) {
            foreach ((array)$path as $pathChoice) {
                if (realpath($this->path()) === realpath($this->basePath($pathChoice))) {
                    return $this->namespace = $namespace;
                }
            }
        }

        throw new \RuntimeException('Unable to detect application namespace.');
    }
}