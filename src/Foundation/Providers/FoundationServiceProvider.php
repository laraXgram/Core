<?php

namespace LaraGram\Foundation\Providers;

use LaraGram\Contracts\Foundation\Application;
use LaraGram\Foundation\Console\CliDumper;
use LaraGram\Foundation\Exceptions\Renderer\HtmlErrorRenderer;
use LaraGram\Foundation\Exceptions\Renderer\Mappers\BladeMapper;
use LaraGram\Foundation\Exceptions\Renderer\Renderer;
use LaraGram\Contracts\Foundation\MaintenanceMode as MaintenanceModeContract;
use LaraGram\Foundation\MaintenanceModeManager;
use LaraGram\Foundation\Precognition;
use LaraGram\Http\VarDumper\Caster\StubCaster;
use LaraGram\Support\Facades\URL;
use LaraGram\Support\Uri;
use LaraGram\Console\Events\CommandFinished;
use LaraGram\Console\Scheduling\Schedule;
use LaraGram\Contracts\Console\Kernel as ConsoleKernel;
use LaraGram\Contracts\Container\Container;
use LaraGram\Contracts\Events\Dispatcher;
use LaraGram\Contracts\View\Factory;
use LaraGram\Database\ConnectionInterface;
use LaraGram\Database\Grammar;
use LaraGram\Foundation\Exceptions\Renderer\Listener;
use LaraGram\Foundation\Http\HtmlDumper;
use LaraGram\Foundation\Vite;
use LaraGram\Http\Client\Factory as HttpFactory;
use LaraGram\Http\VarDumper\Cloner\AbstractCloner;
use LaraGram\Queue\Events\JobAttempted;
use LaraGram\Request\Request;
use LaraGram\Http\Request as HttpRequest;
use LaraGram\Request\ValidatedInput;
use LaraGram\Support\AggregateServiceProvider;
use LaraGram\Support\Defer\DeferredCallbackCollection;
use LaraGram\Validation\ValidationException;

class FoundationServiceProvider extends AggregateServiceProvider
{
    /**
     * The provider class names.
     *
     * @var string[]
     */
    protected $providers = [
        FormRequestServiceProvider::class,
    ];

    /**
     * The singletons to register into the container.
     *
     * @var array
     */
    public $singletons = [
        HttpFactory::class => HttpFactory::class,
        Vite::class => Vite::class,
    ];

    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../Exceptions/views' => $this->app->resourcePath('views/errors/'),
            ], 'laragram-errors');
        }

        if ($this->app->hasDebugModeEnabled()) {
            $this->app->make(Listener::class)->registerListeners(
                $this->app->make(Dispatcher::class)
            );
        }
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        parent::register();

        $this->registerConsoleSchedule();
        $this->registerDumper();
        $this->registerRequestValidation();
        $this->registerRequestSignatureValidation();
        $this->registerUriUrlGeneration();
        $this->registerDeferHandler();
        $this->registerExceptionRenderer();
        $this->registerMaintenanceModeManager();
    }

    /**
     * Register the console schedule implementation.
     *
     * @return void
     */
    public function registerConsoleSchedule()
    {
        $this->app->singleton(Schedule::class, function ($app) {
            return $app->make(ConsoleKernel::class)->resolveConsoleSchedule();
        });
    }

    /**
     * Register a var dumper (with source) to debug variables.
     *
     * @return void
     */
    public function registerDumper()
    {
        AbstractCloner::$defaultCasters[ConnectionInterface::class] ??= [StubCaster::class, 'cutInternals'];
        AbstractCloner::$defaultCasters[Container::class] ??= [StubCaster::class, 'cutInternals'];
        AbstractCloner::$defaultCasters[Dispatcher::class] ??= [StubCaster::class, 'cutInternals'];
        AbstractCloner::$defaultCasters[Factory::class] ??= [StubCaster::class, 'cutInternals'];
        AbstractCloner::$defaultCasters[Grammar::class] ??= [StubCaster::class, 'cutInternals'];

        $basePath = $this->app->basePath();

        $compiledViewPath = $this->app['config']->get('view.compiled');

        $format = $_SERVER['VAR_DUMPER_FORMAT'] ?? null;

        match (true) {
            'html' == $format => HtmlDumper::register($basePath, $compiledViewPath),
            'cli' == $format => CliDumper::register($basePath, $compiledViewPath),
            'server' == $format => null,
            $format && 'tcp' == parse_url($format, PHP_URL_SCHEME) => null,
            default => in_array(PHP_SAPI, ['cli', 'phpdbg']) ? CliDumper::register($basePath, $compiledViewPath) : HtmlDumper::register($basePath, $compiledViewPath),
        };
    }

    /**
     * Register the "validate" macro on the request.
     *
     * @return void
     */
    public function registerRequestValidation()
    {
        Request::macro('validate', function (array $rules, ...$params) {
            return new ValidatedInput(
                validator($this->all(), $rules, ...$params)->validate()
            );
        });

        Request::macro('validateWithBag', function (string $errorBag, array $rules, ...$params) {
            try {
                return $this->validate($rules, ...$params);
            } catch (ValidationException $e) {
                $e->errorBag = $errorBag;

                throw $e;
            }
        });

        HttpRequest::macro('validate', function (array $rules, ...$params) {
            return tap(validator($this->all(), $rules, ...$params), function ($validator) {
                if ($this->isPrecognitive()) {
                    $validator->after(Precognition::afterValidationHook($this))
                        ->setRules(
                            $this->filterPrecognitiveRules($validator->getRulesWithoutPlaceholders())
                        );
                }
            })->validate();
        });

        HttpRequest::macro('validateWithBag', function (string $errorBag, array $rules, ...$params) {
            try {
                return $this->validate($rules, ...$params);
            } catch (ValidationException $e) {
                $e->errorBag = $errorBag;

                throw $e;
            }
        });
    }

    /**
     * Register the "hasValidSignature" macro on the request.
     *
     * @return void
     */
    public function registerRequestSignatureValidation()
    {
        \LaraGram\Http\Request::macro('hasValidSignature', function ($absolute = true) {
            return URL::hasValidSignature($this, $absolute);
        });

        \LaraGram\Http\Request::macro('hasValidRelativeSignature', function () {
            return URL::hasValidSignature($this, $absolute = false);
        });

        \LaraGram\Http\Request::macro('hasValidSignatureWhileIgnoring', function ($ignoreQuery = [], $absolute = true) {
            return URL::hasValidSignature($this, $absolute, $ignoreQuery);
        });

        \LaraGram\Http\Request::macro('hasValidRelativeSignatureWhileIgnoring', function ($ignoreQuery = []) {
            return URL::hasValidSignature($this, $absolute = false, $ignoreQuery);
        });
    }

    /**
     * Register the URL resolver for the URI generator.
     *
     * @return void
     */
    protected function registerUriUrlGeneration()
    {
        Uri::setUrlGeneratorResolver(fn () => app('url'));
    }

    /**
     * Register the "defer" function termination handler.
     *
     * @return void
     */
    protected function registerDeferHandler()
    {
        $this->app->scoped(DeferredCallbackCollection::class);

        $this->app['events']->listen(function (CommandFinished $event) {
            app(DeferredCallbackCollection::class)->invokeWhen(fn ($callback) => app()->runningInConsole() && ($event->exitCode === 0 || $callback->always));
        });

        $this->app['events']->listen(function (JobAttempted $event) {
            if (in_array($event->connectionName, ['sync', 'deferred'])) {
                return;
            }

            app(DeferredCallbackCollection::class)->invokeWhen(fn ($callback) => ($event->successful() || $callback->always));
        });
    }

    /**
     * Register the exceptions renderer.
     *
     * @return void
     */
    protected function registerExceptionRenderer()
    {
        $this->loadViewsFrom(__DIR__.'/../Exceptions/views', 'laragram-exceptions');

        if (! $this->app->hasDebugModeEnabled()) {
            return;
        }

        $this->loadViewsFrom(__DIR__.'/../resources/exceptions/renderer', 'laragram-exceptions-renderer');

        $this->app->singleton(Renderer::class, function (Application $app) {
            $errorRenderer = new HtmlErrorRenderer(
                $app['config']->get('app.debug'),
            );

            return new Renderer(
                $app->make(\LaraGram\Contracts\View\Factory::class),
                $app->make(\LaraGram\Foundation\Exceptions\Renderer\Listener::class),
                $errorRenderer,
                $app->make(BladeMapper::class),
                $app->basePath(),
            );
        });

        $this->app->singleton(Listener::class);
    }
    /**
     * Register the maintenance mode manager service.
     *
     * @return void
     */
    public function registerMaintenanceModeManager()
    {
        $this->app->singleton(MaintenanceModeManager::class);

        $this->app->bind(
            MaintenanceModeContract::class,
            fn () => $this->app->make(MaintenanceModeManager::class)->driver()
        );
    }

}
