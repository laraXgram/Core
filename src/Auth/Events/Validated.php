<?php

namespace LaraGram\Auth\Events;

use LaraGram\Queue\SerializesModels;

class Validated
{
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  string  $guard  The authentication guard name.
     * @param  \LaraGram\Contracts\Auth\StatefulAuthenticatable  $user  The user retrieved and validated from the User Provider.
     */
    public function __construct(
        public $guard,
        public $user,
    ) {
    }
}
