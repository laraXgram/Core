<?php

namespace LaraGram\Broadcasting;

use LaraGram\Broadcasting\Telegram\AudienceRegistry;
use LaraGram\Broadcasting\Telegram\BotApiMethods;
use LaraGram\Broadcasting\Telegram\Stores\DatabaseStore;
use LaraGram\Broadcasting\Telegram\Stores\NullStore;
use LaraGram\Broadcasting\Telegram\Stores\RedisStore;
use LaraGram\Contracts\Broadcasting\Broadcaster as BroadcasterContract;
use LaraGram\Contracts\Broadcasting\BroadcastStore;
use LaraGram\Contracts\Broadcasting\Factory as BroadcastingFactory;
use LaraGram\Contracts\Support\DeferrableProvider;
use LaraGram\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(BroadcastManager::class, function ($app) {
            BotApiMethods::ensure();

            return new BroadcastManager($app);
        });

        $this->app->singleton(BroadcasterContract::class, function ($app) {
            return $app->make(BroadcastManager::class)->connection();
        });

        $this->app->alias(
            BroadcastManager::class, BroadcastingFactory::class
        );

        $this->app->singleton(AudienceRegistry::class, fn ($app) => new AudienceRegistry($app));

        $this->app->singleton(BroadcastStore::class, function ($app) {
            $store = $app['config']->get('broadcasting.store', 'database');
            $config = (array) $app['config']->get("broadcasting.stores.{$store}", ['driver' => $store]);

            return match ($config['driver'] ?? 'database') {
                'null' => new NullStore,
                'redis' => new RedisStore($app['redis'], $config['connection'] ?? null, $config['prefix'] ?? 'broadcast'),
                default => new DatabaseStore($app['db'], $config['connection'] ?? null, [
                    'chats' => $config['chats_table'] ?? null,
                    'members' => $config['members_table'] ?? null,
                    'targets' => $config['targets_table'] ?? null,
                ]),
            };
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            BroadcastManager::class,
            BroadcastingFactory::class,
            BroadcasterContract::class,
            AudienceRegistry::class,
            BroadcastStore::class,
        ];
    }
}
