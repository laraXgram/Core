<?php

namespace LaraGram\Broadcasting\Telegram\Events;

use LaraGram\Broadcasting\Telegram\Progress;

class BroadcastCompleted
{
    /**
     * Create a new event instance.
     *
     * @param  string  $id  The broadcast identifier.
     * @param  \LaraGram\Broadcasting\Telegram\Progress  $progress  The final counters.
     */
    public function __construct(
        public string $id,
        public Progress $progress,
    ) {
        //
    }
}
