<?php

namespace LaraGram\Contracts\Broadcasting;

interface ShouldBroadcast
{
    /**
     * Get the channels the event should broadcast on.
     *
     * @return \LaraGram\Broadcasting\Channel|\LaraGram\Broadcasting\Channel[]|string[]|string
     */
    public function broadcastOn();
}
