<?php

namespace LaraGram\Broadcasting\Telegram;

class SentBroadcast extends Recipients
{
    /**
     * Create the recipients of a sent broadcast.
     *
     * @param  string  $broadcastId  The identifier of the broadcast that sent the messages.
     * @param  string|null  $broadcaster
     */
    public function __construct(string $broadcastId, ?string $broadcaster = 'telegram')
    {
        parent::__construct(Audience::sent($broadcastId), $broadcaster);

        $this->options['messages_of'] = $broadcastId;
    }

    /**
     * Choose which sent message the calls use when the broadcast sent several (0 is the first).
     */
    public function messageIndex(int $index): static
    {
        $this->options['message_index'] = max(0, $index);

        return $this;
    }

    /**
     * Delete every message the broadcast sent.
     */
    public function delete(): TelegramBroadcast
    {
        return $this->endpoint('deleteMessages', [], 'chat_id');
    }

    /**
     * Pin the sent message.
     */
    public function pin(bool $silent = true): TelegramBroadcast
    {
        return $this->endpoint('pinChatMessage', ['disable_notification' => $silent], 'chat_id');
    }
}
