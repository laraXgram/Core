<?php

namespace LaraGram\Conversation;

use LaraGram\Contracts\Support\DeferrableProvider;
use LaraGram\Support\ServiceProvider;

class ConversationServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->app->singleton('conversation', function () {
            return new Conversation();
        });
    }

    public function provides(): array
    {
        return ['conversation'];
    }
}