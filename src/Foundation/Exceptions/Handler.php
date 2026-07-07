<?php

namespace LaraGram\Foundation\Exceptions;

use Closure;
use Exception;
use LaraGram\Contracts\Foundation\ExceptionRenderer;
use LaraGram\Foundation\Exceptions\Renderer\HtmlErrorRenderer;
use LaraGram\Foundation\Exceptions\Renderer\Renderer;
use LaraGram\Foundation\Http\Exceptions\HttpExceptionInterface;
use LaraGram\Http\BaseRedirectResponse;
use LaraGram\Http\RedirectResponse;
use LaraGram\Http\Response;
use LaraGram\Http\BaseResponse;
use LaraGram\Contracts\Support\Responsable;
use LaraGram\Routing\Router;
use LaraGram\Auth\Access\AuthorizationException;
use LaraGram\Auth\AuthenticationException;
use LaraGram\Http\Exceptions\HttpResponseException;
use LaraGram\Http\Exceptions\OriginMismatchException;
use LaraGram\Routing\Exceptions\BackedEnumCaseNotFoundException;
use LaraGram\Session\TokenMismatchException;
use LaraGram\Support\ViewErrorBag;
use LaraGram\Validation\ValidationException;
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
use LaraGram\Http\Exceptions\RequestExceptionInterface;
use LaraGram\Foundation\Http\Exceptions\HttpException;
use LaraGram\Foundation\Http\Exceptions\AccessDeniedHttpException;
use LaraGram\Foundation\Http\Exceptions\BadRequestHttpException;
use LaraGram\Foundation\Http\Exceptions\NotFoundHttpException;
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
    protected $dontReport = [];

    /**
     * The callbacks that inspect exceptions to determine if they should be reported.
     *
     * @var array
     */
    protected $dontReportCallbacks = [];

    /**
     * A list of the exception types that should stop job retries.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontRetry = [];

    /**
     * The callbacks that inspect exceptions to determine if they should stop job retries.
     *
     * @var array
     */
    protected $dontRetryCallbacks = [];

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
     * The exception currently being reported.
     *
     * @var \Throwable|null
     */
    protected ?Throwable $currentlyReporting = null;

    /**
     * The callbacks that should be used during rendering.
     *
     * @var \Closure[]
     */
    protected $renderCallbacks = [];

    /**
     * The callback that determines if the exception handler response should be JSON.
     *
     * @var callable|null
     */
    protected $shouldRenderJsonWhenCallback;

    /**
     * The callback that prepares responses to be returned to the browser.
     *
     * @var callable|null
     */
    protected $finalizeResponseCallback;

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
        ListenNotFoundException::class,
        ModelNotFoundException::class,
        MultipleRecordsFoundException::class,
        RecordNotFoundException::class,
        RecordsNotFoundException::class,
        AuthenticationException::class,
        AuthorizationException::class,
        BackedEnumCaseNotFoundException::class,
        HttpException::class,
        HttpResponseException::class,
        OriginMismatchException::class,
        RequestExceptionInterface::class,
        TokenMismatchException::class,
        ValidationException::class,
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
     * Register a renderable callback.
     *
     * @param  callable  $renderUsing
     * @return $this
     */
    public function renderable(callable $renderUsing)
    {
        if (! $renderUsing instanceof Closure) {
            $renderUsing = Closure::fromCallable($renderUsing);
        }

        $this->renderCallbacks[] = $renderUsing;

        return $this;
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
     * Register a callback to determine if an exception should not be reported.
     *
     * @param  (callable(\Throwable): bool)  $dontReportWhen
     * @return $this
     */
    public function dontReportWhen(callable $dontReportWhen)
    {
        if (! $dontReportWhen instanceof Closure) {
            $dontReportWhen = Closure::fromCallable($dontReportWhen);
        }

        $this->dontReportCallbacks[] = $dontReportWhen;

        return $this;
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
     * Indicate that the given exception type should stop job retries.
     *
     * @param  array|string  $exceptions
     * @return $this
     */
    public function dontRetry(array|string $exceptions)
    {
        $exceptions = Arr::wrap($exceptions);

        $this->dontRetry = array_values(array_unique(array_merge($this->dontRetry, $exceptions)));

        return $this;
    }

    /**
     * Register a callback to determine if an exception should stop job retries.
     *
     * @param  (callable(\Throwable): bool)  $dontRetryWhen
     * @return $this
     */
    public function dontRetryWhen(callable $dontRetryWhen)
    {
        if (! $dontRetryWhen instanceof Closure) {
            $dontRetryWhen = Closure::fromCallable($dontRetryWhen);
        }

        $this->dontRetryCallbacks[] = $dontRetryWhen;

        return $this;
    }

    /**
     * Determine if the exception should stop job retries.
     *
     * @param  \Throwable  $e
     * @return bool
     */
    public function shouldStopRetries(Throwable $e)
    {
        if (! is_null(Arr::first($this->dontRetry, fn ($type) => $e instanceof $type))) {
            return true;
        }

        foreach ($this->dontRetryCallbacks as $dontRetryCallback) {
            if ($dontRetryCallback($e) === true) {
                return true;
            }
        }

        return false;
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

        $originallyReporting = $this->currentlyReporting;

        try {
            $context = $this->buildExceptionContext($e);

            method_exists($logger, $level)
                ? $logger->{$level}($e->getMessage(), $context)
                : $logger->log($level, $e->getMessage(), $context);
        } finally {
            $this->currentlyReporting = $originallyReporting;
        }
    }

    /**
     * Determine if a given exception is being reported.
     */
    public function isReporting(Throwable $e): bool
    {
        return $this->currentlyReporting === $e;
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

        foreach ($this->dontReportCallbacks as $dontReportCallback) {
            if ($dontReportCallback($e) === true) {
                return true;
            }
        }

        return rescue(fn () => with($this->throttle($e), function ($throttle) use ($e) {
            if ($throttle instanceof Unlimited || $throttle === null) {
                return false;
            }

            if ($throttle instanceof Lottery) {
                return ! $throttle($e);
            }

            return ! $this->container->make(RateLimiter::class)->attempt(
                with($throttle->key ?: 'laragram:foundation:exceptions:'.$e::class, fn ($key) => $this->hashThrottleKeys ? md5($key) : $key),
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
            ->reject(fn ($ignored) => in_array($ignored, $exceptions))
            ->values()
            ->all();

        $this->internalDontReport = (new Collection($this->internalDontReport))
            ->reject(fn ($ignored) => in_array($ignored, $exceptions))
            ->values()
            ->all();

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
     * Creates the context for an exception.
     *
     * @return array<array-key, mixed>
     */
    public function buildContextForException(Throwable $e)
    {
        return $this->exceptionContext($e);
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
     * Render an exception into an HTTP response.
     *
     * @param  \LaraGram\Http\Request  $request
     * @param  \Throwable  $e
     * @return \LaraGram\Http\BaseResponse
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $e)
    {
        $e = $this->mapException($e);

        if (method_exists($e, 'render') && $response = $e->render($request)) {
            return $this->finalizeRenderedResponse(
                $request,
                Router::toResponse($request, $response),
                $e
            );
        }

        if ($e instanceof Responsable) {
            return $this->finalizeRenderedResponse($request, $e->toResponse($request), $e);
        }

        $e = $this->prepareException($e);

        if ($response = $this->renderViaCallbacks($request, $e)) {
            return $this->finalizeRenderedResponse($request, $response, $e);
        }

        return $this->finalizeRenderedResponse($request, match (true) {
            $e instanceof HttpResponseException => $e->getResponse(),
            $e instanceof AuthenticationException => $this->unauthenticated($request, $e),
            $e instanceof ValidationException => $this->convertValidationExceptionToResponse($e, $request),
            default => $this->renderExceptionResponse($request, $e),
        }, $e);
    }

    /**
     * Prepare the final, rendered response to be returned to the browser.
     *
     * @param  \LaraGram\Http\Request  $request
     * @param  \LaraGram\Http\BaseResponse  $response
     * @param  \Throwable  $e
     * @return \LaraGram\Http\BaseResponse
     */
    protected function finalizeRenderedResponse($request, $response, Throwable $e)
    {
        return $this->finalizeResponseCallback
            ? call_user_func($this->finalizeResponseCallback, $response, $e, $request)
            : $response;
    }

    /**
     * Prepare the final, rendered response for an exception using the given callback.
     *
     * @param  callable  $callback
     * @return $this
     */
    public function respondUsing($callback)
    {
        $this->finalizeResponseCallback = $callback;

        return $this;
    }

    /**
     * Prepare exception for rendering.
     *
     * @param  \Throwable  $e
     * @return \Throwable
     */
    protected function prepareException(Throwable $e)
    {
        return match (true) {
            $e instanceof BackedEnumCaseNotFoundException => new NotFoundHttpException($e->getMessage(), $e),
            $e instanceof ModelNotFoundException => new NotFoundHttpException($e->getMessage(), $e),
            $e instanceof AuthorizationException && $e->hasStatus() => new HttpException(
                $e->status(), $e->response()?->message() ?: (Response::$statusTexts[$e->status()] ?? 'Whoops, looks like something went wrong.'), $e
            ),
            $e instanceof AuthorizationException && ! $e->hasStatus() => new AccessDeniedHttpException($e->getMessage(), $e),
            $e instanceof OriginMismatchException => new HttpException(403, $e->getMessage(), $e),
            $e instanceof TokenMismatchException => new HttpException(419, $e->getMessage(), $e),
            $e instanceof RequestExceptionInterface => new BadRequestHttpException('Bad request.', $e),
            $e instanceof RecordNotFoundException => new NotFoundHttpException('Not found.', $e),
            $e instanceof RecordsNotFoundException => new NotFoundHttpException('Not found.', $e),
            default => $e,
        };
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
     * Try to render a response from request and exception via render callbacks.
     *
     * @param  \LaraGram\Http\Request  $request
     * @param  \Throwable  $e
     * @return mixed
     *
     * @throws \ReflectionException
     */
    protected function renderViaCallbacks($request, Throwable $e)
    {
        foreach ($this->renderCallbacks as $renderCallback) {
            foreach ($this->firstClosureParameterTypes($renderCallback) as $type) {
                if (is_a($e, $type)) {
                    $response = $renderCallback($e, $request);

                    if (! is_null($response)) {
                        return $response;
                    }
                }
            }
        }
    }

    /**
     * Render a default exception response if any.
     *
     * @param  \LaraGram\Http\Request  $request
     * @param  \Throwable  $e
     * @return \LaraGram\Http\Response|\LaraGram\Http\JsonResponse|\LaraGram\Http\RedirectResponse
     */
    protected function renderExceptionResponse($request, Throwable $e)
    {
        return $this->shouldReturnJson($request, $e)
            ? $this->prepareJsonResponse($request, $e)
            : $this->prepareResponse($request, $e);
    }

    /**
     * Convert an authentication exception into a response.
     *
     * @param  \LaraGram\Http\Request  $request
     * @param  \LaraGram\Auth\AuthenticationException  $exception
     * @return \LaraGram\Http\Response|\LaraGram\Http\JsonResponse|\LaraGram\Http\RedirectResponse
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($this->shouldReturnJson($request, $exception)) {
            return response()->json(['message' => $exception->getMessage()], 401);
        }

        $redirectTo = $exception->redirectTo($request);

        if (! $redirectTo) {
            return response()->noContent(401);
        }

        return redirect()->guest($redirectTo);
    }

    /**
     * Create a response object from the given validation exception.
     *
     * @param  \LaraGram\Validation\ValidationException  $e
     * @param  \LaraGram\Http\Request  $request
     * @return \LaraGram\Http\BaseResponse
     */
    protected function convertValidationExceptionToResponse(ValidationException $e, $request)
    {
        if ($e->response) {
            return $e->response;
        }

        return $this->shouldReturnJson($request, $e)
            ? $this->invalidJson($request, $e)
            : $this->invalid($request, $e);
    }

    /**
     * Convert a validation exception into a response.
     *
     * @param  \LaraGram\Http\Request  $request
     * @param  \LaraGram\Validation\ValidationException  $exception
     * @return \LaraGram\Http\Response|\LaraGram\Http\JsonResponse|\LaraGram\Http\RedirectResponse
     */
    protected function invalid($request, ValidationException $exception)
    {
        return redirect($exception->redirectTo ?? url()->previous())
            ->withInput(Arr::except($request->input(), $this->dontFlash))
            ->withErrors($exception->errors(), $request->input('_error_bag', $exception->errorBag));
    }

    /**
     * Convert a validation exception into a JSON response.
     *
     * @param  \LaraGram\Http\Request  $request
     * @param  \LaraGram\Validation\ValidationException  $exception
     * @return \LaraGram\Http\JsonResponse
     */
    protected function invalidJson($request, ValidationException $exception)
    {
        return response()->json([
            'message' => $exception->getMessage(),
            'errors' => $exception->errors(),
        ], $exception->status);
    }

    /**
     * Determine if the exception handler response should be JSON.
     *
     * @param  \LaraGram\Http\Request  $request
     * @param  \Throwable  $e
     * @return bool
     */
    protected function shouldReturnJson($request, Throwable $e)
    {
        return $this->shouldRenderJsonWhenCallback
            ? call_user_func($this->shouldRenderJsonWhenCallback, $request, $e)
            : $request->expectsJson();
    }

    /**
     * Register the callable that determines if the exception handler response should be JSON.
     *
     * @param  callable(\LaraGram\Http\Request $request, \Throwable): bool  $callback
     * @return $this
     */
    public function shouldRenderJsonWhen($callback)
    {
        $this->shouldRenderJsonWhenCallback = $callback;

        return $this;
    }

    /**
     * Prepare a response for the given exception.
     *
     * @param  \LaraGram\Http\Request  $request
     * @param  \Throwable  $e
     * @return \LaraGram\Http\Response|\LaraGram\Http\JsonResponse|\LaraGram\Http\RedirectResponse
     */
    protected function prepareResponse($request, Throwable $e)
    {
        if (! $this->isHttpException($e) && config('app.debug')) {
            return $this->toLaraGramResponse($this->convertExceptionToResponse($e), $e)->prepare($request);
        }

        if (! $this->isHttpException($e)) {
            $e = new HttpException(500, $e->getMessage(), $e);
        }

        return $this->toLaraGramResponse(
            $this->renderHttpException($e), $e
        )->prepare($request);
    }

    /**
     * Create a LaraGram response for the given exception.
     *
     * @param  \Throwable  $e
     * @return \LaraGram\Http\BaseResponse
     */
    protected function convertExceptionToResponse(Throwable $e)
    {
        return new BaseResponse(
            $this->renderExceptionContent($e),
            $this->isHttpException($e) ? $e->getStatusCode() : 500,
            $this->isHttpException($e) ? $e->getHeaders() : []
        );
    }

    /**
     * Get the response content for the given exception.
     *
     * @param  \Throwable  $e
     * @return string
     */
    protected function renderExceptionContent(Throwable $e)
    {
        try {
            if (config('app.debug')) {
                if (app()->has(ExceptionRenderer::class)) {
                    return $this->renderExceptionWithCustomRenderer($e);
                } elseif ($this->container->bound(Renderer::class)) {
                    return $this->container->make(Renderer::class)->render(request(), $e);
                }
            }

            return $this->renderExceptionWithLaraGram($e, config('app.debug'));
        } catch (Throwable $e) {
            return $this->renderExceptionWithLaraGram($e, config('app.debug'));
        }
    }

    /**
     * Render an exception to a string using the registered `ExceptionRenderer`.
     *
     * @param  \Throwable  $e
     * @return string
     */
    protected function renderExceptionWithCustomRenderer(Throwable $e)
    {
        return app(ExceptionRenderer::class)->render($e);
    }

    /**
     * Render an exception to a string using LaraGram.
     *
     * @param  \Throwable  $e
     * @param  bool  $debug
     * @return string
     */
    protected function renderExceptionWithLaraGram(Throwable $e, $debug)
    {
        $renderer = new HtmlErrorRenderer($debug);

        return $renderer->render($e)->getAsString();
    }

    /**
     * Render the given HttpException.
     *
     * @param  \LaraGram\Foundation\Http\Exceptions\HttpExceptionInterface  $e
     * @return \LaraGram\Http\BaseResponse
     *
     * @throws \Throwable
     */
    protected function renderHttpException(HttpExceptionInterface $e)
    {
        $this->registerErrorViewPaths();

        if ($view = $this->getHttpExceptionView($e)) {
            try {
                return response()->view($view, [
                    'errors' => new ViewErrorBag,
                    'exception' => $e,
                ], $e->getStatusCode(), $e->getHeaders());
            } catch (Throwable $t) {
                config('app.debug') && throw $t;

                $this->report($t);
            }
        }

        return $this->convertExceptionToResponse($e);
    }

    /**
     * Register the error template hint paths.
     *
     * @return void
     */
    protected function registerErrorViewPaths()
    {
        (new RegisterErrorViewPaths)();
    }

    /**
     * Get the view used to render HTTP exceptions.
     *
     * @param  \LaraGram\Foundation\Http\Exceptions\HttpExceptionInterface  $e
     * @return string|null
     */
    protected function getHttpExceptionView(HttpExceptionInterface $e)
    {
        $view = 'errors::'.$e->getStatusCode();

        if (view()->exists($view)) {
            return $view;
        }

        $view = substr($view, 0, -2).'xx';

        if (view()->exists($view)) {
            return $view;
        }

        return null;
    }

    /**
     * Map the given exception into an LaraGram response.
     *
     * @param  \LaraGram\Http\BaseResponse  $response
     * @param  \Throwable  $e
     * @return \LaraGram\Http\Response|\LaraGram\Http\RedirectResponse
     */
    protected function toLaraGramResponse($response, Throwable $e)
    {
        if ($response instanceof BaseRedirectResponse) {
            $response = new RedirectResponse(
                $response->getTargetUrl(), $response->getStatusCode(), $response->headers->all()
            );
        } else {
            $response = response(
                $response->getContent(), $response->getStatusCode(), $response->headers->all()
            );
        }

        return $response->withException($e);
    }

    /**
     * Prepare a JSON response for the given exception.
     *
     * @param  \LaraGram\Http\Request  $request
     * @param  \Throwable  $e
     * @return \LaraGram\Http\JsonResponse
     */
    protected function prepareJsonResponse($request, Throwable $e)
    {
        return response()->json(
            $this->convertExceptionToArray($e),
            $this->isHttpException($e) ? $e->getStatusCode() : 500,
            $this->isHttpException($e) ? $e->getHeaders() : [],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
        );
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
            'message' => $this->isHttpException($e) ? $e->getMessage() : 'Server Error',
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
     * Determine if the given exception is an HTTP exception.
     *
     * @param  \Throwable  $e
     * @return bool
     */
    protected function isHttpException(Throwable $e)
    {
        return $e instanceof HttpExceptionInterface;
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
