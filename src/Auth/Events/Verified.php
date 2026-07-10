<?php

namespace LaraGram\Auth\Events;

use LaraGram\Queue\SerializesModels;

class Verified
{
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  \LaraGram\Contracts\Auth\MustVerifyEmail  $user  The verified user.
     */
    public function __construct(
        public $user,
    ) {
    }
}
