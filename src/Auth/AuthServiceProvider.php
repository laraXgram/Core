<?php

namespace LaraGram\Auth;

use App\Models\User;
use LaraGram\Auth\Access\Gate;
use LaraGram\Contracts\Auth\Access\Gate as GateContract;
use LaraGram\Contracts\Auth\Authenticatable as AuthenticatableContract;
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
        $this->registerRequestRebindHandler();


        if ($this->app['config']->get('auth.observe_users_status', false)){
            $this->registerListens();
        }

        $this->registerGates();
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

    public function registerGates()
    {
        foreach ([
                     'creator',
                     'administrator',
                     'member',
                     'restricted',
                     'left',
                     'kicked'
                 ] as $status) {
            \LaraGram\Support\Facades\Gate::define($status, function (User $user) use ($status) {
                return $user->status === $status;
            });
        }
    }

    public function registerListens()
    {
        Bot::onChatMember(function (Request $request) {
            $user_id = $request->chat_member->new_chat_member->user->id;
            $chat_id = $request->chat_member->chat->id;
            $first_name = $request->chat_member->new_chat_member->user->first_name;
            $last_name = $request->chat_member->new_chat_member->user->last_name ?? '';
            $status = $request->chat_member->new_chat_member->status;

            $user = User::where('user_id', $user_id)
                ->where('chat_id', $chat_id)
                ->first();

            if (!$user) {
                User::create([
                    'user_id' => $user_id,
                    'chat_id' => $chat_id,
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'status' => $status,
                ]);
            } else {
                $user->status = $status;
                $user->save();
            }
        })->name('laragram-auth-chat-member');

        Bot::onMyChatMember(function (Request $request) {
            $user_id = $request->my_chat_member->new_chat_member->user->id;
            $chat_id = $request->my_chat_member->chat->id;
            $first_name = $request->my_chat_member->new_chat_member->user->first_name;
            $last_name = $request->my_chat_member->new_chat_member->user->last_name ?? '';
            $status = $request->my_chat_member->new_chat_member->status;

            $user = User::where('user_id', $user_id)
                ->where('chat_id', $chat_id)
                ->first();

            if (!$user) {
                User::create([
                    'user_id' => $user_id,
                    'chat_id' => $chat_id,
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'status' => $status,
                ]);
            } else {
                $user->status = $status;
                $user->save();
            }
        })->name('laragram-auth-my-chat-member');
    }
}
