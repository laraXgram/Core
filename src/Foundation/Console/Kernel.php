<?php

namespace LaraGram\Foundation\Console;

use Closure;
use LaraGram\Console\Application as Commander;
use LaraGram\Console\Command;
use LaraGram\Contracts\Console\Kernel as KernelContract;
use LaraGram\Contracts\Debug\ExceptionHandler;
use LaraGram\Events\Dispatcher;
use LaraGram\Foundation\Application;
use LaraGram\Foundation\Events\Terminating;
use LaraGram\Support\Arr;
use LaraGram\Support\InteractsWithTime;
use ReflectionClass;
use SplFileInfo;
use Throwable;

class Kernel implements KernelContract
{
    use InteractsWithTime;

    /**
     * The application implementation.
     *
     * @var \LaraGram\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * The event dispatcher implementation.
     *
     * @var \LaraGram\Contracts\Events\Dispatcher
     */
    protected $events;

    /**
     * The Commander application instance.
     *
     * @var \LaraGram\Console\Application|null
     */
    protected $Commander;

    /**
     * The Commander commands provided by the application.
     *
     * @var array
     */
    protected $commands = [];

    /**
     * The paths where Commander commands should be automatically discovered.
     *
     * @var array
     */
    protected $commandPaths = [];

    /**
     * The paths where Commander "routes" should be automatically discovered.
     *
     * @var array
     */
    protected $commandRoutePaths = [];

    /**
     * Indicates if the Closure commands have been loaded.
     *
     * @var bool
     */
    protected $commandsLoaded = false;

    /**
     * The commands paths that have been "loaded".
     *
     * @var array
     */
    protected $loadedPaths = [];

    /**
     * All of the registered command duration handlers.
     *
     * @var array
     */
    protected $commandLifecycleDurationHandlers = [];

    /**
     * When the currently handled command started.
     *
     * @var \DateTimeInterface|null
     */
    protected $commandStartedAt;

    /**
     * The bootstrap classes for the application.
     *
     * @var string[]
     */
    protected $bootstrappers = [
        \LaraGram\Foundation\Bootstrap\LoadConfiguration::class,
        \LaraGram\Foundation\Bootstrap\HandleExceptions::class,
        \LaraGram\Foundation\Bootstrap\RegisterFacades::class,
        \LaraGram\Foundation\Bootstrap\RegisterProviders::class,
        \LaraGram\Foundation\Bootstrap\BootProviders::class,
    ];

    /**
     * Create a new console kernel instance.
     *
     * @param  \LaraGram\Contracts\Foundation\Application  $app
     * @param  \LaraGram\Contracts\Events\Dispatcher  $events
     * @return void
     */
    public function __construct(Application $app, Dispatcher $events)
    {
        $this->app = $app;
        $this->events = $events;
    }

    /**
     * Run the console application.
     *
     * @param  \LaraGram\Console\Input\InputInterface  $input
     * @param  \LaraGram\Console\Output\OutputInterface|null  $output
     * @return int
     */
    public function handle($input, $output = null)
    {
        $this->commandStartedAt = new \DateTime();

        try {
            $this->bootstrap();

            return $this->getCommander()->run($input, $output);
        } catch (Throwable $e) {
            $this->renderException($output, $e);

            return 1;
        }
    }

    /**
     * Terminate the application.
     *
     * @param  \LaraGram\Console\Input\InputInterface  $input
     * @param  int  $status
     * @return void
     */
    public function terminate($input, $status)
    {
        $this->events->dispatch(new Terminating);

        $this->app->terminate();

        if ($this->commandStartedAt === null) {
            return;
        }

        foreach ($this->commandLifecycleDurationHandlers as ['threshold' => $threshold, 'handler' => $handler]) {
            $end ??= new \DateTime();

            if ($this->commandStartedAt->diffInMilliseconds($end) > $threshold) {
                $handler($this->commandStartedAt, $input, $status);
            }
        }

        $this->commandStartedAt = null;
    }

    /**
     * Register a callback to be invoked when the command lifecycle duration exceeds a given amount of time.
     *
     * @param  \DateTimeInterface|float|int  $threshold
     * @param  callable  $handler
     * @return void
     */
    public function whenCommandLifecycleIsLongerThan($threshold, $handler)
    {
        $this->commandLifecycleDurationHandlers[] = [
            'threshold' => $threshold instanceof \DateTimeInterface
                ? $this->secondsUntil($threshold) * 1000
                : ($threshold instanceof \DateInterval
                    ? (($seconds = ($threshold->y * 365 * 24 * 60 * 60) + ($threshold->m * 30 * 24 * 60 * 60) + ($threshold->d * 24 * 60 * 60) + ($threshold->h * 60 * 60) + ($threshold->i * 60) + $threshold->s) * 1000)
                    : $threshold),
            'handler' => $handler,
        ];
    }

    /**
     * When the command being handled started.
     *
     * @return \DateTimeInterface|null
     */
    public function commandStartedAt()
    {
        return $this->commandStartedAt;
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        //
    }

    /**
     * Register a Closure based command with the application.
     *
     * @param  string  $signature
     * @param  \Closure  $callback
     * @return \LaraGram\Foundation\Console\ClosureCommand
     */
    public function command($signature, Closure $callback)
    {
        $command = new ClosureCommand($signature, $callback);

        Commander::starting(function ($Commander) use ($command) {
            $Commander->add($command);
        });

        return $command;
    }

    /**
     * Register all of the commands in the given directory.
     *
     * @param  array|string  $paths
     * @return void
     */
    protected function load($paths)
    {
        $paths = array_unique(Arr::wrap($paths));

        $paths = array_filter($paths, function ($path) {
            return is_dir($path);
        });

        if (empty($paths)) {
            return;
        }

        $this->loadedPaths = array_values(
            array_unique(array_merge($this->loadedPaths, $paths))
        );

        $namespace = $this->app->getNamespace();

        foreach (scandir($paths) as $file) {
            $filePath = $paths . DIRECTORY_SEPARATOR . $file;

            if (is_file($filePath) && pathinfo($filePath, PATHINFO_EXTENSION) === 'php') {
                $command = $this->commandClassFromFile($filePath, $namespace);

                if (is_subclass_of($command, Command::class) &&
                    ! (new ReflectionClass($command))->isAbstract()) {
                    Commander::starting(function ($Commander) use ($command) {
                        $Commander->resolve($command);
                    });
                }
            }
        }
    }

    /**
     * Extract the command class name from the given file path.
     *
     * @param  \SplFileInfo  $file
     * @param  string  $namespace
     * @return string
     */
    protected function commandClassFromFile(SplFileInfo $file, string $namespace): string
    {
        return $namespace.str_replace(
                ['/', '.php'],
                ['\\', ''],
                substr($file->getRealPath(), strlen(realpath(app()->appPath()) . DIRECTORY_SEPARATOR))
            );
    }

    /**
     * Register the given command with the console application.
     *
     * @param  \LaraGram\Console\Command\Command  $command
     * @return void
     */
    public function registerCommand($command)
    {
        $this->getCommander()->add($command);
    }

    /**
     * Run an Commander console command by name.
     *
     * @param  string  $command
     * @param  array  $parameters
     * @param  \LaraGram\Console\Output\OutputInterface|null  $outputBuffer
     * @return int
     *
     * @throws \LaraGram\Console\Exception\CommandNotFoundException
     */
    public function call($command, array $parameters = [], $outputBuffer = null)
    {
        if (in_array($command, ['env:encrypt', 'env:decrypt'], true)) {
            $this->bootstrapWithoutBootingProviders();
        }

        $this->bootstrap();

        return $this->getCommander()->call($command, $parameters, $outputBuffer);
    }

    /**
     * Get all of the commands registered with the console.
     *
     * @return array
     */
    public function all()
    {
        $this->bootstrap();

        return $this->getCommander()->all();
    }

    /**
     * Get the output for the last run command.
     *
     * @return string
     */
    public function output()
    {
        $this->bootstrap();

        return $this->getCommander()->output();
    }

    /**
     * Bootstrap the application for Commander commands.
     *
     * @return void
     */
    public function bootstrap()
    {
        if (! $this->app->hasBeenBootstrapped()) {
            $this->app->bootstrapWith($this->bootstrappers());
        }

        $this->app->loadDeferredProviders();

        if (! $this->commandsLoaded) {
            $this->commands();

            if ($this->shouldDiscoverCommands()) {
                $this->discoverCommands();
            }

            $this->commandsLoaded = true;
        }
    }

    /**
     * Discover the commands that should be automatically loaded.
     *
     * @return void
     */
    protected function discoverCommands()
    {
        foreach ($this->commandPaths as $path) {
            $this->load($path);
        }

        foreach ($this->commandRoutePaths as $path) {
            if (file_exists($path)) {
                require $path;
            }
        }
    }

    /**
     * Bootstrap the application without booting service providers.
     *
     * @return void
     */
    public function bootstrapWithoutBootingProviders()
    {
        $this->app->bootstrapWith(
            array_values(array_filter($this->bootstrappers(), function ($bootstrapper) {
                return $bootstrapper !== \LaraGram\Foundation\Bootstrap\BootProviders::class;
            }))
        );
    }

    /**
     * Determine if the kernel should discover commands.
     *
     * @return bool
     */
    protected function shouldDiscoverCommands()
    {
        return get_class($this) === __CLASS__;
    }

    /**
     * Get the Commander application instance.
     *
     * @return \LaraGram\Console\Application
     */
    protected function getCommander()
    {
        if (is_null($this->Commander)) {
            $this->Commander = (new Commander($this->app, $this->events, $this->app->version()))
                ->resolveCommands($this->commands)
                ->setContainerCommandLoader();
        }

        return $this->Commander;
    }

    /**
     * Set the Commander application instance.
     *
     * @param  \LaraGram\Console\Application|null  $Commander
     * @return void
     */
    public function setCommander($Commander)
    {
        $this->Commander = $Commander;
    }

    /**
     * Set the Commander commands provided by the application.
     *
     * @param  array  $commands
     * @return $this
     */
    public function addCommands(array $commands)
    {
        $this->commands = array_values(array_unique(array_merge($this->commands, $commands)));

        return $this;
    }

    /**
     * Set the paths that should have their Commander commands automatically discovered.
     *
     * @param  array  $paths
     * @return $this
     */
    public function addCommandPaths(array $paths)
    {
        $this->commandPaths = array_values(array_unique(array_merge($this->commandPaths, $paths)));

        return $this;
    }

    /**
     * Set the paths that should have their Commander "routes" automatically discovered.
     *
     * @param  array  $paths
     * @return $this
     */
    public function addCommandRoutePaths(array $paths)
    {
        $this->commandRoutePaths = array_values(array_unique(array_merge($this->commandRoutePaths, $paths)));

        return $this;
    }

    /**
     * Get the bootstrap classes for the application.
     *
     * @return array
     */
    protected function bootstrappers()
    {
        return $this->bootstrappers;
    }

    /**
     * Render the given exception.
     *
     * @param  \LaraGram\Console\Output\OutputInterface  $output
     * @param  \Throwable  $e
     * @return void
     */
    protected function renderException($output, Throwable $e)
    {
        $this->app[ExceptionHandler::class]->renderForConsole($output, $e);
    }
}
