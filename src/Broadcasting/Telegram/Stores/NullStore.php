<?php

namespace LaraGram\Broadcasting\Telegram\Stores;

use LaraGram\Broadcasting\Telegram\ChatCriteria;
use LaraGram\Contracts\Broadcasting\BroadcastStore;

class NullStore implements BroadcastStore
{
    /**
     * {@inheritdoc}
     */
    public function remember(string $bot, int|string $chatId, string $type, array $attributes = [])
    {
        //
    }

    /**
     * {@inheritdoc}
     */
    public function markReachable(string $bot, int|string $chatId)
    {
        //
    }

    /**
     * {@inheritdoc}
     */
    public function markUnreachable(string $bot, int|string $chatId, ?string $reason = null)
    {
        //
    }

    /**
     * {@inheritdoc}
     */
    public function migrate(string $bot, int|string $from, int|string $to)
    {
        //
    }

    /**
     * {@inheritdoc}
     */
    public function reachable(string $bot, array $types = [], int $chunk = 1000, ?ChatCriteria $criteria = null)
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function count(string $bot, array $types = [], ?ChatCriteria $criteria = null)
    {
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function filter(string $bot, array $chatIds, ChatCriteria $criteria)
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function find(string $bot, array $chatIds)
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function touchMember(string $bot, int|string $chatId, int|string $userId)
    {
        //
    }

    /**
     * {@inheritdoc}
     */
    public function rememberMember(string $bot, int|string $chatId, int|string $userId, string $status)
    {
        //
    }

    /**
     * {@inheritdoc}
     */
    public function members(string $bot, int|string $chatId, ?array $statuses = null, int $chunk = 1000)
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function tag(string $bot, array $chatIds, array $tags)
    {
        //
    }

    /**
     * {@inheritdoc}
     */
    public function untag(string $bot, array $chatIds, array $tags)
    {
        //
    }

    /**
     * {@inheritdoc}
     */
    public function tags(string $bot, int|string $chatId)
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function rememberTarget(string $broadcastId, string $bot, int|string $chatId, array $messageIds = [])
    {
        //
    }

    /**
     * {@inheritdoc}
     */
    public function messagesOf(string $broadcastId, array $chatIds)
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function recipientsOf(string $broadcastId, int $chunk = 1000)
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function forget(string $broadcastId)
    {
        //
    }
}
