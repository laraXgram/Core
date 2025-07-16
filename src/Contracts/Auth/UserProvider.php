<?php

namespace LaraGram\Contracts\Auth;

interface UserProvider
{
    /**
     * Retrieve a user by their unique identifier.
     *
     * @param  mixed  $identifier
     * @return \LaraGram\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByUserId($identifier);
}
