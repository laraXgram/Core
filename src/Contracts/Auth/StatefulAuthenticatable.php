<?php

namespace LaraGram\Contracts\Auth;

interface StatefulAuthenticatable extends Authenticatable
{
    /**
     * Get the name of the password attribute for the user.
     *
     * @return string
     */
    public function getAuthPasswordName();

    /**
     * Get the password for the user.
     *
     * @return string
     */
    public function getAuthPassword();

    /**
     * Get the token value for the "remember me" session.
     *
     * @return string
     */
    public function getRememberToken();

    /**
     * Set the token value for the "remember me" session.
     *
     * @param  string  $value
     * @return void
     */
    public function setRememberToken($value);

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function getRememberTokenName();
}
