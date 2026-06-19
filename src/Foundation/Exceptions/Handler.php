<?php

namespace LaraGram\Foundation\Exceptions;

use Closure;
use Exception;
use LaraGram\Cache\RateLimiter;
use LaraGram\Cache\RateLimiting\Limit;
use LaraGram\Cache\RateLimiting\Unlimited;
use LaraGram\Console\View\Components\BulletList;
use LaraGram\Console\View\Components\Error;
use LaraGram\Container\Container;
use LaraGram\Contracts\Debug\ExceptionHandler as ExceptionHandlerContract;
use LaraGram\Contracts\Debug\ShouldntReport;
use LaraGram\Database\Eloquent\ModelNotFoundException;
use LaraGram\Database\MultipleRecordsFoundException;
use LaraGram\Database\RecordNotFoundException;
use LaraGram\Database\RecordsNotFoundException;
use LaraGram\Listening\Exceptions\ListenNotFoundException;
use LaraGram\Support\Arr;
use LaraGram\Support\Collection;
use LaraGram\Support\Lottery;
use LaraGram\Support\Reflector;
use LaraGram\Support\Str;
use LaraGram\Support\Traits\ReflectsClosures;
use InvalidArgumentException;
use LaraGram\Log\LoggerInterface;
use LaraGram\Log\LogLevel;
use LaraGram\Console\ExtendedApplication as ConsoleApplication;
use LaraGram\Console\Exception\CommandNotFoundException;
use Throwable;
use WeakMap;

class Handler implements ExceptionHandlerContract
{
    use ReflectsClosures;

    /**
     * The container implementation.
     *
     * @var \LaraGram\Contracts\Container\Container
     */
    protected $container;

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        ListenNotFoundException::class,
    ];

    /**
     * The callbacks that should be used during reporting.
     *
     * @var \LaraGram\Foundation\Exceptions\ReportableHandler[]
     */
    protected $reportCallbacks = [];

    /**
     * A map of exceptions with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \LaraGram\Log\LogLevel::*>
     */
    protected $levels = [];

    /**
     * The callbacks that should be used to throttle reportable exceptions.
     *
     * @var array
     */
    protected $throttleCallbacks = [];

    /**
     * The callbacks that should be used to build exception context data.
     *
     * @var array
     */
    protected $contextCallbacks = [];

    /**
     * The registered exception mappings.
     *
     * @var array<string, \Closure>
     */
    protected $exceptionMap = [];

    /**
     * Indicates that throttled keys should be hashed.
     *
     * @var bool
     */
    protected $hashThrottleKeys = true;

    /**
     * A list of the internal exception types that should not be reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $internalDontReport = [
        ModelNotFoundException::class,
        MultipleRecordsFoundException::class,
        RecordNotFoundException::class,
        RecordsNotFoundException::class,
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Indicates that an exception instance should only be reported once.
     *
     * @var bool
     */
    protected $withoutDuplicates = false;

    /**
     * The already reported exception map.
     *
     * @var \WeakMap
     */
    protected $reportedExceptionMap;

    /**
     * Create a new exception handler instance.
     *
     * @param  \LaraGram\Contracts\Container\Container  $container
     * @return void
     */
    public function __construct(Container $container)
    {
        $this->container = $container;

        $this->reportedExceptionMap = new WeakMap;

        $this->register();
    }

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Register a reportable callback.
     *
     * @param  callable  $reportUsing
     * @return \LaraGram\Foundation\Exceptions\ReportableHandler
     */
    public function reportable(callable $reportUsing)
    {
        if (! $reportUsing instanceof Closure) {
            $reportUsing = Closure::fromCallable($reportUsing);
        }

        return tap(new ReportableHandler($reportUsing), function ($callback) {
            $this->reportCallbacks[] = $callback;
        });
    }

    /**
     * Register a new exception mapping.
     *
     * @param  \Closure|string  $from
     * @param  \Closure|string|null  $to
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function map($from, $to = null)
    {
        if (is_string($to)) {
            $to = fn ($exception) => new $to('', 0, $exception);
        }

        if (is_callable($from) && is_null($to)) {
            $from = $this->firstClosureParameterType($to = $from);
        }

        if (! is_string($from) || ! $to instanceof Closure) {
            throw new InvalidArgumentException('Invalid exception mapping.');
        }

        $this->exceptionMap[$from] = $to;

        return $this;
    }

    /**
     * Indicate that the given exception type should not be reported.
     *
     * Alias of "ignore".
     *
     * @param  array|string  $exceptions
     * @return $this
     */
    public function dontReport(array|string $exceptions)
    {
        return $this->ignore($exceptions);
    }

    /**
     * Indicate that the given exception type should not be reported.
     *
     * @param  array|string  $exceptions
     * @return $this
     */
    public function ignore(array|string $exceptions)
    {
        $exceptions = Arr::wrap($exceptions);

        $this->dontReport = array_values(array_unique(array_merge($this->dontReport, $exceptions)));

        return $this;
    }

    /**
     * Indicate that the given attributes should never be flashed to the session on validation errors.
     *
     * @param  array|string  $attributes
     * @return $this
     */
    public function dontFlash(array|string $attributes)
    {
        $this->dontFlash = array_values(array_unique(
            array_merge($this->dontFlash, Arr::wrap($attributes))
        ));

        return $this;
    }

    /**
     * Set the log level for the given exception type.
     *
     * @param  class-string<\Throwable>  $type
     * @param  \LaraGram\Log\LogLevel::*  $level
     * @return $this
     */
    public function level($type, $level)
    {
        $this->levels[$type] = $level;

        return $this;
    }

    /**
     * Report or log an exception.
     *
     * @param  \Throwable  $e
     * @return void
     *
     * @throws \Throwable
     */
    public function report(Throwable $e)
    {
        $e = $this->mapException($e);

        if ($this->shouldntReport($e)) {
            return;
        }

        $this->reportThrowable($e);
    }

    /**
     * Reports error based on report method on exception or to logger.
     *
     * @param  \Throwable  $e
     * @return void
     *
     * @throws \Throwable
     */
    protected function reportThrowable(Throwable $e): void
    {
        $this->reportedExceptionMap[$e] = true;

        if (Reflector::isCallable($reportCallable = [$e, 'report']) &&
            $this->container->call($reportCallable) !== false) {
            return;
        }

        foreach ($this->reportCallbacks as $reportCallback) {
            if ($reportCallback->handles($e) && $reportCallback($e) === false) {
                return;
            }
        }

        try {
            $logger = $this->newLogger();
        } catch (Exception) {
            throw $e;
        }

        $level = $this->mapLogLevel($e);

        $context = $this->buildExceptionContext($e);

        method_exists($logger, $level)
            ? $logger->{$level}($e->getMessage(), $context)
            : $logger->log($level, $e->getMessage(), $context);
    }

    /**
     * Determine if the exception should be reported.
     *
     * @param  \Throwable  $e
     * @return bool
     */
    public function shouldReport(Throwable $e)
    {
        return ! $this->shouldntReport($e);
    }

    /**
     * Determine if the exception is in the "do not report" list.
     *
     * @param  \Throwable  $e
     * @return bool
     */
    protected function shouldntReport(Throwable $e)
    {
        if ($this->withoutDuplicates && ($this->reportedExceptionMap[$e] ?? false)) {
            return true;
        }

        if ($e instanceof ShouldntReport) {
            return true;
        }

        $dontReport = array_merge($this->dontReport, $this->internalDontReport);

        if (! is_null(Arr::first($dontReport, fn ($type) => $e instanceof $type))) {
            return true;
        }

        return rescue(fn () => with($this->throttle($e), function ($throttle) use ($e) {
            if ($throttle instanceof Unlimited || $throttle === null) {
                return false;
            }

            if ($throttle instanceof Lottery) {
                return ! $throttle($e);
            }

            return ! $this->container->make(RateLimiter::class)->attempt(
                with($throttle->key ?: 'LaraGram:foundation:exceptions:'.$e::class, fn ($key) => $this->hashThrottleKeys ? md5($key) : $key),
                $throttle->maxAttempts,
                fn () => true,
                $throttle->decaySeconds
            );
        }), rescue: false, report: false);
    }

    /**
     * Throttle the given exception.
     *
     * @param  \Throwable  $e
     * @return \LaraGram\Support\Lottery|\LaraGram\Cache\RateLimiting\Limit|null
     */
    protected function throttle(Throwable $e)
    {
        foreach ($this->throttleCallbacks as $throttleCallback) {
            foreach ($this->firstClosureParameterTypes($throttleCallback) as $type) {
                if (is_a($e, $type)) {
                    $response = $throttleCallback($e);

                    if (! is_null($response)) {
                        return $response;
                    }
                }
            }
        }

        return Limit::none();
    }

    /**
     * Specify the callback that should be used to throttle reportable exceptions.
     *
     * @param  callable  $throttleUsing
     * @return $this
     */
    public function throttleUsing(callable $throttleUsing)
    {
        if (! $throttleUsing instanceof Closure) {
            $throttleUsing = Closure::fromCallable($throttleUsing);
        }

        $this->throttleCallbacks[] = $throttleUsing;

        return $this;
    }

    /**
     * Remove the given exception class from the list of exceptions that should be ignored.
     *
     * @param  array|string  $exceptions
     * @return $this
     */
    public function stopIgnoring(array|string $exceptions)
    {
        $exceptions = Arr::wrap($exceptions);

        $this->dontReport = (new Collection($this->dontReport))
            ->reject(fn ($ignored) => in_array($ignored, $exceptions))->values()->all();

        $this->internalDontReport = (new Collection($this->internalDontReport))
            ->reject(fn ($ignored) => in_array($ignored, $exceptions))->values()->all();

        return $this;
    }

    /**
     * Create the context array for logging the given exception.
     *
     * @param  \Throwable  $e
     * @return array
     */
    protected function buildExceptionContext(Throwable $e)
    {
        return array_merge(
            $this->exceptionContext($e),
            $this->context(),
            ['exception' => $e]
        );
    }

    /**
     * Get the default exception context variables for logging.
     *
     * @param  \Throwable  $e
     * @return array
     */
    protected function exceptionContext(Throwable $e)
    {
        $context = [];

        if (method_exists($e, 'context')) {
            $context = $e->context();
        }

        foreach ($this->contextCallbacks as $callback) {
            $context = array_merge($context, $callback($e, $context));
        }

        return $context;
    }

    /**
     * Get the default context variables for logging.
     *
     * @return array
     */
    protected function context()
    {
        try {
            return array_filter([
                'userId' => user()->id,
            ]);
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * Register a closure that should be used to build exception context data.
     *
     * @param  \Closure  $contextCallback
     * @return $this
     */
    public function buildContextUsing(Closure $contextCallback)
    {
        $this->contextCallbacks[] = $contextCallback;

        return $this;
    }

    /**
     * Map the exception using a registered mapper if possible.
     *
     * @param  \Throwable  $e
     * @return \Throwable
     */
    protected function mapException(Throwable $e)
    {
        if (method_exists($e, 'getInnerException') &&
            ($inner = $e->getInnerException()) instanceof Throwable) {
            return $inner;
        }

        foreach ($this->exceptionMap as $class => $mapper) {
            if (is_a($e, $class)) {
                return $mapper($e);
            }
        }

        return $e;
    }

    /**
     * Convert the given exception to an array.
     *
     * @param  \Throwable  $e
     * @return array
     */
    protected function convertExceptionToArray(Throwable $e)
    {
        return config('app.debug') ? [
            'message' => $e->getMessage(),
            'exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => (new Collection($e->getTrace()))->map(fn ($trace) => Arr::except($trace, ['args']))->all(),
        ] : [
            'message' => 'Server Error',
        ];
    }

    /**
     * Render an exception to the console.
     *
     * @param  \LaraGram\Console\Output\OutputInterface  $output
     * @param  \Throwable  $e
     * @return void
     *
     * @internal This method is not meant to be used or overwritten outside the framework.
     */
    public function renderForConsole($output, Throwable $e)
    {
        if ($e instanceof CommandNotFoundException) {
            $message = Str::of($e->getMessage())->explode('.')->first();

            if (! empty($alternatives = $e->getAlternatives())) {
                $message .= '. Did you mean one of these?';

                with(new Error($output))->render($message);
                with(new BulletList($output))->render($alternatives);

                $output->writeln('');
            } else {
                with(new Error($output))->render($message);
            }

            return;
        }

        (new ConsoleApplication)->renderThrowable($e, $output);
    }

    /**
     * Do not report duplicate exceptions.
     *
     * @return $this
     */
    public function dontReportDuplicates()
    {
        $this->withoutDuplicates = true;

        return $this;
    }

    /**
     * Map the exception to a log level.
     *
     * @param  \Throwable  $e
     * @return \LaraGram\Log\LogLevel::*
     */
    protected function mapLogLevel(Throwable $e)
    {
        return Arr::first(
            $this->levels, fn ($level, $type) => $e instanceof $type, LogLevel::ERROR
        );
    }

    /**
     * Create a new logger instance.
     *
     * @return \LaraGram\Log\LoggerInterface
     */
    protected function newLogger()
    {
        return $this->container->make(LoggerInterface::class);
    }
}
