<?php

namespace LaraGram\Auth;

use LaraGram\Auth\Access\Gate;
use LaraGram\Auth\Status\StatusManager;
use LaraGram\Contracts\Auth\Access\Gate as GateContract;
use LaraGram\Contracts\Auth\Authenticatable as AuthenticatableContract;
use LaraGram\Contracts\Auth\StatusProvider as StatusProviderContract;
use LaraGram\Listening\Listen;
use LaraGram\Request\Request;
use LaraGram\Support\Facades\Bot;
use LaraGram\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerAuthenticator();
        $this->registerUserResolver();
        $this->registerAccessGate();
        $this->registerStatusManager();
        $this->registerRequestRebindHandler();

        if ($this->app['auth.status']->shouldObserve()) {
            $this->registerListens();
        }
    }

    /**
     * Register the chat-member status manager.
     *
     * @return void
     */
    protected function registerStatusManager()
    {
        $this->app->singleton('auth.status', fn ($app) => new StatusManager($app));

        $this->app->alias('auth.status', StatusManager::class);

        $this->app->bind(StatusProviderContract::class, fn ($app) => $app['auth.status']->driver());
    }

    /**
     * Register the authenticator services.
     *
     * @return void
     */
    protected function registerAuthenticator()
    {
        $this->app->singleton('auth', fn($app) => new AuthManager($app));
    }

    /**
     * Register a resolver for the authenticated user.
     *
     * @return void
     */
    protected function registerUserResolver()
    {
        $this->app->bind(AuthenticatableContract::class, fn($app) => call_user_func($app['auth']->userResolver()));
    }

    /**
     * Register the access gate service.
     *
     * @return void
     */
    protected function registerAccessGate()
    {
        $this->app->singleton(GateContract::class, function ($app) {
            return new Gate($app, fn() => call_user_func($app['auth']->userResolver()));
        });
    }

    /**
     * Handle the re-binding of the request binding.
     *
     * @return void
     */
    protected function registerRequestRebindHandler()
    {
        $this->app->rebinding('request', function ($app, $request) {
            $request->setUserResolver(function () use ($app) {
                return call_user_func($app['auth']->userResolver());
            });
        });
    }

    /**
     * Listen for chat-member changes and persist them through the status driver.
     *
     * @return void
     */
    public function registerListens()
    {
        Bot::onChatMember(function (Request $request) {
            $this->storeStatus(
                $request->chat_member->new_chat_member->user,
                $request->chat_member->chat,
                $request->chat_member->new_chat_member->status
            );
        })->name('laragram-auth-chat-member');

        Bot::onMyChatMember(function (Request $request) {
            $this->storeStatus(
                $request->my_chat_member->new_chat_member->user,
                $request->my_chat_member->chat,
                $request->my_chat_member->new_chat_member->status
            );
        })->name('laragram-auth-my-chat-member');
    }

    /**
     * Persist a chat-member status change through the status manager.
     *
     * @param  object  $user
     * @param  object  $chat
     * @param  string  $status
     * @return void
     */
    private function storeStatus($user, $chat, $status)
    {
        $this->app['auth.status']->record($user->id, $chat->id, [
            'first_name' => $user->first_name,
            'last_name'  => $user->last_name ?? '',
            'status'     => $status,
        ]);
    }
}
