<?php

namespace LaraGram\Foundation\Console;

use Closure;
use DateTimeInterface;
use LaraGram\Console\Application as Commander;
use LaraGram\Console\Command;
use LaraGram\Console\Scheduling\Schedule;
use LaraGram\Contracts\Console\Kernel as KernelContract;
use LaraGram\Contracts\Debug\ExceptionHandler;
use LaraGram\Events\Dispatcher;
use LaraGram\Foundation\Application;
use LaraGram\Foundation\Events\Terminating;
use LaraGram\Support\Arr;
use LaraGram\Support\Collection;
use LaraGram\Support\Env;
use LaraGram\Support\Finder\Finder;
use LaraGram\Support\InteractsWithTime;
use LaraGram\Support\Tempora;
use LaraGram\Tempora\TemporaInterval;
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
    protected $commander;

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
     * The paths where Commander "listens" should be automatically discovered.
     *
     * @var array
     */
    protected $commandListenPaths = [];

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
     * @var \LAraGram\Support\Tempora|null
     */
    protected $commandStartedAt;

    /**
     * The bootstrap classes for the application.
     *
     * @var string[]
     */
    protected $bootstrappers = [
        \LaraGram\Foundation\Bootstrap\LoadEnvironmentVariables::class,
        \LaraGram\Foundation\Bootstrap\LoadConfiguration::class,
        \LaraGram\Foundation\Bootstrap\HandleExceptions::class,
        \LaraGram\Foundation\Bootstrap\RegisterFacades::class,
        \LaraGram\Foundation\Bootstrap\SetRequestForConsole::class,
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
        if (! defined('COMMANDER_BINARY')) {
            define('COMMANDER_BINARY', 'laragram');
        }

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
        $this->commandStartedAt = Tempora::now();

        try {
            if (in_array($input->getFirstArgument(), ['env:encrypt', 'env:decrypt'], true)) {
                $this->bootstrapWithoutBootingProviders();
            }

            $this->bootstrap();

            return $this->getCommander()->run($input, $output);
        } catch (Throwable $e) {
            $this->reportException($e);

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

        $this->commandStartedAt->setTimezone($this->app['config']->get('app.timezone') ?? 'UTC');

        foreach ($this->commandLifecycleDurationHandlers as ['threshold' => $threshold, 'handler' => $handler]) {
            $end ??= Tempora::now();

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
        $threshold = $threshold instanceof DateTimeInterface
            ? $this->secondsUntil($threshold) * 1000
            : $threshold;

        $threshold = $threshold instanceof TemporaInterval
            ? $threshold->totalMilliseconds
            : $threshold;

        $this->commandLifecycleDurationHandlers[] = [
            'threshold' => $threshold,
            'handler' => $handler,
        ];
    }

    /**
     * When the command being handled started.
     *
     * @return \LaraGram\Support\Tempora|null
     */
    public function commandStartedAt()
    {
        return $this->commandStartedAt;
    }

    /**
     * Define the application's command schedule.
     *
     * @param  \LaraGram\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        //
    }

    /**
     * Resolve a console schedule instance.
     *
     * @return \LaraGram\Console\Scheduling\Schedule
     */
    public function resolveConsoleSchedule()
    {
        return tap(new Schedule($this->scheduleTimezone()), function ($schedule) {
            $this->schedule($schedule->useCache($this->scheduleCache()));
        });
    }

    /**
     * Get the timezone that should be used by default for scheduled events.
     *
     * @return \DateTimeZone|string|null
     */
    protected function scheduleTimezone()
    {
        $config = $this->app['config'];

        return $config->get('app.schedule_timezone', $config->get('app.timezone'));
    }

    /**
     * Get the name of the cache store that should manage scheduling mutexes.
     *
     * @return string|null
     */
    protected function scheduleCache()
    {
        return $this->app['config']->get('cache.schedule_store', Env::get('SCHEDULE_CACHE_DRIVER', function () {
            return Env::get('SCHEDULE_CACHE_STORE');
        }));
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

        Commander::starting(function ($commander) use ($command) {
            $commander->add($command);
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

        foreach (Finder::create()->in($paths)->files() as $file) {
            $command = $this->commandClassFromFile($file, $namespace);

            if (is_subclass_of($command, Command::class) &&
                ! (new ReflectionClass($command))->isAbstract()) {
                Commander::starting(function ($commander) use ($command) {
                    $commander->resolve($command);
                });
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
                substr($file->getRealPath(), strlen(realpath(app()->path()) . DIRECTORY_SEPARATOR))
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
     * Queue the given console command.
     *
     * @param  string  $command
     * @param  array  $parameters
     * @return \LaraGram\Foundation\Bus\PendingDispatch
     */
    public function queue($command, array $parameters = [])
    {
        return QueuedCommand::dispatch(func_get_args());
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

        foreach ($this->commandListenPaths as $path) {
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
            (new Collection($this->bootstrappers()))
                ->reject(fn ($bootstrapper) => $bootstrapper === \LaraGram\Foundation\Bootstrap\BootProviders::class)
                ->all()
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
        if (is_null($this->commander)) {
            $this->commander = (new commander($this->app, $this->events, $this->app->version()))
                ->resolveCommands($this->commands)
                ->setContainerCommandLoader();
        }

        return $this->commander;
    }

    /**
     * Set the Commander application instance.
     *
     * @param  \LaraGram\Console\Application|null  $commander
     * @return void
     */
    public function setCommander($commander)
    {
        $this->commander = $commander;
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
     * Set the paths that should have their Commander "listens" automatically discovered.
     *
     * @param  array  $paths
     * @return $this
     */
    public function addCommandListenPaths(array $paths)
    {
        $this->commandListenPaths = array_values(array_unique(array_merge($this->commandListenPaths, $paths)));

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
     * Report the exception to the exception handler.
     *
     * @param  \Throwable  $e
     * @return void
     */
    protected function reportException(Throwable $e)
    {
        $this->app[ExceptionHandler::class]->report($e);
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
