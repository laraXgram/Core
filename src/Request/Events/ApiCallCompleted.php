<?php

namespace LaraGram\Request\Events;

use Throwable;

class ApiCallCompleted
{
    /**
     * Create a new event instance.
     *
     * @param  string  $method  The Bot API method that was called.
     * @param  array<string, mixed>  $parameters  The parameters the method was called with.
     * @param  string|null  $connection  The bot connection the call was sent through.
     * @param  mixed  $response  The response Telegram (or the interceptor) returned, if any.
     * @param  float  $duration  How long the call took, in milliseconds.
     * @param  \Throwable|null  $exception  The exception the call threw, if any.
     * @param  bool  $intercepted  Whether the call was handed to an interceptor instead of Telegram.
     * @return void
     */
    public function __construct(
        public string $method,
        public array $parameters,
        public ?string $connection,
        public mixed $response,
        public float $duration,
        public ?Throwable $exception = null,
        public bool $intercepted = false,
    ) {
    }

    /**
     * Determine if Telegram accepted the call.
     *
     * @return bool
     */
    public function successful(): bool
    {
        if ($this->exception !== null) {
            return false;
        }

        $ok = is_object($this->response) && method_exists($this->response, 'offsetGet')
            ? ($this->response['ok'] ?? true)
            : (is_array($this->response) ? ($this->response['ok'] ?? true) : true);

        return $ok !== false;
    }
}
