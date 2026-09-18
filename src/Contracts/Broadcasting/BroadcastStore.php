<?php

namespace LaraGram\Contracts\Broadcasting;

use LaraGram\Broadcasting\Telegram\ChatCriteria;

interface BroadcastStore
{
    /**
     * Store or refresh a chat the bot can reach.
     *
     * @param  string  $bot
     * @param  int|string  $chatId
     * @param  string  $type  private | group | supergroup | channel
     * @param  array  $attributes  title, username, first_name, last_name, language_code
     * @return void
     */
    public function remember(string $bot, int|string $chatId, string $type, array $attributes = []);

    /**
     * Mark a chat as reachable again (the bot was unblocked or re-added).
     *
     * @param  string  $bot
     * @param  int|string  $chatId
     * @return void
     */
    public function markReachable(string $bot, int|string $chatId);

    /**
     * Mark a chat as unreachable so future broadcasts skip it.
     *
     * @param  string  $bot
     * @param  int|string  $chatId
     * @param  string|null  $reason
     * @return void
     */
    public function markUnreachable(string $bot, int|string $chatId, ?string $reason = null);

    /**
     * Move a chat, its members and its tags to a new identifier.
     *
     * @param  string  $bot
     * @param  int|string  $from
     * @param  int|string  $to
     * @return void
     */
    public function migrate(string $bot, int|string $from, int|string $to);

    /**
     * Lazily iterate the reachable chat identifiers of the given types matching the criteria.
     *
     * @param  string  $bot
     * @param  array<int, string>  $types  An empty array means every type.
     * @param  int  $chunk
     * @param  \LaraGram\Broadcasting\Telegram\ChatCriteria|null  $criteria
     * @return iterable<int, int|string>
     */
    public function reachable(string $bot, array $types = [], int $chunk = 1000, ?ChatCriteria $criteria = null);

    /**
     * Count the reachable chats of the given types matching the criteria.
     *
     * @param  string  $bot
     * @param  array<int, string>  $types  An empty array means every type.
     * @param  \LaraGram\Broadcasting\Telegram\ChatCriteria|null  $criteria
     * @return int
     */
    public function count(string $bot, array $types = [], ?ChatCriteria $criteria = null);

    /**
     * Keep the given chat identifiers that are recorded, reachable and match the criteria.
     *
     * @param  string  $bot
     * @param  array<int, int|string>  $chatIds
     * @param  \LaraGram\Broadcasting\Telegram\ChatCriteria  $criteria
     * @return array<int, int|string>
     */
    public function filter(string $bot, array $chatIds, ChatCriteria $criteria);

    /**
     * Get the stored details of the given chats, keyed by chat identifier.
     *
     * @param  string  $bot
     * @param  array<int, int|string>  $chatIds
     * @return array<string, array>
     */
    public function find(string $bot, array $chatIds);

    /**
     * Record that a user is (still) present in a chat, without downgrading an admin.
     *
     * @param  string  $bot
     * @param  int|string  $chatId
     * @param  int|string  $userId
     * @return void
     */
    public function touchMember(string $bot, int|string $chatId, int|string $userId);

    /**
     * Record a user's membership status in a chat.
     *
     * @param  string  $bot
     * @param  int|string  $chatId
     * @param  int|string  $userId
     * @param  string  $status  creator | administrator | member | restricted | left | kicked
     * @return void
     */
    public function rememberMember(string $bot, int|string $chatId, int|string $userId, string $status);

    /**
     * Lazily iterate the recorded members of a chat.
     *
     * @param  string  $bot
     * @param  int|string  $chatId
     * @param  array<int, string>|null  $statuses
     * @param  int  $chunk
     * @return iterable<int, int|string>
     */
    public function members(string $bot, int|string $chatId, ?array $statuses = null, int $chunk = 1000);

    /**
     * Add tags to chats.
     *
     * @param  string  $bot
     * @param  array<int, int|string>  $chatIds
     * @param  array<int, string>  $tags
     * @return void
     */
    public function tag(string $bot, array $chatIds, array $tags);

    /**
     * Remove tags from chats.
     *
     * @param  string  $bot
     * @param  array<int, int|string>  $chatIds
     * @param  array<int, string>  $tags
     * @return void
     */
    public function untag(string $bot, array $chatIds, array $tags);

    /**
     * Get the tags of a chat.
     *
     * @param  string  $bot
     * @param  int|string  $chatId
     * @return array<int, string>
     */
    public function tags(string $bot, int|string $chatId);

    /**
     * Record the chat a recallable broadcast reached, and the messages it sent there.
     *
     * @param  string  $broadcastId
     * @param  string  $bot
     * @param  int|string  $chatId
     * @param  array<int, int>  $messageIds
     * @return void
     */
    public function rememberTarget(string $broadcastId, string $bot, int|string $chatId, array $messageIds = []);

    /**
     * Get the messages a broadcast sent to the given chats, keyed by chat identifier.
     *
     * @param  string  $broadcastId
     * @param  array<int, int|string>  $chatIds
     * @return array<string, array<int, int>>
     */
    public function messagesOf(string $broadcastId, array $chatIds);

    /**
     * Lazily iterate the chats a broadcast reached.
     *
     * @param  string  $broadcastId
     * @param  int  $chunk
     * @return iterable<int, int|string>
     */
    public function recipientsOf(string $broadcastId, int $chunk = 1000);

    /**
     * Forget the chats and messages recorded for a broadcast.
     *
     * @param  string  $broadcastId
     * @return void
     */
    public function forget(string $broadcastId);
}
