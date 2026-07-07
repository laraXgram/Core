<?php

namespace LaraGram\Conversation;

use LaraGram\Contracts\Bot\Kernel;
use LaraGram\Conversation\Middleware\HandleConversation;
use LaraGram\Support\ServiceProvider;

class ConversationServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('conversation', function ($app) {
            return new ConversationManager(
                $app,
                $app['config'],
                $app['events'],
                $app['validator']
            );
        });

        $this->app->alias('conversation', ConversationManager::class);
    }

    /**
     * Bootstrap the service provider.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->resolved(Kernel::class)) {
            /** @var \LaraGram\Foundation\Bot\Kernel $kernel */
            $kernel = $this->app->make(Kernel::class);
            $kernel->pushMiddleware(HandleConversation::class);
        }
    }
}
