<?php

namespace LaraGram\Broadcasting\Telegram;

use LaraGram\Broadcasting\Telegram\Concerns\BroadcastsBotApiMethods;
use LaraGram\Template\Template;

class NextStep
{
    use BroadcastsBotApiMethods;

    /**
     * Create a new step.
     *
     * @param  \LaraGram\Broadcasting\Telegram\TelegramBroadcast  $broadcast
     */
    public function __construct(protected TelegramBroadcast $broadcast)
    {
        //
    }

    /**
     * Render a template for every recipient and deliver the Bot API calls it makes.
     *
     * @throws \LaraGram\Broadcasting\BroadcastException
     */
    public function template(Template|string $template, array $data = [], bool $perRecipient = true): TelegramBroadcast
    {
        return Action::template($template, $data, $perRecipient)->appendTo($this->broadcast);
    }

    /**
     * Deliver a Bot API method Laraquest knows but the generated methods do not have yet.
     *
     * @param  string  $method
     * @param  array  $arguments
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     *
     * @throws \BadMethodCallException
     */
    public function __call($method, $arguments)
    {
        return Action::fromCall(static::class, $method, $arguments)->appendTo($this->broadcast);
    }

    /**
     * Add a Bot API call (used by the generated methods).
     *
     * @param  string  $method
     * @param  array  $parameters
     * @param  string  $recipient
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    protected function endpoint(string $method, array $parameters, string $recipient): TelegramBroadcast
    {
        return Action::fromEndpoint($method, $parameters, $recipient)->appendTo($this->broadcast);
    }
}
