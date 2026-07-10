<?php

namespace LaraGram\Auth\Events;

use LaraGram\Queue\SerializesModels;

class Registered
{
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  \LaraGram\Contracts\Auth\StatefulAuthenticatable  $user  The authenticated user.
     */
    public function __construct(
        public $user,
    ) {
    }
}
