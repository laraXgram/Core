<?php

namespace LaraGram\Contracts\Auth;

interface PasswordBrokerFactory
{
    /**
     * Get a password broker instance by name.
     *
     * @param  string|null  $name
     * @return \LaraGram\Contracts\Auth\PasswordBroker
     */
    public function broker($name = null);
}
