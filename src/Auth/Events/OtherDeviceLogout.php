<?php

namespace LaraGram\Auth\Events;

use LaraGram\Queue\SerializesModels;

class OtherDeviceLogout
{
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  string  $guard  The authentication guard name.
     * @param  \LaraGram\Contracts\Auth\StatefulAuthenticatable  $user  \LaraGram\Contracts\Auth\StatefulAuthenticatable
     */
    public function __construct(
        public $guard,
        public $user,
    ) {
    }
}
