<?php

namespace LaraGram\Contracts\Support;

interface MessageProvider
{
    /**
     * Get the messages for the instance.
     *
     * @return \LaraGram\Contracts\Support\MessageBag
     */
    public function getMessageBag();
}
