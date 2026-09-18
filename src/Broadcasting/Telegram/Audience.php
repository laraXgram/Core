<?php

namespace LaraGram\Broadcasting\Telegram;

use LaraGram\Broadcasting\Channel;

class Audience extends Channel
{
    /**
     * The chat types covered by each built-in audience.
     *
     * An empty array covers every chat type.
     *
     * @var array<string, array<int, string>>
     */
    public const BUILT_IN = [
        'users' => ['private'],
        'private' => ['private'],
        'groups' => ['group', 'supergroup'],
        'supergroups' => ['supergroup'],
        'channels' => ['channel'],
        'chats' => [],
        'all' => [],
    ];

    /**
     * Create an audience of every private chat (the bot's users).
     *
     * @return static
     */
    public static function users()
    {
        return new static('users');
    }

    /**
     * Create an audience of every group and supergroup.
     *
     * @return static
     */
    public static function groups()
    {
        return new static('groups');
    }

    /**
     * Create an audience of every supergroup.
     *
     * @return static
     */
    public static function supergroups()
    {
        return new static('supergroups');
    }

    /**
     * Create an audience of every channel.
     *
     * @return static
     */
    public static function channels()
    {
        return new static('channels');
    }

    /**
     * Create an audience of every known chat.
     *
     * @return static
     */
    public static function chats()
    {
        return new static('chats');
    }

    /**
     * Create an audience registered with Broadcast::audience().
     *
     * @param  string  $name
     * @return static
     */
    public static function named(string $name)
    {
        return new static($name);
    }

    /**
     * Create an audience of a single chat.
     *
     * @param  int|string  $chatId
     * @return static
     */
    public static function chat(int|string $chatId)
    {
        return new static('chat.'.$chatId);
    }

    /**
     * Create an audience of the users recorded as members of a group or channel.
     *
     * @param  int|string  $chatId
     * @return static
     */
    public static function members(int|string $chatId)
    {
        return new static('members.'.$chatId);
    }

    /**
     * Create an audience of the chats a remembered broadcast was sent to.
     *
     * @param  string  $broadcastId
     * @return static
     */
    public static function sent(string $broadcastId)
    {
        return new static('sent.'.$broadcastId);
    }

    /**
     * Extract the chat of a "members.{chatId}" audience name.
     *
     * @param  string  $name
     * @return int|string|null
     */
    public static function membersOf(string $name): int|string|null
    {
        return str_starts_with($name, 'members.') ? static::chatId(substr($name, 8)) : null;
    }

    /**
     * Extract the broadcast of a "sent.{id}" audience name.
     *
     * @param  string  $name
     * @return string|null
     */
    public static function sentBy(string $name): ?string
    {
        return str_starts_with($name, 'sent.') && strlen($name) > 5 ? substr($name, 5) : null;
    }

    /**
     * Determine if the given name is a single chat (an id, "@username" or "chat.{id}").
     *
     * @param  string  $name
     * @return bool
     */
    public static function isChat(string $name): bool
    {
        return static::chatId($name) !== null;
    }

    /**
     * Extract the chat identifier from a single-chat audience name.
     *
     * @param  string  $name
     * @return int|string|null
     */
    public static function chatId(string $name): int|string|null
    {
        if (str_starts_with($name, 'chat.')) {
            $name = substr($name, 5);
        }

        if (preg_match('/^-?\d+$/', $name)) {
            return (int) $name;
        }

        if (preg_match('/^@\w{4,}$/', $name)) {
            return $name;
        }

        return null;
    }
}
