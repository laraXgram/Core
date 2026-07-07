<?php

namespace LaraGram\Http\Client;

use Closure;
use LaraGram\Http\Client\Core\Exceptions\ConnectException;
use LaraGram\Http\Client\Core\Middleware;
use LaraGram\Http\Client\Promises\Create;
use LaraGram\Http\Client\Promises\PromiseInterface;
use LaraGram\Http\Client\Core\Response as Psr7Response;
use LaraGram\Http\Client\Core\TransferStats;
use LaraGram\Contracts\Events\Dispatcher;
use LaraGram\Support\Collection;
use LaraGram\Support\Str;
use LaraGram\Support\Stringable;
use LaraGram\Support\Traits\Macroable;
use InvalidArgumentException;
use JsonException;

/**
 * @mixin \LaraGram\Http\Client\PendingRequest
 */
class Factory
{
    use Macroable {
        __call as macroCall;
    }

    /**
     * The event dispatcher implementation.
     *
     * @var \LaraGram\Contracts\Events\Dispatcher|null
     */
    protected $dispatcher;

    /**
     * The middleware to apply to every request.
     *
     * @var array
     */
    protected $globalMiddleware = [];

    /**
     * The options to apply to every request.
     *
     * @var \Closure|array
     */
    protected $globalOptions = [];

    /**
     * The stub callables that will handle requests.
     *
     * @var \LaraGram\Support\Collection
     */
    protected $stubCallbacks;

    /**
     * Indicates if the factory is recording requests and responses.
     *
     * @var bool
     */
    protected $recording = false;

    /**
     * The recorded response array.
     *
     * @var list<array{0: \LaraGram\Http\Client\Request, 1: \LaraGram\Http\Client\Response|null}>
     */
    protected $recorded = [];

    /**
     * All created response sequences.
     *
     * @var list<\LaraGram\Http\Client\ResponseSequence>
     */
    protected $responseSequences = [];

    /**
     * Indicates that an exception should be thrown if any request is not faked.
     *
     * @var bool
     */
    protected $preventStrayRequests = false;

    /**
     * A list of URL patterns that are allowed to bypass the stray request guard.
     *
     * @var array<int, string>
     */
    protected $allowedStrayRequestUrls = [];

    /**
     * Create a new factory instance.
     *
     * @param  \LaraGram\Contracts\Events\Dispatcher|null  $dispatcher
     */
    public function __construct(?Dispatcher $dispatcher = null)
    {
        $this->dispatcher = $dispatcher;

        $this->stubCallbacks = new Collection;
    }

    /**
     * Add middleware to apply to every request.
     *
     * @param  callable  $middleware
     * @return $this
     */
    public function globalMiddleware($middleware)
    {
        $this->globalMiddleware[] = $middleware;

        return $this;
    }

    /**
     * Add request middleware to apply to every request.
     *
     * @param  callable  $middleware
     * @return $this
     */
    public function globalRequestMiddleware($middleware)
    {
        $this->globalMiddleware[] = Middleware::mapRequest($middleware);

        return $this;
    }

    /**
     * Add response middleware to apply to every request.
     *
     * @param  callable  $middleware
     * @return $this
     */
    public function globalResponseMiddleware($middleware)
    {
        $this->globalMiddleware[] = Middleware::mapResponse($middleware);

        return $this;
    }

    /**
     * Set the options to apply to every request.
     *
     * @param  \Closure|array  $options
     * @return $this
     */
    public function globalOptions($options)
    {
        $this->globalOptions = $options;

        return $this;
    }

    /**
     * Create a new response instance for use during stubbing.
     *
     * @param  array|string|null  $body
     * @param  int  $status
     * @param  array  $headers
     * @return \LaraGram\Http\Client\Promises\PromiseInterface
     */
    public static function response($body = null, $status = 200, $headers = [])
    {
        return Create::promiseFor(
            static::psr7Response($body, $status, $headers)
        );
    }

    /**
     * Create a new PSR-7 response instance for use during stubbing.
     *
     * @param  array|string|null  $body
     * @param  int  $status
     * @param  array<string, mixed>  $headers
     * @return \LaraGram\Http\Client\Core\Response
     *
     * @throws \InvalidArgumentException
     */
    public static function psr7Response($body = null, $status = 200, $headers = [])
    {
        if (is_array($body)) {
            try {
                $body = json_encode($body, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                throw new InvalidArgumentException('HTTP fake response body could not be JSON encoded.', previous: $e);
            }

            $headers['Content-Type'] = 'application/json';
        }

        if (! is_string($body) && ! is_null($body)) {
            throw new InvalidArgumentException('HTTP fake response body must be a string, array, or null.');
        }

        return new Psr7Response($status, static::normalizeResponseHeaders($headers), $body);
    }

    /**
     * Normalize the given fake response headers.
     *
     * @param  array  $headers
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    protected static function normalizeResponseHeaders(array $headers): array
    {
        foreach ($headers as $name => $value) {
            if (is_array($value)) {
                if ($value === []) {
                    $headers[$name] = '';

                    continue;
                }

                foreach ($value as $key => $item) {
                    $value[$key] = match (true) {
                        $item === null => '',
                        is_scalar($item) => static::normalizeScalarString($item),
                        $item instanceof Stringable => $item->toString(),
                        default => throw new InvalidArgumentException('HTTP fake response header values must be scalar, null, LaraGram Stringable, or arrays of scalar, null, or LaraGram Stringable values.'),
                    };
                }

                $headers[$name] = $value;

                continue;
            }

            $headers[$name] = match (true) {
                $value === null => '',
                is_scalar($value) => static::normalizeScalarString($value),
                $value instanceof Stringable => $value->toString(),
                default => throw new InvalidArgumentException('HTTP fake response header values must be scalar, null, LaraGram Stringable, or arrays of scalar, null, or LaraGram Stringable values.'),
            };
        }

        return $headers;
    }

    /**
     * Normalize a scalar to a string without triggering PHP 8.5 non-finite float warnings.
     *
     * @param  scalar  $value
     * @return string
     */
    protected static function normalizeScalarString($value): string
    {
        if (is_float($value) && ! is_finite($value)) {
            return match (true) {
                is_nan($value) => 'NAN',
                $value > 0 => 'INF',
                default => '-INF',
            };
        }

        return (string) $value;
    }

    /**
     * Create a new RequestException instance for use during stubbing.
     *
     * @param  array|string|null  $body
     * @param  int  $status
     * @param  array<string, mixed>  $headers
     * @return \LaraGram\Http\Client\RequestException
     */
    public static function failedRequest($body = null, $status = 200, $headers = [])
    {
        return new RequestException(new Response(static::psr7Response($body, $status, $headers)));
    }

    /**
     * Create a new connection exception for use during stubbing.
     *
     * @param  string|null  $message
     * @return \Closure(\LaraGram\Http\Client\Request): \LaraGram\Http\Client\Promises\PromiseInterface
     */
    public static function failedConnection($message = null)
    {
        return function ($request) use ($message) {
            return Create::rejectionFor(new ConnectException(
                $message ?? "cURL error 6: Could not resolve host: {$request->toPsrRequest()->getUri()->getHost()} (see https://curl.haxx.se/libcurl/c/libcurl-errors.html) for {$request->toPsrRequest()->getUri()}.",
                $request->toPsrRequest(),
            ));
        };
    }

    /**
     * Get an invokable object that returns a sequence of responses in order for use during stubbing.
     *
     * @param  array  $responses
     * @return \LaraGram\Http\Client\ResponseSequence
     */
    public function sequence(array $responses = [])
    {
        return $this->responseSequences[] = new ResponseSequence($responses);
    }

    /**
     * Register a stub callable that will intercept requests and be able to return stub responses.
     *
     * @param  callable|array<string, mixed>|null  $callback
     * @return $this
     */
    public function fake($callback = null)
    {
        $this->record();

        $this->recorded = [];

        if (is_null($callback)) {
            $callback = function () {
                return static::response();
            };
        }

        if (is_array($callback)) {
            foreach ($callback as $url => $callable) {
                $this->stubUrl($url, $callable);
            }

            return $this;
        }

        $this->stubCallbacks = $this->stubCallbacks->merge(new Collection([
            function ($request, $options) use ($callback) {
                $response = $callback;

                while ($response instanceof Closure) {
                    $response = $response($request, $options);
                }

                if ($response instanceof PromiseInterface && ($options['on_stats'] ?? null) instanceof Closure) {
                    $options['on_stats'](new TransferStats(
                        $request->toPsrRequest(),
                        $response->wait(),
                    ));
                }

                return $response;
            },
        ]));

        return $this;
    }

    /**
     * Register a response sequence for the given URL pattern.
     *
     * @param  string  $url
     * @return \LaraGram\Http\Client\ResponseSequence
     */
    public function fakeSequence($url = '*')
    {
        return tap($this->sequence(), function ($sequence) use ($url) {
            $this->fake([$url => $sequence]);
        });
    }

    /**
     * Stub the given URL using the given callback.
     *
     * @param  string  $url
     * @param  \LaraGram\Http\Client\Response|\LaraGram\Http\Client\Promises\PromiseInterface|callable|int|string|array|\LaraGram\Http\Client\ResponseSequence  $callback
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function stubUrl($url, $callback)
    {
        return $this->fake(function ($request, $options) use ($url, $callback) {
            if (! Str::is(Str::start($url, '*'), $request->url())) {
                return;
            }

            if (is_int($callback)) {
                if ($callback >= 100 && $callback < 600) {
                    return static::response(status: $callback);
                }

                throw new InvalidArgumentException('HTTP status code must be between 100 and 599.');
            }

            if (is_string($callback)) {
                return static::response($callback);
            }

            if ($callback instanceof Closure || $callback instanceof ResponseSequence) {
                return $callback($request, $options);
            }

            return $callback;
        });
    }

    /**
     * Indicate that an exception should be thrown if any request is not faked.
     *
     * @param  bool  $prevent
     * @return $this
     */
    public function preventStrayRequests($prevent = true)
    {
        $this->preventStrayRequests = $prevent;

        return $this;
    }

    /**
     * Determine if stray requests are being prevented.
     *
     * @return bool
     */
    public function preventingStrayRequests()
    {
        return $this->preventStrayRequests;
    }

    /**
     * Allow stray, unfaked requests entirely, or optionally allow only specific URLs.
     *
     * @param  array<int, string>|null  $only
     * @return $this
     */
    public function allowStrayRequests(?array $only = null)
    {
        if (is_null($only)) {
            $this->preventStrayRequests(false);

            $this->allowedStrayRequestUrls = [];
        } else {
            $this->allowedStrayRequestUrls = array_values($only);
        }

        return $this;
    }

    /**
     * Begin recording request / response pairs.
     *
     * @return $this
     */
    public function record()
    {
        $this->recording = true;

        return $this;
    }

    /**
     * Record a request response pair.
     *
     * @param  \LaraGram\Http\Client\Request  $request
     * @param  \LaraGram\Http\Client\Response|null  $response
     * @return void
     */
    public function recordRequestResponsePair($request, $response)
    {
        if ($this->recording) {
            $this->recorded[] = [$request, $response];
        }
    }

    /**
     * Get a collection of the request / response pairs matching the given truth test.
     *
     * @param  (\Closure(\LaraGram\Http\Client\Request, \LaraGram\Http\Client\Response|null): bool)|callable  $callback
     * @return \LaraGram\Support\Collection<int, array{0: \LaraGram\Http\Client\Request, 1: \LaraGram\Http\Client\Response|null}>
     */
    public function recorded($callback = null)
    {
        if (empty($this->recorded)) {
            return new Collection;
        }

        $collect = new Collection($this->recorded);

        if ($callback) {
            return $collect->filter(fn ($pair) => $callback($pair[0], $pair[1]));
        }

        return $collect;
    }

    /**
     * Create a new pending request instance for this factory.
     *
     * @return \LaraGram\Http\Client\PendingRequest
     */
    public function createPendingRequest()
    {
        return tap($this->newPendingRequest(), function ($request) {
            $request
                ->stub($this->stubCallbacks)
                ->preventStrayRequests($this->preventStrayRequests)
                ->allowStrayRequests($this->allowedStrayRequestUrls);
        });
    }

    /**
     * Instantiate a new pending request instance for this factory.
     *
     * @return \LaraGram\Http\Client\PendingRequest
     */
    protected function newPendingRequest()
    {
        return (new PendingRequest($this, $this->globalMiddleware))->withOptions(value($this->globalOptions));
    }

    /**
     * Get the current event dispatcher implementation.
     *
     * @return \LaraGram\Contracts\Events\Dispatcher|null
     */
    public function getDispatcher()
    {
        return $this->dispatcher;
    }

    /**
     * Get the array of global middleware.
     *
     * @return array
     */
    public function getGlobalMiddleware()
    {
        return $this->globalMiddleware;
    }

    /**
     * Execute a method against a new pending request instance.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $parameters);
        }

        return $this->createPendingRequest()->{$method}(...$parameters);
    }
}
