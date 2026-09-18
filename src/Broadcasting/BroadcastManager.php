<?php

namespace LaraGram\Broadcasting;

use Closure;
use LaraGram\Broadcasting\Broadcasters\LogBroadcaster;
use LaraGram\Broadcasting\Broadcasters\NullBroadcaster;
use LaraGram\Broadcasting\Broadcasters\RedisBroadcaster;
use LaraGram\Broadcasting\Broadcasters\TelegramBroadcaster;
use LaraGram\Broadcasting\Telegram\Audience;
use LaraGram\Broadcasting\Telegram\AudienceRegistry;
use LaraGram\Broadcasting\Telegram\Progress;
use LaraGram\Broadcasting\Telegram\RecallRules;
use LaraGram\Broadcasting\Telegram\Recipients;
use LaraGram\Broadcasting\Telegram\SentBroadcast;
use LaraGram\Bus\UniqueLock;
use LaraGram\Contracts\Broadcasting\BroadcastStore;
use LaraGram\Contracts\Broadcasting\Factory as FactoryContract;
use LaraGram\Contracts\Broadcasting\ShouldBeUnique;
use LaraGram\Contracts\Broadcasting\ShouldBroadcastNow;
use LaraGram\Contracts\Broadcasting\ShouldRescue;
use LaraGram\Contracts\Bus\Dispatcher as BusDispatcherContract;
use LaraGram\Contracts\Cache\Repository as Cache;
use LaraGram\Contracts\Foundation\CachesRoutes;
use LaraGram\Log\LoggerInterface;
use LaraGram\Queue\Attributes\Connection as ConnectionAttribute;
use LaraGram\Queue\Attributes\Queue as QueueAttribute;
use LaraGram\Queue\Attributes\ReadsQueueAttributes;
use LaraGram\Support\Queue\Concerns\ResolvesQueueRoutes;
use LaraGram\Support\RebindsCallbacksToSelf;
use InvalidArgumentException;
use ReflectionException;
use RuntimeException;
use Throwable;

use function LaraGram\Support\enum_value;

/**
 * @mixin \LaraGram\Contracts\Broadcasting\Broadcaster
 */
class BroadcastManager implements FactoryContract
{
    use ReadsQueueAttributes, RebindsCallbacksToSelf, ResolvesQueueRoutes;

    /**
     * The application instance.
     *
     * @var \LaraGram\Contracts\Container\Container
     */
    protected $app;

    /**
     * The array of resolved broadcast drivers.
     *
     * @var array
     */
    protected $drivers = [];

    /**
     * The registered custom driver creators.
     *
     * @var array
     */
    protected $customCreators = [];

    /**
     * Create a new manager instance.
     *
     * @param  \LaraGram\Contracts\Container\Container  $app
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * Register the routes for handling broadcast channel authentication and sockets.
     *
     * @param  array|null  $attributes
     * @return void
     */
    public function routes(?array $attributes = null)
    {
        if ($this->app instanceof CachesRoutes && $this->app->routesAreCached()) {
            return;
        }

        $attributes = $attributes ?: ['middleware' => ['web']];

        $this->app['router']->group($attributes, function ($router) {
            $router->match(
                ['get', 'post'], '/broadcasting/auth',
                '\\'.BroadcastController::class.'@authenticate'
            )->withoutMiddleware([\LaraGram\Foundation\Http\Middleware\PreventRequestForgery::class]);
        });
    }

    /**
     * Register the routes for handling broadcast user authentication.
     *
     * @param  array|null  $attributes
     * @return void
     */
    public function userRoutes(?array $attributes = null)
    {
        if ($this->app instanceof CachesRoutes && $this->app->routesAreCached()) {
            return;
        }

        $attributes = $attributes ?: ['middleware' => ['web']];

        $this->app['router']->group($attributes, function ($router) {
            $router->match(
                ['get', 'post'], '/broadcasting/user-auth',
                '\\'.BroadcastController::class.'@authenticateUser'
            )->withoutMiddleware([\LaraGram\Foundation\Http\Middleware\PreventRequestForgery::class]);
        });
    }

    /**
     * Register the routes for handling broadcast authentication and sockets.
     *
     * Alias of "routes" method.
     *
     * @param  array|null  $attributes
     * @return void
     */
    public function channelRoutes(?array $attributes = null)
    {
        $this->routes($attributes);
    }

    /**
     * Get the socket ID for the given request.
     *
     * @param  \LaraGram\Http\Request|null  $request
     * @return string|null
     */
    public function socket($request = null)
    {
        if (! $request && ! $this->app->bound('http.request')) {
            return;
        }

        $request = $request ?: $this->app['http.request'];

        return $request->header('X-Socket-ID');
    }

    /**
     * Begin sending an anonymous broadcast to the given channels.
     */
    public function on(Channel|string|array $channels): AnonymousEvent
    {
        return new AnonymousEvent($channels);
    }

    /**
     * Begin sending an anonymous broadcast to the given private channels.
     */
    public function private(string $channel): AnonymousEvent
    {
        return $this->on(new PrivateChannel($channel));
    }

    /**
     * Begin sending an anonymous broadcast to the given presence channels.
     */
    public function presence(string $channel): AnonymousEvent
    {
        return $this->on(new PresenceChannel($channel));
    }

    /**
     * Begin a Telegram broadcast to the given chats or audiences.
     *
     * Accepts chat ids, "@username" strings, audience names ("users", "groups",
     * a registered audience), Audience channels, or an array of any of them.
     */
    public function to(Channel|array|string|int $audiences): Recipients
    {
        return new Recipients($audiences, $this->getTelegramConnection());
    }

    /**
     * Begin a Telegram broadcast to every private chat (the bot's users).
     */
    public function users(): Recipients
    {
        return $this->to(Audience::users());
    }

    /**
     * Begin a Telegram broadcast to every group and supergroup.
     */
    public function groups(): Recipients
    {
        return $this->to(Audience::groups());
    }

    /**
     * Begin a Telegram broadcast to every supergroup.
     */
    public function supergroups(): Recipients
    {
        return $this->to(Audience::supergroups());
    }

    /**
     * Begin a Telegram broadcast to every channel the bot administers.
     */
    public function channels(): Recipients
    {
        return $this->to(Audience::channels());
    }

    /**
     * Begin a Telegram broadcast to every known chat.
     */
    public function chats(): Recipients
    {
        return $this->to(Audience::chats());
    }

    /**
     * Begin a Telegram broadcast to the users recorded as members of a group or channel.
     *
     * @param  int|string  $chatId
     */
    public function members(int|string $chatId): Recipients
    {
        return $this->to(Audience::members($chatId));
    }

    /**
     * Get the recipients of a previous broadcast that remembered its messages.
     *
     * Broadcast::sent($id)->editMessageText('Updated!')->queue();
     *
     * @param  string  $id
     */
    public function sent(string $id): SentBroadcast
    {
        return new SentBroadcast($id, $this->getTelegramConnection());
    }

    /**
     * Undo a recallable broadcast: delete what it sent, unpin what it pinned,
     * unban whom it banned, and so on. Returns the identifier of the new broadcast.
     *
     * @param  string  $id
     * @param  bool  $partial  Skip the calls that cannot be undone instead of failing.
     * @return string
     *
     * @throws \LaraGram\Broadcasting\BroadcastException
     */
    public function recall(string $id, bool $partial = false)
    {
        $steps = $this->progress($id)?->steps() ?? [];

        if ($steps === []) {
            // The broadcast is unknown, so the only thing that can be undone
            // is the messages recorded for its recipients.
            return $this->sent($id)->delete()->queue();
        }

        $unsupported = [];
        $inverses = [];

        foreach (array_reverse($steps) as $step) {
            if (! RecallRules::supports($step)) {
                $unsupported[] = $step->method;

                continue;
            }

            if ($inverse = RecallRules::inverse($step)) {
                $inverses[] = $inverse;
            }
        }

        if ($unsupported !== [] && ! $partial) {
            throw new BroadcastException(sprintf(
                'Broadcast [%s] cannot be recalled because [%s] cannot be undone. Register an inverse with Broadcast::recallUsing(), or recall it partially.',
                $id, implode(', ', array_unique($unsupported))
            ));
        }

        if ($inverses === []) {
            throw new BroadcastException("Broadcast [{$id}] has nothing to undo.");
        }

        $broadcast = $this->sent($id);

        foreach ($inverses as $index => $inverse) {
            $broadcast = $index === 0 ? $inverse->appendTo($this->broadcastFor($broadcast)) : $inverse->appendTo($broadcast);
        }

        return $broadcast->queue();
    }

    /**
     * Start a broadcast for the given recipients.
     *
     * @param  \LaraGram\Broadcasting\Telegram\SentBroadcast  $recipients
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    protected function broadcastFor(SentBroadcast $recipients)
    {
        return (fn () => $this->broadcast())->call($recipients);
    }

    /**
     * Add tags to chats, for filtering broadcasts with tagged().
     *
     * @param  array|int|string  $chatIds
     * @param  array|string  $tags
     * @param  string|null  $bot  Defaults to the default bot connection.
     * @return $this
     */
    public function tag(array|int|string $chatIds, array|string $tags, ?string $bot = null)
    {
        $this->store()->tag($bot ?? $this->defaultBot(), array_values((array) $chatIds), array_values((array) $tags));

        return $this;
    }

    /**
     * Remove tags from chats.
     *
     * @param  array|int|string  $chatIds
     * @param  array|string  $tags
     * @param  string|null  $bot  Defaults to the default bot connection.
     * @return $this
     */
    public function untag(array|int|string $chatIds, array|string $tags, ?string $bot = null)
    {
        $this->store()->untag($bot ?? $this->defaultBot(), array_values((array) $chatIds), array_values((array) $tags));

        return $this;
    }

    /**
     * Get the most recent Telegram broadcasts, newest first.
     *
     * @param  int  $limit
     * @return array<int, \LaraGram\Broadcasting\Telegram\Progress>
     */
    public function recent(int $limit = 20)
    {
        return Progress::recent($limit);
    }

    /**
     * Get the bot connection used when none is given.
     *
     * @return string
     */
    protected function defaultBot(): string
    {
        $config = $this->app['config'];

        $bot = $config->get('broadcasting.connections.'.$this->getTelegramConnection().'.bot') ?: $config->get('bot.default');

        if (empty($bot) || $bot === 'auto') {
            $bot = \LaraGram\Laraquest\ConnectionRegistry::getDefaultConnection() ?? array_key_first((array) $config->get('bot.connections', []));
        }

        return (string) $bot;
    }

    /**
     * Register a named Telegram audience.
     *
     * The resolver receives the bot connection name (and any "{placeholder}"
     * parameters) and returns chat ids, models, a query, or any iterable.
     *
     * @param  string  $name
     * @param  callable|string  $resolver
     * @return $this
     */
    public function audience(string $name, callable|string $resolver)
    {
        $this->audiences()->define($name, $resolver);

        return $this;
    }

    /**
     * Get the Telegram audience registry.
     *
     * @return \LaraGram\Broadcasting\Telegram\AudienceRegistry
     */
    public function audiences()
    {
        return $this->app->make(AudienceRegistry::class);
    }

    /**
     * Get the store holding the chats, members and broadcast targets.
     *
     * @return \LaraGram\Contracts\Broadcasting\BroadcastStore
     */
    public function store()
    {
        return $this->app->make(BroadcastStore::class);
    }

    /**
     * Register the inverse of a Bot API method, used when a broadcast is recalled.
     *
     * @param  string  $method
     * @param  callable(\LaraGram\Broadcasting\Telegram\Action): (\LaraGram\Broadcasting\Telegram\Action|null)  $callback
     * @return $this
     */
    public function recallUsing(string $method, callable $callback)
    {
        RecallRules::using($method, $callback);

        return $this;
    }

    /**
     * Get the progress of a Telegram broadcast, or null when it is unknown.
     *
     * @param  string  $id
     * @return \LaraGram\Broadcasting\Telegram\Progress|null
     */
    public function progress(string $id)
    {
        $progress = Progress::for($id);

        return $progress->exists() ? $progress : null;
    }

    /**
     * Cancel a running Telegram broadcast. Recipients not reached yet are skipped.
     *
     * @param  string  $id
     * @return bool
     */
    public function cancel(string $id)
    {
        $progress = Progress::for($id);

        if (! $progress->exists() || $progress->finished()) {
            return false;
        }

        $progress->cancel();

        return true;
    }

    /**
     * Begin broadcasting an event.
     *
     * @param  mixed  $event
     * @return \LaraGram\Broadcasting\PendingBroadcast
     */
    public function event($event = null)
    {
        return new PendingBroadcast($this->app->make('events'), $event);
    }

    /**
     * Queue the given event for broadcast.
     *
     * @param  mixed  $event
     * @return void
     */
    public function queue($event)
    {
        if ($event instanceof ShouldBroadcastNow ||
            (is_object($event) &&
             method_exists($event, 'shouldBroadcastNow') &&
             $event->shouldBroadcastNow())) {
            $dispatch = fn () => $this->app->make(BusDispatcherContract::class)
                ->dispatchNow(new BroadcastEvent(clone $event));

            return $event instanceof ShouldRescue
                ? $this->rescue($dispatch)
                : $dispatch();
        }

        $queue = match (true) {
            method_exists($event, 'broadcastQueue') => $event->broadcastQueue(),
            isset($event->broadcastQueue) => $event->broadcastQueue,
            isset($event->queue) => $event->queue,
            default => null,
        };

        if (is_null($queue)) {
            $queue = $this->getAttributeValue($event, QueueAttribute::class, 'queue')
                ?? $this->resolveQueueFromQueueRoute($event)
                ?? null;
        }

        $broadcastEvent = new BroadcastEvent(clone $event);

        if ($event instanceof ShouldBeUnique) {
            $broadcastEvent = new UniqueBroadcastEvent(clone $event);

            if ($this->mustBeUniqueAndCannotAcquireLock($broadcastEvent)) {
                return;
            }
        }

        $delay = method_exists($event, 'broadcastDelay') ? $event->broadcastDelay() : null;

        $push = function () use ($event, $queue, $broadcastEvent, $delay) {
            $connection = $this->app->make('queue')->connection(
                $event->connection
                    ?? $this->getAttributeValue($event, ConnectionAttribute::class, 'connection')
                    ?? $this->resolveConnectionFromQueueRoute($event)
                    ?? null
            );

            return is_null($delay)
                ? $connection->pushOn($queue, $broadcastEvent)
                : $connection->laterOn($queue, $delay, $broadcastEvent);
        };

        $event instanceof ShouldRescue
            ? $this->rescue($push)
            : $push();
    }

    /**
     * Determine if the broadcastable event must be unique and determine if we can acquire the necessary lock.
     *
     * @param  mixed  $event
     * @return bool
     */
    protected function mustBeUniqueAndCannotAcquireLock($event)
    {
        return ! (new UniqueLock(
            method_exists($event, 'uniqueVia')
                ? $event->uniqueVia()
                : $this->app->make(Cache::class)
        ))->acquire($event);
    }

    /**
     * Get a broadcaster instance by name.
     *
     * @param  \UnitEnum|string|null  $name
     * @return mixed
     */
    public function connection($name = null)
    {
        return $this->driver($name);
    }

    /**
     * Get a driver instance.
     *
     * @param  \UnitEnum|string|null  $name
     * @return mixed
     */
    public function driver($name = null)
    {
        $name = enum_value($name) ?: $this->getDefaultDriver();

        return $this->drivers[$name] = $this->get($name);
    }

    /**
     * Attempt to get the connection from the local cache.
     *
     * @param  string  $name
     * @return \LaraGram\Contracts\Broadcasting\Broadcaster
     */
    protected function get($name)
    {
        return $this->drivers[$name] ?? $this->resolve($name);
    }

    /**
     * Resolve the given broadcaster.
     *
     * @param  string  $name
     * @return \LaraGram\Contracts\Broadcasting\Broadcaster
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    protected function resolve($name)
    {
        $config = $this->getConfig($name);

        if (is_null($config)) {
            throw new InvalidArgumentException("Broadcast connection [{$name}] is not defined.");
        }

        if (isset($this->customCreators[$config['driver']])) {
            return $this->callCustomCreator($config);
        }

        $driverMethod = 'create'.ucfirst($config['driver']).'Driver';

        if (! method_exists($this, $driverMethod)) {
            throw new InvalidArgumentException("Driver [{$config['driver']}] is not supported.");
        }

        try {
            return $this->{$driverMethod}($config);
        } catch (Throwable $e) {
            throw new RuntimeException("Failed to create broadcaster for connection \"{$name}\" with error: {$e->getMessage()}.", 0, $e);
        }
    }

    /**
     * Call a custom driver creator.
     *
     * @param  array  $config
     * @return mixed
     */
    protected function callCustomCreator(array $config)
    {
        return $this->customCreators[$config['driver']]($this->app, $config);
    }

    /**
     * Create an instance of the driver.
     *
     * @param  array  $config
     * @return \LaraGram\Contracts\Broadcasting\Broadcaster
     */
    protected function createTelegramDriver(array $config)
    {
        return new TelegramBroadcaster(
            $this->app, $this->audiences(), $config
        );
    }

    /**
     * Create an instance of the driver.
     *
     * @param  array  $config
     * @return \LaraGram\Contracts\Broadcasting\Broadcaster
     */
    protected function createRedisDriver(array $config)
    {
        return new RedisBroadcaster(
            $this->app->make('redis'), $config['connection'] ?? null,
            $this->app['config']->get('database.redis.options.prefix', '')
        );
    }

    /**
     * Create an instance of the driver.
     *
     * @param  array  $config
     * @return \LaraGram\Contracts\Broadcasting\Broadcaster
     */
    protected function createLogDriver(array $config)
    {
        return new LogBroadcaster(
            $this->app->make(LoggerInterface::class)
        );
    }

    /**
     * Create an instance of the driver.
     *
     * @param  array  $config
     * @return \LaraGram\Contracts\Broadcasting\Broadcaster
     */
    protected function createNullDriver(array $config)
    {
        return new NullBroadcaster;
    }

    /**
     * Get the connection configuration.
     *
     * @param  string  $name
     * @return array
     */
    protected function getConfig($name)
    {
        if (! is_null($name) && $name !== 'null') {
            return $this->app['config']["broadcasting.connections.{$name}"];
        }

        return ['driver' => 'null'];
    }

    /**
     * Get the default driver name.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return $this->app['config']['broadcasting.default'] ?? 'null';
    }

    /**
     * Set the default driver name.
     *
     * @param  \UnitEnum|string  $name
     * @return void
     */
    public function setDefaultDriver($name)
    {
        $this->app['config']['broadcasting.default'] = enum_value($name);
    }

    /**
     * Get the name of the connection Telegram broadcasts are sent through.
     *
     * The default connection is used when it is a Telegram connection,
     * otherwise the first connection using the "telegram" driver.
     *
     * @return string
     */
    public function getTelegramConnection()
    {
        $connections = (array) ($this->app['config']['broadcasting.connections'] ?? []);

        $default = $this->getDefaultDriver();

        if (($connections[$default]['driver'] ?? null) === 'telegram') {
            return $default;
        }

        foreach ($connections as $name => $config) {
            if (($config['driver'] ?? null) === 'telegram') {
                return (string) $name;
            }
        }

        return 'telegram';
    }

    /**
     * Disconnect the given driver / connection and remove it from local cache.
     *
     * @param  \UnitEnum|string|null  $name
     * @return void
     */
    public function purge($name = null)
    {
        $name = enum_value($name) ?? $this->getDefaultDriver();

        unset($this->drivers[$name]);
    }

    /**
     * Register a custom driver creator Closure.
     *
     * @param  string  $driver
     * @param  \Closure  $callback
     *
     * @param-closure-this  $this  $callback
     *
     * @return $this
     */
    public function extend($driver, Closure $callback)
    {
        try {
            $callback = $this->bindCallbackToSelf($callback) ?? throw new RuntimeException('Unable to bind custom driver callback');
        } catch (ReflectionException $e) {
            throw new RuntimeException('Unable to bind custom driver callback', previous: $e);
        }

        $this->customCreators[$driver] = $callback;

        return $this;
    }

    /**
     * Execute the given callback using "rescue" if possible.
     *
     * @param  \Closure  $callback
     * @return mixed
     */
    protected function rescue(Closure $callback)
    {
        if (function_exists('rescue')) {
            return rescue($callback);
        }

        return $callback();
    }

    /**
     * Get the application instance used by the manager.
     *
     * @return \LaraGram\Contracts\Foundation\Application
     */
    public function getApplication()
    {
        return $this->app;
    }

    /**
     * Set the application instance used by the manager.
     *
     * @param  \LaraGram\Contracts\Foundation\Application  $app
     * @return $this
     */
    public function setApplication($app)
    {
        $this->app = $app;

        return $this;
    }

    /**
     * Forget all of the resolved driver instances.
     *
     * @return $this
     */
    public function forgetDrivers()
    {
        $this->drivers = [];

        return $this;
    }

    /**
     * Dynamically call the default driver instance.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->driver()->$method(...$parameters);
    }
}
