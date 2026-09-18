<?php

namespace LaraGram\Broadcasting\Telegram;

use JsonSerializable;
use LaraGram\Contracts\Support\Arrayable;

class Recipient implements Arrayable, JsonSerializable
{
    /**
     * Create a new recipient.
     *
     * @param  int|string  $chat_id
     * @param  string  $type  private | group | supergroup | channel
     * @param  string|null  $title
     * @param  string|null  $username
     * @param  string|null  $first_name
     * @param  string|null  $last_name
     * @param  string|null  $language_code
     * @param  bool  $known  Whether the chat is recorded in the chat repository.
     */
    public function __construct(
        public int|string $chat_id,
        public string $type = 'private',
        public ?string $title = null,
        public ?string $username = null,
        public ?string $first_name = null,
        public ?string $last_name = null,
        public ?string $language_code = null,
        public bool $known = false,
    ) {
        //
    }

    /**
     * Create a recipient from stored chat details, or from the identifier alone.
     *
     * @param  int|string  $chatId
     * @param  array|null  $details
     * @return static
     */
    public static function make(int|string $chatId, ?array $details = null)
    {
        if ($details === null) {
            return new static($chatId, is_int($chatId) && $chatId > 0 ? 'private' : 'group');
        }

        return new static(
            $chatId,
            (string) ($details['type'] ?? 'private'),
            $details['title'] ?? null,
            $details['username'] ?? null,
            $details['first_name'] ?? null,
            $details['last_name'] ?? null,
            $details['language_code'] ?? null,
            true,
        );
    }

    /**
     * Get the display name: the full name of a user, or the title of a chat.
     *
     * @return string
     */
    public function name(): string
    {
        $name = trim(($this->first_name ?? '').' '.($this->last_name ?? ''));

        return $name !== '' ? $name : ($this->title ?? $this->username ?? (string) $this->chat_id);
    }

    /**
     * Determine if the recipient is a private chat.
     *
     * @return bool
     */
    public function isPrivate(): bool
    {
        return $this->type === 'private';
    }

    /**
     * Get the array form of the recipient.
     *
     * @return array
     */
    public function toArray(): array
    {
        return get_object_vars($this) + ['name' => $this->name()];
    }

    /**
     * Get the JSON serializable form of the recipient.
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
