<?php

namespace LaraGram\Conversation;

use LaraGram\Support\ServiceProvider;

class ConversationServiceProvider extends ServiceProvider
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