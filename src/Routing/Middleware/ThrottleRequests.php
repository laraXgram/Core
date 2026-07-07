<?php

namespace LaraGram\Routing\Middleware;

use Closure;
use LaraGram\Cache\RateLimiter;
use LaraGram\Cache\RateLimiting\Unlimited;
use LaraGram\Http\Exceptions\HttpResponseException;
use LaraGram\Http\Exceptions\ThrottleRequestsException;
use LaraGram\Routing\Exceptions\MissingRateLimiterException;
use LaraGram\Support\Collection;
use LaraGram\Support\InteractsWithTime;
use RuntimeException;
use LaraGram\Http\BaseResponse;

use function LaraGram\Support\enum_value;

class ThrottleRequests
{
    use InteractsWithTime;

    /**
     * The rate limiter instance.
     *
     * @var \LaraGram\Cache\RateLimiter
     */
    protected $limiter;

    /**
     * Indicates if the rate limiter keys should be hashed.
     *
     * @var bool
     */
    protected static $shouldHashKeys = true;

    /**
     * Create a new request throttler.
     *
     * @param  \LaraGram\Cache\RateLimiter  $limiter
     */
    public function __construct(RateLimiter $limiter)
    {
        $this->limiter = $limiter;
    }

    /**
     * Specify the named rate limiter to use for the middleware.
     *
     * @param  \UnitEnum|string  $name
     * @return string
     */
    public static function using($name)
    {
        return static::class.':'.enum_value($name);
    }

    /**
     * Specify the rate limiter configuration for the middleware.
     *
     * @param  int  $maxAttempts
     * @param  int  $decayMinutes
     * @param  string  $prefix
     * @return string
     *
     * @named-arguments-supported
     */
    public static function with($maxAttempts = 60, $decayMinutes = 1, $prefix = '')
    {
        return static::class.':'.implode(',', func_get_args());
    }

    /**
     * Handle an incoming request.
     *
     * @param  \LaraGram\Http\Request  $request
     * @param  \Closure  $next
     * @param  int|string  $maxAttempts
     * @param  float|int  $decayMinutes
     * @param  string  $prefix
     * @return \LaraGram\Http\BaseResponse
     *
     * @throws \LaraGram\Http\Exceptions\ThrottleRequestsException
     * @throws \LaraGram\Routing\Exceptions\MissingRateLimiterException
     */
    public function handle($request, Closure $next, $maxAttempts = 60, $decayMinutes = 1, $prefix = '')
    {
        if (is_string($maxAttempts)
            && func_num_args() === 3
            && ! is_null($limiter = $this->limiter->limiter($maxAttempts))) {
            return $this->handleRequestUsingNamedLimiter($request, $next, $maxAttempts, $limiter);
        }

        return $this->handleRequest(
            $request,
            $next,
            [
                (object) [
                    'key' => $prefix.$this->resolveRequestSignature($request),
                    'maxAttempts' => $this->resolveMaxAttempts($request, $maxAttempts),
                    'decaySeconds' => 60 * $decayMinutes,
                    'afterCallback' => null,
                    'responseCallback' => null,
                ],
            ]
        );
    }

    /**
     * Handle an incoming request.
     *
     * @param  \LaraGram\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $limiterName
     * @param  \Closure  $limiter
     * @return \LaraGram\Http\BaseResponse
     *
     * @throws \LaraGram\Http\Exceptions\ThrottleRequestsException
     */
    protected function handleRequestUsingNamedLimiter($request, Closure $next, $limiterName, Closure $limiter)
    {
        $limiterResponse = $limiter($request);

        if ($limiterResponse instanceof BaseResponse) {
            return $limiterResponse;
        } elseif ($limiterResponse instanceof Unlimited) {
            return $next($request);
        }

        return $this->handleRequest(
            $request,
            $next,
            Collection::wrap($limiterResponse)->map(function ($limit) use ($limiterName) {
                return (object) [
                    'key' => self::$shouldHashKeys ? md5($limiterName.$limit->key) : $limiterName.':'.$limit->key,
                    'maxAttempts' => $limit->maxAttempts,
                    'decaySeconds' => $limit->decaySeconds,
                    'afterCallback' => $limit->afterCallback,
                    'responseCallback' => $limit->responseCallback,
                ];
            })->all()
        );
    }

    /**
     * Handle an incoming request.
     *
     * @param  \LaraGram\Http\Request  $request
     * @param  \Closure  $next
     * @param  array  $limits
     * @return \LaraGram\Http\BaseResponse
     *
     * @throws \LaraGram\Http\Exceptions\ThrottleRequestsException
     */
    protected function handleRequest($request, Closure $next, array $limits)
    {
        foreach ($limits as $limit) {
            if ($this->limiter->tooManyAttempts($limit->key, $limit->maxAttempts)) {
                throw $this->buildException($request, $limit->key, $limit->maxAttempts, $limit->responseCallback);
            }
        }

        foreach ($limits as $limit) {
            if (! $limit->afterCallback) {
                $this->limiter->hit($limit->key, $limit->decaySeconds);
            }
        }

        $response = $next($request);

        foreach ($limits as $limit) {
            if ($limit->afterCallback && ($limit->afterCallback)($response)) {
                $this->limiter->hit($limit->key, $limit->decaySeconds);
            }

            $response = $this->addHeaders(
                $response,
                $limit->maxAttempts,
                $this->calculateRemainingAttempts($limit->key, $limit->maxAttempts)
            );
        }

        return $response;
    }

    /**
     * Resolve the number of attempts if the user is authenticated or not.
     *
     * @param  \LaraGram\Http\Request  $request
     * @param  int|string  $maxAttempts
     * @return int
     *
     * @throws \LaraGram\Routing\Exceptions\MissingRateLimiterException
     */
    protected function resolveMaxAttempts($request, $maxAttempts)
    {
        if (str_contains($maxAttempts, '|')) {
            $maxAttempts = explode('|', $maxAttempts, 2)[$request->user() ? 1 : 0];
        }

        if (! is_numeric($maxAttempts) &&
            $request->user()?->hasAttribute($maxAttempts)
        ) {
            $maxAttempts = $request->user()->{$maxAttempts};
        }

        // If we still don't have a numeric value, there was no matching rate limiter...
        if (! is_numeric($maxAttempts)) {
            is_null($request->user())
                ? throw MissingRateLimiterException::forLimiter($maxAttempts)
                : throw MissingRateLimiterException::forLimiterAndUser($maxAttempts, get_class($request->user()));
        }

        return (int) $maxAttempts;
    }

    /**
     * Resolve request signature.
     *
     * @param  \LaraGram\Http\Request  $request
     * @return string
     *
     * @throws \RuntimeException
     */
    protected function resolveRequestSignature($request)
    {
        if ($user = $request->user()) {
            return $this->formatIdentifier($user->getAuthIdentifier());
        } elseif ($route = $request->route()) {
            return $this->formatIdentifier($route->getDomain().'|'.$request->ip());
        }

        throw new RuntimeException('Unable to generate the request signature. Route unavailable.');
    }

    /**
     * Create a 'too many attempts' exception.
     *
     * @param  \LaraGram\Http\Request  $request
     * @param  string  $key
     * @param  int  $maxAttempts
     * @param  callable|null  $responseCallback
     * @return \LaraGram\Http\Exceptions\ThrottleRequestsException|\LaraGram\Http\Exceptions\HttpResponseException
     */
    protected function buildException($request, $key, $maxAttempts, $responseCallback = null)
    {
        $retryAfter = $this->getTimeUntilNextRetry($key);

        $headers = $this->getHeaders(
            $maxAttempts,
            $this->calculateRemainingAttempts($key, $maxAttempts, $retryAfter),
            $retryAfter
        );

        return is_callable($responseCallback)
            ? new HttpResponseException($responseCallback($request, $headers))
            : new ThrottleRequestsException('Too Many Attempts.', null, $headers);
    }

    /**
     * Get the number of seconds until the next retry.
     *
     * @param  string  $key
     * @return int
     */
    protected function getTimeUntilNextRetry($key)
    {
        return $this->limiter->availableIn($key);
    }

    /**
     * Add the limit header information to the given response.
     *
     * @param  \LaraGram\Http\BaseResponse  $response
     * @param  int  $maxAttempts
     * @param  int  $remainingAttempts
     * @param  int|null  $retryAfter
     * @return \LaraGram\Http\BaseResponse
     */
    protected function addHeaders(BaseResponse $response, $maxAttempts, $remainingAttempts, $retryAfter = null)
    {
        $response->headers->add(
            $this->getHeaders($maxAttempts, $remainingAttempts, $retryAfter, $response)
        );

        return $response;
    }

    /**
     * Get the limit headers information.
     *
     * @param  int  $maxAttempts
     * @param  int  $remainingAttempts
     * @param  int|null  $retryAfter
     * @param  \LaraGram\Http\BaseResponse|null  $response
     * @return array
     */
    protected function getHeaders($maxAttempts,
        $remainingAttempts,
        $retryAfter = null,
        ?BaseResponse $response = null)
    {
        if ($response &&
            ! is_null($response->headers->get('X-RateLimit-Remaining')) &&
            (int) $response->headers->get('X-RateLimit-Remaining') <= (int) $remainingAttempts) {
            return [];
        }

        $headers = [
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => $remainingAttempts,
        ];

        if (! is_null($retryAfter)) {
            $headers['Retry-After'] = $retryAfter;
            $headers['X-RateLimit-Reset'] = $this->availableAt($retryAfter);
        }

        return $headers;
    }

    /**
     * Calculate the number of remaining attempts.
     *
     * @param  string  $key
     * @param  int  $maxAttempts
     * @param  int|null  $retryAfter
     * @return int
     */
    protected function calculateRemainingAttempts($key, $maxAttempts, $retryAfter = null)
    {
        return is_null($retryAfter) ? $this->limiter->retriesLeft($key, $maxAttempts) : 0;
    }

    /**
     * Format the given identifier based on the configured hashing settings.
     *
     * @param  string  $value
     * @return string
     */
    private function formatIdentifier($value)
    {
        return self::$shouldHashKeys ? sha1($value) : $value;
    }

    /**
     * Specify whether rate limiter keys should be hashed.
     *
     * @param  bool  $shouldHashKeys
     * @return void
     */
    public static function shouldHashKeys(bool $shouldHashKeys = true)
    {
        self::$shouldHashKeys = $shouldHashKeys;
    }
}
