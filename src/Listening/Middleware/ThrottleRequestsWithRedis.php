<?php

namespace LaraGram\Listening\Middleware;

use Closure;
use LaraGram\Cache\RateLimiter;
use LaraGram\Contracts\Redis\Factory as Redis;
use LaraGram\Redis\Limiters\DurationLimiter;

class ThrottleRequestsWithRedis extends ThrottleRequests
{
    /**
     * The Redis factory implementation.
     *
     * @var \LaraGram\Contracts\Redis\Factory
     */
    protected $redis;

    /**
     * The timestamp of the end of the current duration by key.
     *
     * @var array
     */
    public $decaysAt = [];

    /**
     * The number of remaining slots by key.
     *
     * @var array
     */
    public $remaining = [];

    /**
     * Create a new request throttler.
     *
     * @param  \LaraGram\Cache\RateLimiter  $limiter
     * @param  \LaraGram\Contracts\Redis\Factory  $redis
     * @return void
     */
    public function __construct(RateLimiter $limiter, Redis $redis)
    {
        parent::__construct($limiter);

        $this->redis = $redis;
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
            if ($this->tooManyAttempts($limit->key, $limit->maxAttempts, $limit->decaySeconds)) {
                throw $this->buildException($request, $limit->responseCallback);
            }
        }

        return $next($request);
    }

    /**
     * Determine if the given key has been "accessed" too many times.
     *
     * @param  string  $key
     * @param  int  $maxAttempts
     * @param  int  $decaySeconds
     * @return mixed
     */
    protected function tooManyAttempts($key, $maxAttempts, $decaySeconds)
    {
        $limiter = new DurationLimiter(
            $this->getRedisConnection(), $key, $maxAttempts, $decaySeconds
        );

        return tap(! $limiter->acquire(), function () use ($key, $limiter) {
            [$this->decaysAt[$key], $this->remaining[$key]] = [
                $limiter->decaysAt, $limiter->remaining,
            ];
        });
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
        return is_null($retryAfter) ? $this->remaining[$key] : 0;
    }

    /**
     * Get the number of seconds until the lock is released.
     *
     * @param  string  $key
     * @return int
     */
    protected function getTimeUntilNextRetry($key)
    {
        return $this->decaysAt[$key] - $this->currentTime();
    }

    /**
     * Get the Redis connection that should be used for throttling.
     *
     * @return \LaraGram\Redis\Connections\Connection
     */
    protected function getRedisConnection()
    {
        return $this->redis->connection();
    }
}
