<?php

namespace LaraGram\Support\Facades;

use LaraGram\Contracts\Broadcasting\Factory as BroadcastingFactoryContract;

/**
 * @method static void routes(array|null $attributes = null)
 * @method static void userRoutes(array|null $attributes = null)
 * @method static void channelRoutes(array|null $attributes = null)
 * @method static string|null socket(\LaraGram\Http\Request|null $request = null)
 * @method static \LaraGram\Broadcasting\AnonymousEvent on(\LaraGram\Broadcasting\Channel|array|string $channels)
 * @method static \LaraGram\Broadcasting\AnonymousEvent private(string $channel)
 * @method static \LaraGram\Broadcasting\AnonymousEvent presence(string $channel)
 * @method static \LaraGram\Broadcasting\Telegram\Recipients to(\LaraGram\Broadcasting\Channel|array|string|int $audiences)
 * @method static \LaraGram\Broadcasting\Telegram\Recipients users()
 * @method static \LaraGram\Broadcasting\Telegram\Recipients groups()
 * @method static \LaraGram\Broadcasting\Telegram\Recipients supergroups()
 * @method static \LaraGram\Broadcasting\Telegram\Recipients channels()
 * @method static \LaraGram\Broadcasting\Telegram\Recipients chats()
 * @method static \LaraGram\Broadcasting\Telegram\Recipients members(int|string $chatId)
 * @method static \LaraGram\Broadcasting\Telegram\SentBroadcast sent(string $id)
 * @method static string recall(string $id, bool $partial = false)
 * @method static \LaraGram\Broadcasting\BroadcastManager tag(array|int|string $chatIds, array|string $tags, string|null $bot = null)
 * @method static \LaraGram\Broadcasting\BroadcastManager untag(array|int|string $chatIds, array|string $tags, string|null $bot = null)
 * @method static \LaraGram\Broadcasting\Telegram\Progress[] recent(int $limit = 20)
 * @method static \LaraGram\Broadcasting\BroadcastManager audience(string $name, callable|string $resolver)
 * @method static \LaraGram\Broadcasting\Telegram\AudienceRegistry audiences()
 * @method static \LaraGram\Contracts\Broadcasting\BroadcastStore store()
 * @method static \LaraGram\Broadcasting\BroadcastManager recallUsing(string $method, callable $callback)
 * @method static \LaraGram\Broadcasting\Telegram\Progress|null progress(string $id)
 * @method static bool cancel(string $id)
 * @method static \LaraGram\Broadcasting\PendingBroadcast event(mixed $event = null)
 * @method static void queue(mixed $event)
 * @method static mixed connection(\UnitEnum|string|null $name = null)
 * @method static mixed driver(\UnitEnum|string|null $name = null)
 * @method static string getDefaultDriver()
 * @method static void setDefaultDriver(\UnitEnum|string $name)
 * @method static string getTelegramConnection()
 * @method static void purge(\UnitEnum|string|null $name = null)
 * @method static \LaraGram\Broadcasting\BroadcastManager extend(string $driver, \Closure $callback)
 * @method static \LaraGram\Contracts\Foundation\Application getApplication()
 * @method static \LaraGram\Broadcasting\BroadcastManager setApplication(\LaraGram\Contracts\Foundation\Application $app)
 * @method static \LaraGram\Broadcasting\BroadcastManager forgetDrivers()
 * @method static string|null resolveConnectionFromQueueRoute(object $queueable)
 * @method static string|null resolveQueueFromQueueRoute(object $queueable)
 * @method static mixed auth(\LaraGram\Http\Request $request)
 * @method static mixed validAuthenticationResponse(\LaraGram\Http\Request $request, mixed $result)
 * @method static void broadcast(array $channels, string $event, array $payload = [])
 * @method static array|null resolveAuthenticatedUser(\LaraGram\Http\Request $request)
 * @method static void resolveAuthenticatedUserUsing(\Closure $callback)
 * @method static \LaraGram\Broadcasting\Broadcasters\Broadcaster channel(\LaraGram\Contracts\Broadcasting\HasBroadcastChannel|string $channel, callable|string $callback, array $options = [])
 * @method static \LaraGram\Support\Collection getChannels()
 *
 * @see \LaraGram\Broadcasting\BroadcastManager
 * @see \LaraGram\Broadcasting\Broadcasters\Broadcaster
 */
class Broadcast extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return BroadcastingFactoryContract::class;
    }
}
