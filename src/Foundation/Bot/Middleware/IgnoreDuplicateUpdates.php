<?php

namespace LaraGram\Foundation\Bot\Middleware;

use Closure;
use LaraGram\Contracts\Foundation\Application;
use LaraGram\Foundation\Bot\Events\DuplicateUpdateSkipped;
use LaraGram\Request\Request;
use LaraGram\Request\Response;
use Throwable;

/**
 * Handles every update once.
 *
 * Telegram delivers an update again when a webhook answers too slowly or
 * fails, and getUpdates does when it was not confirmed. Each update id is
 * claimed once per bot connection in the cache; an update that was already
 * claimed is skipped. Enable it with `$middleware->deduplicateUpdates()`.
 */
class IgnoreDuplicateUpdates
{
    /**
     * The server variable that lets an update through even if it was handled
     * before (replays and simulations). Like LARAGRAM_BOT_CONNECTION, it can
     * only be set by the process feeding the update, never by a client.
     *
     * @var string
     */
    public const ALLOW_DUPLICATE_VARIABLE = 'LARAGRAM_ALLOW_DUPLICATE';

    /**
     * The cache store the update ids are claimed in (null for the default store).
     *
     * @var string|null
     */
    protected static ?string $store = null;

    /**
     * The number of seconds an update id stays claimed.
     *
     * Telegram keeps undelivered updates for at most a day.
     *
     * @var int
     */
    protected static int $ttl = 86400;

    /**
     * Create a new middleware instance.
     *
     * @param  \LaraGram\Contracts\Foundation\Application  $app
     * @return void
     */
    public function __construct(protected Application $app)
    {
        //
    }

    /**
     * Set the cache store and the time update ids stay claimed.
     *
     * @param  string|null  $store
     * @param  int  $ttl
     * @return void
     */
    public static function using(?string $store = null, int $ttl = 86400): void
    {
        static::$store = $store;
        static::$ttl = $ttl;
    }

    /**
     * Handle the incoming request.
     *
     * @param  mixed  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($request instanceof Request && $this->isDuplicate($request)) {
            $this->app['events']->dispatch(new DuplicateUpdateSkipped($request));

            return new Response;
        }

        return $next($request);
    }

    /**
     * Determine if the update was already handled, claiming it otherwise.
     *
     * A cache failure lets the update through.
     *
     * @param  \LaraGram\Request\Request  $request
     * @return bool
     */
    protected function isDuplicate(Request $request): bool
    {
        if ($request->server()->get(static::ALLOW_DUPLICATE_VARIABLE) || ! is_numeric($updateId = $request->update_id)) {
            return false;
        }

        try {
            return ! $this->app['cache']->store(static::$store)->add(
                'laragram:update:'.($request->getBoundConnection() ?? 'default').':'.$updateId,
                1,
                static::$ttl,
            );
        } catch (Throwable $e) {
            report($e);

            return false;
        }
    }
}
