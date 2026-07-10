<?php

namespace LaraGram\Auth;

use LaraGram\Contracts\Auth\Guard;
use LaraGram\Contracts\Auth\UserProvider;

/**
 * Authentication guard for Telegram bot requests.
 *
 * The authenticated identity is derived from the current Telegram update's
 * user (resolved through the global user() helper) and hydrated through the
 * configured user provider by its Telegram user identifier.
 */
class BotGuard implements Guard
{
    use GuardHelpers;

    /**
     * Create a new bot authentication guard.
     *
     * @param  \LaraGram\Contracts\Auth\UserProvider  $provider
     * @return void
     */
    public function __construct(UserProvider $provider)
    {
        $this->provider = $provider;
    }

    /**
     * Get the currently authenticated user.
     *
     * @return \LaraGram\Contracts\Auth\Authenticatable|null
     */
    public function user()
    {
        if (! is_null($this->user)) {
            return $this->user;
        }

        $telegramUser = user();

        if (! is_null($telegramUser) && isset($telegramUser->id)) {
            $this->user = $this->provider->retrieveByUserId($telegramUser->id);
        }

        return $this->user;
    }

    /**
     * Validate a user's credentials.
     *
     * Bot authentication is identity-based (no password credentials), so this
     * confirms whether the given Telegram user id resolves to a known user.
     *
     * @param  array  $credentials
     * @return bool
     */
    public function validate(array $credentials = [])
    {
        if (empty($credentials['id'] ?? null)) {
            return false;
        }

        return ! is_null($this->provider->retrieveByUserId($credentials['id']));
    }
}
