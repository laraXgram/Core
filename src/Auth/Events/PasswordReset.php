<?php

namespace LaraGram\Auth\Events;

use LaraGram\Queue\SerializesModels;

class PasswordReset
{
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  \LaraGram\Contracts\Auth\StatefulAuthenticatable  $user  The user.
     */
    public function __construct(
        public $user,
    ) {
    }
}
