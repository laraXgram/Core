<?php

namespace LaraGram\Contracts\Auth;

interface UserProvider
{
    /**
     * Retrieve a user by their Telegram user identifier (bot guard).
     *
     * @param  mixed  $identifier
     * @return \LaraGram\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByUserId($identifier);

    /**
     * Retrieve a user by their unique identifier.
     *
     * @param  mixed  $identifier
     * @return \LaraGram\Contracts\Auth\StatefulAuthenticatable|null
     */
    public function retrieveById($identifier);

    /**
     * Retrieve a user by their unique identifier and "remember me" token.
     *
     * @param  mixed  $identifier
     * @param  string  $token
     * @return \LaraGram\Contracts\Auth\StatefulAuthenticatable|null
     */
    public function retrieveByToken($identifier, #[\SensitiveParameter] $token);

    /**
     * Update the "remember me" token for the given user in storage.
     *
     * @param  \LaraGram\Contracts\Auth\StatefulAuthenticatable  $user
     * @param  string  $token
     * @return void
     */
    public function updateRememberToken(StatefulAuthenticatable $user, #[\SensitiveParameter] $token);

    /**
     * Retrieve a user by the given credentials.
     *
     * @param  array  $credentials
     * @return \LaraGram\Contracts\Auth\StatefulAuthenticatable|null
     */
    public function retrieveByCredentials(#[\SensitiveParameter] array $credentials);

    /**
     * Validate a user against the given credentials.
     *
     * @param  \LaraGram\Contracts\Auth\StatefulAuthenticatable  $user
     * @param  array  $credentials
     * @return bool
     */
    public function validateCredentials(StatefulAuthenticatable $user, #[\SensitiveParameter] array $credentials);

    /**
     * Rehash the user's password if required and supported.
     *
     * @param  \LaraGram\Contracts\Auth\StatefulAuthenticatable  $user
     * @param  array  $credentials
     * @param  bool  $force
     * @return void
     */
    public function rehashPasswordIfRequired(StatefulAuthenticatable $user, #[\SensitiveParameter] array $credentials, bool $force = false);
}
