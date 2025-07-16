<?php

namespace LaraGram\Listening\Middleware;

use Closure;
use LaraGram\Cache\RateLimiter;
use LaraGram\Cache\RateLimiting\Unlimited;
use LaraGram\Request\Exceptions\RequestResponseException;
use LaraGram\Request\Exceptions\ThrottleRequestsException;
use LaraGram\Listening\Exceptions\MissingRateLimiterException;
use LaraGram\Support\Collection;
use LaraGram\Support\InteractsWithTime;
use RuntimeException;
use LaraGram\Request\Response;

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
     * @return void
     */
    public function __construct(RateLimiter $limiter)
    {
        $this->limiter = $limiter;
    }

    /**
     * Specify the named rate limiter to use for the middleware.
     *
     * @param  string  $name
     * @return string
     */
    public static function using($name)
    {
        return static::class.':'.$name;
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
     * @param  \LaraGram\Request\Request  $request
     * @param  \Closure  $next
     * @param  int|string  $maxAttempts
     * @param  float|int  $decayMinutes
     * @param  string  $prefix
     * @return \LaraGram\Request\Response
     *
     * @throws \LaraGram\Request\Exceptions\ThrottleRequestsException
     * @throws \LaraGram\Listening\Exceptions\MissingRateLimiterException
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
                    'responseCallback' => null,
                ],
            ]
        );
    }

    /**
     * Handle an incoming request.
     *
     * @param  \LaraGram\Request\Request  $request
     * @param  \Closure  $next
     * @param  string  $limiterName
     * @param  \Closure  $limiter
     * @return \LaraGram\Request\Response
     *
     * @throws \LaraGram\Request\Exceptions\ThrottleRequestsException
     */
    protected function handleRequestUsingNamedLimiter($request, Closure $next, $limiterName, Closure $limiter)
    {
        $limiterResponse = $limiter($request);

        if ($limiterResponse instanceof Response) {
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
                    'responseCallback' => $limit->responseCallback,
                ];
            })->all()
        );
    }

    /**
     * Handle an incoming request.
     *
     * @param  \LaraGram\Request\Request  $request
     * @param  \Closure  $next
     * @param  array  $limits
     * @return \LaraGram\Request\Response
     *
     * @throws \LaraGram\Request\Exceptions\ThrottleRequestsException
     */
    protected function handleRequest($request, Closure $next, array $limits)
    {
        foreach ($limits as $limit) {
            if ($this->limiter->tooManyAttempts($limit->key, $limit->maxAttempts)) {
                throw $this->buildException($request, $limit->responseCallback);
            }

            $this->limiter->hit($limit->key, $limit->decaySeconds);
        }

        return $next($request);
    }

    /**
     * Resolve the number of attempts if the user is authenticated or not.
     *
     * @param  \LaraGram\Request\Request  $request
     * @param  int|string  $maxAttempts
     * @return int
     *
     * @throws \LaraGram\Listening\Exceptions\MissingRateLimiterException
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
     * @param  \LaraGram\Request\Request  $request
     * @return string
     *
     * @throws \RuntimeException
     */
    protected function resolveRequestSignature($request)
    {
        if ($user = $request->user()) {
            return $this->formatIdentifier($user->getAuthIdentifier());
        } elseif ($request->listen()) {
            return $this->formatIdentifier(user()->id);
        }

        throw new RuntimeException('Unable to generate the request signature. Route unavailable.');
    }

    /**
     * Create a 'too many attempts' exception.
     *
     * @param  \LaraGram\Request\Request  $request
     * @param  callable|null  $responseCallback
     * @return \LaraGram\Request\Exceptions\ThrottleRequestsException|\LaraGram\Request\Exceptions\RequestResponseException
     */
    protected function buildException($request, $responseCallback = null)
    {
        return is_callable($responseCallback)
            ? new RequestResponseException($responseCallback($request))
            : new ThrottleRequestsException('Too Many Attempts.');
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
