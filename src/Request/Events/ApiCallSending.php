<?php

namespace LaraGram\Request\Events;

class ApiCallSending
{
    /**
     * Create a new event instance.
     *
     * @param  string  $method  The Bot API method being called.
     * @param  array<string, mixed>  $parameters  The parameters the method is called with.
     * @param  string|null  $connection  The bot connection the call is sent through.
     * @param  bool  $intercepted  Whether the call is handed to an interceptor instead of Telegram.
     * @return void
     */
    public function __construct(
        public string $method,
        public array $parameters,
        public ?string $connection = null,
        public bool $intercepted = false,
    ) {
    }
}
