<?php

namespace LaraGram\Contracts\Auth;

interface Guard
{
    /**
     * Determine if the current user is authenticated.
     *
     * @return bool
     */
    public function check();

    /**
     * Get the currently authenticated user.
     *
     * @return \LaraGram\Contracts\Auth\Authenticatable|null
     */
    public function user();

    /**
     * Get the ID for the currently authenticated user.
     *
     * @return int|string|null
     */
    public function id();

    /**
     * Validate a user's credentials.
     *
     * @param  array  $credentials
     * @return bool
     */
    public function validate(array $credentials = []);

    /**
     * Determine if the guard has a user instance.
     *
     * @return bool
     */
    public function hasUser();

    /**
     * Set the current user.
     *
     * @param  \LaraGram\Contracts\Auth\Authenticatable  $user
     * @return $this
     */
    public function setUser(Authenticatable $user);
}
