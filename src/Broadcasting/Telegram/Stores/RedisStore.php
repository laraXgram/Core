<?php

namespace LaraGram\Broadcasting\Telegram\Stores;

use LaraGram\Broadcasting\Telegram\ChatCriteria;
use LaraGram\Contracts\Broadcasting\BroadcastStore;
use LaraGram\Contracts\Redis\Factory as Redis;

class RedisStore implements BroadcastStore
{
    /**
     * Create a new Redis store.
     *
     * @param  \LaraGram\Contracts\Redis\Factory  $redis
     * @param  string|null  $connection
     * @param  string  $prefix
     */
    public function __construct(
        protected Redis $redis,
        protected ?string $connection = null,
        protected string $prefix = 'broadcast',
    ) {
        //
    }

    /**
     * {@inheritdoc}
     */
    public function remember(string $bot, int|string $chatId, string $type, array $attributes = [])
    {
        $now = time();
        $current = $this->chat($bot, $chatId);

        $chat = array_merge($current, array_filter([
            'title' => $attributes['title'] ?? null,
            'username' => $attributes['username'] ?? null,
            'first_name' => $attributes['first_name'] ?? null,
            'last_name' => $attributes['last_name'] ?? null,
            'language_code' => $attributes['language_code'] ?? null,
        ], fn ($value) => $value !== null), [
            'chat_id' => (string) $chatId,
            'type' => $type,
            'reachable' => '1',
            'unreachable_reason' => '',
            'last_seen_at' => (string) $now,
            'created_at' => (string) ($current['created_at'] ?? $now),
        ]);

        $this->redis()->hmset($this->key($bot, 'chat', $chatId), $chat);

        $this->redis()->sadd($this->key($bot, 'chats'), $chatId);
        $this->redis()->sadd($this->key($bot, 'reachable'), $chatId);
        $this->redis()->zadd($this->key($bot, 'seen'), $now, $chatId);
        $this->redis()->zadd($this->key($bot, 'joined'), (int) $chat['created_at'], $chatId);

        if (($current['type'] ?? $type) !== $type) {
            $this->redis()->srem($this->key($bot, 'type', $current['type']), $chatId);
        }

        $this->redis()->sadd($this->key($bot, 'type', $type), $chatId);

        if (! empty($current['language_code']) && ($chat['language_code'] ?? null) !== $current['language_code']) {
            $this->redis()->srem($this->key($bot, 'lang', $current['language_code']), $chatId);
        }

        if (! empty($chat['language_code'])) {
            $this->redis()->sadd($this->key($bot, 'lang', $chat['language_code']), $chatId);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function markReachable(string $bot, int|string $chatId)
    {
        if ($this->chat($bot, $chatId) === []) {
            return;
        }

        $this->redis()->hmset($this->key($bot, 'chat', $chatId), ['reachable' => '1', 'unreachable_reason' => '']);
        $this->redis()->sadd($this->key($bot, 'reachable'), $chatId);
    }

    /**
     * {@inheritdoc}
     */
    public function markUnreachable(string $bot, int|string $chatId, ?string $reason = null)
    {
        if ($this->chat($bot, $chatId) === []) {
            return;
        }

        $this->redis()->hmset($this->key($bot, 'chat', $chatId), ['reachable' => '0', 'unreachable_reason' => (string) $reason]);
        $this->redis()->srem($this->key($bot, 'reachable'), $chatId);
    }

    /**
     * {@inheritdoc}
     */
    public function migrate(string $bot, int|string $from, int|string $to)
    {
        $chat = $this->chat($bot, $from);

        if ($chat !== []) {
            $this->remember($bot, $to, 'supergroup', $chat);

            $this->tag($bot, [$to], $this->tags($bot, $from));
        }

        foreach ($this->redis()->hgetall($this->key($bot, 'members', $from)) ?: [] as $userId => $status) {
            $this->rememberMember($bot, $to, $userId, $status);
        }

        foreach ($this->redis()->smembers($this->key($bot, 'targets-of', $from)) ?: [] as $broadcastId) {
            $messages = $this->redis()->hget($this->key(null, 'targets', $broadcastId), (string) $from);

            $this->rememberTarget($broadcastId, $bot, $to, (array) json_decode((string) $messages, true));
        }

        $this->forgetChat($bot, $from, $chat);
    }

    /**
     * {@inheritdoc}
     */
    public function reachable(string $bot, array $types = [], int $chunk = 1000, ?ChatCriteria $criteria = null)
    {
        foreach ($this->matching($bot, $types, $criteria, $chunk) as $chat) {
            yield $this->chatId($chat['chat_id']);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function count(string $bot, array $types = [], ?ChatCriteria $criteria = null)
    {
        return iterator_count($this->matching($bot, $types, $criteria, 1000));
    }

    /**
     * {@inheritdoc}
     */
    public function filter(string $bot, array $chatIds, ChatCriteria $criteria)
    {
        $kept = [];

        foreach (array_chunk($chatIds, 500) as $batch) {
            foreach ($this->chats($bot, $batch) as $chat) {
                if (($chat['reachable'] ?? '1') === '1' && $this->matches($bot, $chat, $criteria)) {
                    $kept[(string) $chat['chat_id']] = true;
                }
            }
        }

        return array_values(array_filter($chatIds, fn ($chatId) => isset($kept[(string) $chatId])));
    }

    /**
     * {@inheritdoc}
     */
    public function find(string $bot, array $chatIds)
    {
        $details = [];

        foreach ($this->chats($bot, $chatIds) as $chat) {
            $details[(string) $chat['chat_id']] = $this->details($bot, $chat);
        }

        return $details;
    }

    /**
     * {@inheritdoc}
     */
    public function touchMember(string $bot, int|string $chatId, int|string $userId)
    {
        $status = $this->redis()->hget($this->key($bot, 'members', $chatId), (string) $userId);

        $this->rememberMember($bot, $chatId, $userId, in_array($status, ['creator', 'administrator', 'restricted'], true) ? $status : 'member');
    }

    /**
     * {@inheritdoc}
     */
    public function rememberMember(string $bot, int|string $chatId, int|string $userId, string $status)
    {
        $this->redis()->hset($this->key($bot, 'members', $chatId), (string) $userId, $status);

        if (in_array($status, ChatCriteria::PRESENT, true)) {
            $this->redis()->sadd($this->key($bot, 'member-of', $userId), $chatId);
            $this->redis()->sadd($this->key($bot, 'present', $chatId), $userId);
        } else {
            $this->redis()->srem($this->key($bot, 'member-of', $userId), $chatId);
            $this->redis()->srem($this->key($bot, 'present', $chatId), $userId);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function members(string $bot, int|string $chatId, ?array $statuses = null, int $chunk = 1000)
    {
        foreach ($this->redis()->hgetall($this->key($bot, 'members', $chatId)) ?: [] as $userId => $status) {
            if (in_array($status, $statuses ?? ChatCriteria::PRESENT, true)) {
                yield $this->chatId($userId);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function tag(string $bot, array $chatIds, array $tags)
    {
        foreach ($chatIds as $chatId) {
            foreach ($tags as $tag) {
                $this->redis()->sadd($this->key($bot, 'tag', $tag), $chatId);
                $this->redis()->sadd($this->key($bot, 'tags-of', $chatId), $tag);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function untag(string $bot, array $chatIds, array $tags)
    {
        foreach ($chatIds as $chatId) {
            foreach ($tags as $tag) {
                $this->redis()->srem($this->key($bot, 'tag', $tag), $chatId);
                $this->redis()->srem($this->key($bot, 'tags-of', $chatId), $tag);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function tags(string $bot, int|string $chatId)
    {
        return array_map('strval', $this->redis()->smembers($this->key($bot, 'tags-of', $chatId)) ?: []);
    }

    /**
     * {@inheritdoc}
     */
    public function rememberTarget(string $broadcastId, string $bot, int|string $chatId, array $messageIds = [])
    {
        $this->redis()->hset(
            $this->key(null, 'targets', $broadcastId),
            (string) $chatId,
            json_encode(array_values(array_map('intval', $messageIds))),
        );

        $this->redis()->sadd($this->key($bot, 'targets-of', $chatId), $broadcastId);
    }

    /**
     * {@inheritdoc}
     */
    public function messagesOf(string $broadcastId, array $chatIds)
    {
        $messages = [];

        foreach ($chatIds as $chatId) {
            $stored = $this->redis()->hget($this->key(null, 'targets', $broadcastId), (string) $chatId);

            if (is_string($stored)) {
                $messages[(string) $chatId] = (array) json_decode($stored, true);
            }
        }

        return $messages;
    }

    /**
     * {@inheritdoc}
     */
    public function recipientsOf(string $broadcastId, int $chunk = 1000)
    {
        foreach ($this->redis()->hkeys($this->key(null, 'targets', $broadcastId)) ?: [] as $chatId) {
            yield $this->chatId($chatId);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function forget(string $broadcastId)
    {
        $this->redis()->del($this->key(null, 'targets', $broadcastId));
    }

    /**
     * Lazily iterate the reachable chats matching the types and criteria.
     *
     * @param  string  $bot
     * @param  array  $types
     * @param  \LaraGram\Broadcasting\Telegram\ChatCriteria|null  $criteria
     * @param  int  $chunk
     * @return \Generator<int, array>
     */
    protected function matching(string $bot, array $types, ?ChatCriteria $criteria, int $chunk): \Generator
    {
        $candidates = $this->candidates($bot, $types, $criteria);

        foreach (array_chunk($candidates, max(1, $chunk)) as $batch) {
            foreach ($this->chats($bot, $batch) as $chat) {
                if (($chat['reachable'] ?? '0') === '1' && $this->matches($bot, $chat, $criteria)) {
                    yield $chat;
                }
            }
        }
    }

    /**
     * Get the chat identifiers the indexes can narrow the criteria down to.
     *
     * @param  string  $bot
     * @param  array  $types
     * @param  \LaraGram\Broadcasting\Telegram\ChatCriteria|null  $criteria
     * @return array<int, string>
     */
    protected function candidates(string $bot, array $types, ?ChatCriteria $criteria): array
    {
        $sets = [$this->key($bot, 'reachable')];
        $ranges = [];

        if ($types !== []) {
            $sets[] = $this->union(array_map(fn ($type) => $this->key($bot, 'type', $type), $types));
        }

        foreach ($criteria?->conditions() ?? [] as $condition) {
            switch ($condition[0]) {
                case 'whereIn':
                    if ($condition[1] === 'language_code' && empty($condition[3])) {
                        $sets[] = $this->union(array_map(fn ($code) => $this->key($bot, 'lang', $code), $condition[2]));
                    } elseif ($condition[1] === 'type' && empty($condition[3])) {
                        $sets[] = $this->union(array_map(fn ($type) => $this->key($bot, 'type', $type), $condition[2]));
                    }
                    break;

                case 'tagged':
                    if ($condition[2] === 'any') {
                        $sets[] = $this->union(array_map(fn ($tag) => $this->key($bot, 'tag', $tag), $condition[1]));
                    } elseif ($condition[2] === 'all') {
                        foreach ($condition[1] as $tag) {
                            $sets[] = $this->key($bot, 'tag', $tag);
                        }
                    }
                    break;

                case 'memberOf':
                    if (empty($condition[3])) {
                        $sets[] = $this->union(array_map(fn ($chatId) => $this->key($bot, 'present', $chatId), $condition[1]));
                    }
                    break;

                case 'hasMember':
                    if (empty($condition[3])) {
                        $sets[] = $this->union(array_map(fn ($userId) => $this->key($bot, 'member-of', $userId), $condition[1]));
                    }
                    break;

                case 'where':
                    if (in_array($condition[1], ['last_seen_at', 'created_at'], true)) {
                        $ranges[] = $condition;
                    }
                    break;
            }
        }

        $ids = array_map('strval', $this->redis()->sinter(...$sets) ?: []);

        foreach ($ranges as [, $column, $operator, $value]) {
            $index = $this->key($bot, $column === 'created_at' ? 'joined' : 'seen');
            $timestamp = strtotime((string) $value) ?: 0;

            $allowed = array_map('strval', match (true) {
                in_array($operator, ['>=', '>'], true) => $this->redis()->zrangebyscore($index, $timestamp, '+inf'),
                in_array($operator, ['<=', '<'], true) => $this->redis()->zrangebyscore($index, '-inf', $timestamp),
                default => $ids,
            } ?: []);

            $ids = array_values(array_intersect($ids, $allowed));
        }

        sort($ids, SORT_NATURAL);

        return $ids;
    }

    /**
     * Determine if a chat matches the conditions the indexes could not answer.
     *
     * @param  string  $bot
     * @param  array  $chat
     * @param  \LaraGram\Broadcasting\Telegram\ChatCriteria|null  $criteria
     * @return bool
     */
    protected function matches(string $bot, array $chat, ?ChatCriteria $criteria): bool
    {
        foreach ($criteria?->conditions() ?? [] as $condition) {
            $matches = match ($condition[0]) {
                'where' => $this->compare($chat[$condition[1]] ?? null, $condition[2], $condition[3]),
                'whereIn' => in_array((string) ($chat[$condition[1]] ?? ''), array_map('strval', $condition[2]), true) !== (bool) ($condition[3] ?? false),
                'whereNull' => (($chat[$condition[1]] ?? '') === '') !== (bool) ($condition[2] ?? false),
                'tagged' => $this->matchesTags($bot, $chat['chat_id'], $condition[1], $condition[2]),
                'memberOf' => $this->matchesMembership($bot, $condition[1], $chat['chat_id'], $condition[2]) !== (bool) ($condition[3] ?? false),
                'hasMember' => $this->matchesMembership($bot, [$chat['chat_id']], null, $condition[2], $condition[1]) !== (bool) ($condition[3] ?? false),
                default => true,
            };

            if (! $matches) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine if a chat carries the tags.
     *
     * @param  string  $bot
     * @param  int|string  $chatId
     * @param  array  $tags
     * @param  string  $mode
     * @return bool
     */
    protected function matchesTags(string $bot, int|string $chatId, array $tags, string $mode): bool
    {
        $current = $this->tags($bot, $chatId);

        $matching = array_intersect($tags, $current);

        return match ($mode) {
            'all' => count($matching) === count($tags),
            'none' => $matching === [],
            default => $matching !== [],
        };
    }

    /**
     * Determine if any of the given chats has one of the users as a present member.
     *
     * @param  string  $bot
     * @param  array  $chatIds
     * @param  int|string|null  $userId
     * @param  array  $statuses
     * @param  array|null  $userIds
     * @return bool
     */
    protected function matchesMembership(string $bot, array $chatIds, int|string|null $userId, array $statuses, ?array $userIds = null): bool
    {
        foreach ($chatIds as $chatId) {
            foreach ($userIds ?? [$userId] as $user) {
                $status = $this->redis()->hget($this->key($bot, 'members', $chatId), (string) $user);

                if (is_string($status) && in_array($status, $statuses, true)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Compare a stored value with a condition.
     *
     * @param  mixed  $value
     * @param  string  $operator
     * @param  mixed  $expected
     * @return bool
     */
    protected function compare(mixed $value, string $operator, mixed $expected): bool
    {
        if (in_array($operator, ['like', 'not like'], true)) {
            $pattern = '/^'.str_replace(['%', '_'], ['.*', '.'], preg_quote((string) $expected, '/')).'$/i';

            return preg_match($pattern, (string) $value) === ($operator === 'like' ? 1 : 0);
        }

        if (in_array($operator, ['<', '<=', '>', '>='], true) && ! (is_numeric($value) && is_numeric($expected))) {
            // Dates are stored as timestamps, while the criteria carry "Y-m-d H:i:s".
            $value = is_numeric($value) ? (int) $value : (strtotime((string) $value) ?: 0);
            $expected = is_numeric($expected) ? (int) $expected : (strtotime((string) $expected) ?: 0);
        }

        return match ($operator) {
            '=' => (string) $value === (string) $expected,
            '!=', '<>' => (string) $value !== (string) $expected,
            '<' => $value < $expected,
            '<=' => $value <= $expected,
            '>' => $value > $expected,
            '>=' => $value >= $expected,
            default => true,
        };
    }

    /**
     * Get the stored details of a chat, in the shape the broadcast expects.
     *
     * @param  string  $bot
     * @param  array  $chat
     * @return array
     */
    protected function details(string $bot, array $chat): array
    {
        return [
            'chat_id' => $this->chatId($chat['chat_id']),
            'type' => $chat['type'] ?? 'private',
            'title' => $chat['title'] ?? null,
            'username' => $chat['username'] ?? null,
            'first_name' => $chat['first_name'] ?? null,
            'last_name' => $chat['last_name'] ?? null,
            'language_code' => $chat['language_code'] ?? null,
            'reachable' => ($chat['reachable'] ?? '1') === '1',
            'tags' => $this->tags($bot, $chat['chat_id']),
            'last_seen_at' => $chat['last_seen_at'] ?? null,
            'created_at' => $chat['created_at'] ?? null,
        ];
    }

    /**
     * Read the stored hash of a chat.
     *
     * @param  string  $bot
     * @param  int|string  $chatId
     * @return array
     */
    protected function chat(string $bot, int|string $chatId): array
    {
        return (array) ($this->redis()->hgetall($this->key($bot, 'chat', $chatId)) ?: []);
    }

    /**
     * Read the stored hashes of several chats.
     *
     * @param  string  $bot
     * @param  array  $chatIds
     * @return \Generator<int, array>
     */
    protected function chats(string $bot, array $chatIds): \Generator
    {
        foreach ($chatIds as $chatId) {
            $chat = $this->chat($bot, $chatId);

            if ($chat !== []) {
                yield $chat;
            }
        }
    }

    /**
     * Remove a chat and its indexes.
     *
     * @param  string  $bot
     * @param  int|string  $chatId
     * @param  array  $chat
     * @return void
     */
    protected function forgetChat(string $bot, int|string $chatId, array $chat): void
    {
        foreach ($this->tags($bot, $chatId) as $tag) {
            $this->redis()->srem($this->key($bot, 'tag', $tag), $chatId);
        }

        foreach (['chat', 'members', 'present', 'tags-of', 'targets-of'] as $key) {
            $this->redis()->del($this->key($bot, $key, $chatId));
        }

        $this->redis()->srem($this->key($bot, 'chats'), $chatId);
        $this->redis()->srem($this->key($bot, 'reachable'), $chatId);
        $this->redis()->zrem($this->key($bot, 'seen'), $chatId);
        $this->redis()->zrem($this->key($bot, 'joined'), $chatId);

        if (! empty($chat['type'])) {
            $this->redis()->srem($this->key($bot, 'type', $chat['type']), $chatId);
        }

        if (! empty($chat['language_code'])) {
            $this->redis()->srem($this->key($bot, 'lang', $chat['language_code']), $chatId);
        }
    }

    /**
     * Store the union of the given sets in a temporary key and return it.
     *
     * @param  array<int, string>  $keys
     * @return string
     */
    protected function union(array $keys): string
    {
        if (count($keys) === 1) {
            return $keys[0];
        }

        $destination = $this->key(null, 'union', bin2hex(random_bytes(8)));

        $this->redis()->sunionstore($destination, ...$keys);
        $this->redis()->expire($destination, 60);

        return $destination;
    }

    /**
     * Get a Redis key.
     *
     * @param  string|null  $bot
     * @param  string  $name
     * @param  int|string|null  $id
     * @return string
     */
    protected function key(?string $bot, string $name, int|string|null $id = null): string
    {
        return implode(':', array_filter([$this->prefix, $bot, $name, $id], fn ($part) => $part !== null && $part !== ''));
    }

    /**
     * Normalize a stored chat identifier.
     *
     * @param  mixed  $chatId
     * @return int|string
     */
    protected function chatId(mixed $chatId): int|string
    {
        return is_numeric($chatId) ? (int) $chatId : (string) $chatId;
    }

    /**
     * Get the Redis connection the store runs on.
     *
     * @return \LaraGram\Redis\Connections\Connection
     */
    protected function redis()
    {
        return $this->redis->connection($this->connection);
    }
}
