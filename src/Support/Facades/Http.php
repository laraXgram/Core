<?php

namespace LaraGram\Support\Facades;

use LaraGram\Http\Client\Factory;

/**
 * @method static \LaraGram\Http\Client\Factory globalMiddleware(callable $middleware)
 * @method static \LaraGram\Http\Client\Factory globalRequestMiddleware(callable $middleware)
 * @method static \LaraGram\Http\Client\Factory globalResponseMiddleware(callable $middleware)
 * @method static \LaraGram\Http\Client\Factory globalOptions(\Closure|array $options)
 * @method static \LaraGram\Http\Client\Promises\PromiseInterface response(array|string|null $body = null, int $status = 200, array $headers = [])
 * @method static \LaraGram\Http\Client\Core\Response psr7Response(array|string|null $body = null, int $status = 200, array $headers = [])
 * @method static \LaraGram\Http\Client\RequestException failedRequest(array|string|null $body = null, int $status = 200, array $headers = [])
 * @method static \Closure failedConnection(string|null $message = null)
 * @method static \LaraGram\Http\Client\ResponseSequence sequence(array $responses = [])
 * @method static bool preventingStrayRequests()
 * @method static \LaraGram\Http\Client\Factory allowStrayRequests(array|null $only = null)
 * @method static \LaraGram\Http\Client\Factory record()
 * @method static void recordRequestResponsePair(\LaraGram\Http\Client\Request $request, \LaraGram\Http\Client\Response|null $response)
 * @method static void assertSent(callable|\Closure $callback)
 * @method static void assertSentInOrder(array $callbacks)
 * @method static void assertNotSent(callable|\Closure $callback)
 * @method static void assertNothingSent()
 * @method static void assertSentCount(int $count)
 * @method static void assertSequencesAreEmpty()
 * @method static \LaraGram\Support\Collection recorded(\Closure|callable $callback = null)
 * @method static \LaraGram\Http\Client\PendingRequest createPendingRequest()
 * @method static \LaraGram\Contracts\Events\Dispatcher|null getDispatcher()
 * @method static array getGlobalMiddleware()
 * @method static void macro(string $name, object|callable $macro)
 * @method static void mixin(object $mixin, bool $replace = true)
 * @method static bool hasMacro(string $name)
 * @method static void flushMacros()
 * @method static mixed macroCall(string $method, array $parameters)
 * @method static \LaraGram\Http\Client\PendingRequest baseUrl(string $url)
 * @method static \LaraGram\Http\Client\PendingRequest withBody(\LaraGram\Http\Factory\StreamInterface|string $content, string $contentType = 'application/json')
 * @method static \LaraGram\Http\Client\PendingRequest asJson()
 * @method static \LaraGram\Http\Client\PendingRequest asForm()
 * @method static \LaraGram\Http\Client\PendingRequest attach(string|array $name, string|resource $contents = '', string|null $filename = null, array $headers = [])
 * @method static \LaraGram\Http\Client\PendingRequest asMultipart()
 * @method static \LaraGram\Http\Client\PendingRequest bodyFormat(string $format)
 * @method static \LaraGram\Http\Client\PendingRequest withQueryParameters(array $parameters)
 * @method static \LaraGram\Http\Client\PendingRequest contentType(string $contentType)
 * @method static \LaraGram\Http\Client\PendingRequest acceptJson()
 * @method static \LaraGram\Http\Client\PendingRequest accept(string $contentType)
 * @method static \LaraGram\Http\Client\PendingRequest withHeaders(array $headers)
 * @method static \LaraGram\Http\Client\PendingRequest withHeader(string $name, mixed $value)
 * @method static \LaraGram\Http\Client\PendingRequest replaceHeaders(array $headers)
 * @method static \LaraGram\Http\Client\PendingRequest withBasicAuth(string $username, string $password)
 * @method static \LaraGram\Http\Client\PendingRequest withDigestAuth(string $username, string $password)
 * @method static \LaraGram\Http\Client\PendingRequest withNtlmAuth(string $username, string $password)
 * @method static \LaraGram\Http\Client\PendingRequest withToken(string $token, string $type = 'Bearer')
 * @method static \LaraGram\Http\Client\PendingRequest withUserAgent(string|bool $userAgent)
 * @method static \LaraGram\Http\Client\PendingRequest withUrlParameters(array $parameters = [])
 * @method static \LaraGram\Http\Client\PendingRequest withCookies(array $cookies, string $domain)
 * @method static \LaraGram\Http\Client\PendingRequest maxRedirects(int $max)
 * @method static \LaraGram\Http\Client\PendingRequest withoutRedirecting()
 * @method static \LaraGram\Http\Client\PendingRequest withoutVerifying()
 * @method static \LaraGram\Http\Client\PendingRequest sink(string|resource $to)
 * @method static \LaraGram\Http\Client\PendingRequest timeout(int|float $seconds)
 * @method static \LaraGram\Http\Client\PendingRequest connectTimeout(int|float $seconds)
 * @method static \LaraGram\Http\Client\PendingRequest retry(array|int $times, \Closure|int $sleepMilliseconds = 0, callable|null $when = null, bool $throw = true)
 * @method static \LaraGram\Http\Client\PendingRequest withOptions(array $options)
 * @method static \LaraGram\Http\Client\PendingRequest withMiddleware(callable $middleware)
 * @method static \LaraGram\Http\Client\PendingRequest withRequestMiddleware(callable $middleware)
 * @method static \LaraGram\Http\Client\PendingRequest withResponseMiddleware(callable $middleware)
 * @method static \LaraGram\Http\Client\PendingRequest withAttributes(array $attributes)
 * @method static \LaraGram\Http\Client\PendingRequest beforeSending(callable $callback)
 * @method static \LaraGram\Http\Client\PendingRequest afterResponse(callable|null $callback)
 * @method static \LaraGram\Http\Client\PendingRequest throw(callable|null $callback = null)
 * @method static \LaraGram\Http\Client\PendingRequest throwIf(callable|bool $condition)
 * @method static \LaraGram\Http\Client\PendingRequest throwUnless(callable|bool $condition)
 * @method static \LaraGram\Http\Client\PendingRequest dump()
 * @method static \LaraGram\Http\Client\PendingRequest dd()
 * @method static \LaraGram\Http\Client\Response|\LaraGram\Http\Client\Promises\PromiseInterface get(string $url, array|string|null $query = null)
 * @method static \LaraGram\Http\Client\Response|\LaraGram\Http\Client\Promises\PromiseInterface head(string $url, array|string|null $query = null)
 * @method static \LaraGram\Http\Client\Response|\LaraGram\Http\Client\Promises\PromiseInterface post(string $url, array|\JsonSerializable|\LaraGram\Contracts\Support\Arrayable $data = [])
 * @method static \LaraGram\Http\Client\Response|\LaraGram\Http\Client\Promises\PromiseInterface patch(string $url, array|\JsonSerializable|\LaraGram\Contracts\Support\Arrayable $data = [])
 * @method static \LaraGram\Http\Client\Response|\LaraGram\Http\Client\Promises\PromiseInterface put(string $url, array|\JsonSerializable|\LaraGram\Contracts\Support\Arrayable $data = [])
 * @method static \LaraGram\Http\Client\Response|\LaraGram\Http\Client\Promises\PromiseInterface delete(string $url, array|\JsonSerializable|\LaraGram\Contracts\Support\Arrayable $data = [])
 * @method static array pool(callable $callback, int|null $concurrency = 0)
 * @method static \LaraGram\Http\Client\Batch batch(callable $callback)
 * @method static \LaraGram\Http\Client\Response|\LaraGram\Http\Client\Promises\LazyPromise send(string $method, string $url, array $options = [])
 * @method static \LaraGram\Http\Client\Core\Client buildClient()
 * @method static \LaraGram\Http\Client\Core\Client createClient(\LaraGram\Http\Client\Core\HandlerStack $handlerStack)
 * @method static \LaraGram\Http\Client\Core\HandlerStack buildHandlerStack()
 * @method static \LaraGram\Http\Client\Core\HandlerStack pushHandlers(\LaraGram\Http\Client\Core\HandlerStack $handlerStack)
 * @method static \Closure buildBeforeSendingHandler()
 * @method static \Closure buildRecorderHandler()
 * @method static \Closure buildStubHandler()
 * @method static \LaraGram\Http\Factory\RequestInterface runBeforeSendingCallbacks(\LaraGram\Http\Factory\RequestInterface $request, array $options)
 * @method static array mergeOptions(array ...$options)
 * @method static \LaraGram\Http\Client\PendingRequest stub(callable $callback)
 * @method static bool isAllowedRequestUrl(string $url)
 * @method static \LaraGram\Http\Client\PendingRequest async(bool $async = true)
 * @method static \LaraGram\Http\Client\Promises\PromiseInterface|null getPromise()
 * @method static \LaraGram\Http\Client\PendingRequest truncateExceptionsAt(int $length)
 * @method static \LaraGram\Http\Client\PendingRequest dontTruncateExceptions()
 * @method static \LaraGram\Http\Client\PendingRequest setClient(\LaraGram\Http\Client\Core\Client $client)
 * @method static \LaraGram\Http\Client\PendingRequest setHandler(callable $handler)
 * @method static array getOptions()
 * @method static \LaraGram\Http\Client\PendingRequest|mixed when(\Closure|mixed|null $value = null, callable|null $callback = null, callable|null $default = null)
 * @method static \LaraGram\Http\Client\PendingRequest|mixed unless(\Closure|mixed|null $value = null, callable|null $callback = null, callable|null $default = null)
 *
 * @see \LaraGram\Http\Client\Factory
 */
class Http extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return Factory::class;
    }

    /**
     * Register a stub callable that will intercept requests and be able to return stub responses.
     *
     * @param  \Closure|array|null  $callback
     * @return \LaraGram\Http\Client\Factory
     */
    public static function fake($callback = null)
    {
        return tap(static::getFacadeRoot(), function ($fake) use ($callback) {
            static::swap($fake->fake($callback));
        });
    }

    /**
     * Register a response sequence for the given URL pattern.
     *
     * @param  string  $urlPattern
     * @return \LaraGram\Http\Client\ResponseSequence
     */
    public static function fakeSequence(string $urlPattern = '*')
    {
        $fake = tap(static::getFacadeRoot(), function ($fake) {
            static::swap($fake);
        });

        return $fake->fakeSequence($urlPattern);
    }

    /**
     * Indicate that an exception should be thrown if any request is not faked.
     *
     * @param  bool  $prevent
     * @return \LaraGram\Http\Client\Factory
     */
    public static function preventStrayRequests($prevent = true)
    {
        return tap(static::getFacadeRoot(), function ($fake) use ($prevent) {
            static::swap($fake->preventStrayRequests($prevent));
        });
    }

    /**
     * Stub the given URL using the given callback.
     *
     * @param  string  $url
     * @param  \LaraGram\Http\Client\Response|\LaraGram\Http\Client\Promises\PromiseInterface|callable  $callback
     * @return \LaraGram\Http\Client\Factory
     */
    public static function stubUrl($url, $callback)
    {
        return tap(static::getFacadeRoot(), function ($fake) use ($url, $callback) {
            static::swap($fake->stubUrl($url, $callback));
        });
    }
}
