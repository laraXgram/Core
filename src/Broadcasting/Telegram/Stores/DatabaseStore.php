<?php

namespace LaraGram\Broadcasting\Telegram\Stores;

use LaraGram\Broadcasting\Telegram\ChatCriteria;
use LaraGram\Contracts\Broadcasting\BroadcastStore;
use LaraGram\Database\ConnectionResolverInterface;
use LaraGram\Support\Facades\Date;

class DatabaseStore implements BroadcastStore
{
    /**
     * The columns returned by find().
     *
     * @var array<int, string>
     */
    protected const DETAILS = ['chat_id', 'type', 'title', 'username', 'first_name', 'last_name', 'language_code', 'reachable', 'tags', 'last_seen_at', 'created_at'];

    /**
     * The character surrounding every tag in the tags column.
     *
     * @var string
     */
    protected const TAG_DELIMITER = '|';

    /**
     * Create a new database store.
     *
     * @param  \LaraGram\Database\ConnectionResolverInterface  $resolver
     * @param  string|null  $connection
     * @param  array{chats?: string, members?: string, targets?: string}  $tables
     */
    public function __construct(
        protected ConnectionResolverInterface $resolver,
        protected ?string $connection = null,
        protected array $tables = [],
    ) {
        $this->tables = array_merge([
            'chats' => 'broadcast_chats',
            'members' => 'broadcast_members',
            'targets' => 'broadcast_targets',
        ], array_filter($tables));
    }

    /**
     * {@inheritdoc}
     */
    public function remember(string $bot, int|string $chatId, string $type, array $attributes = [])
    {
        $now = Date::now();

        $values = [
            'bot' => $bot,
            'chat_id' => $chatId,
            'type' => $type,
            'title' => $this->limit($attributes['title'] ?? null),
            'username' => $this->limit($attributes['username'] ?? null),
            'first_name' => $this->limit($attributes['first_name'] ?? null),
            'last_name' => $this->limit($attributes['last_name'] ?? null),
            'language_code' => $attributes['language_code'] ?? null,
            'reachable' => true,
            'unreachable_reason' => null,
            'tags' => null,
            'last_seen_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $update = ['type', 'reachable', 'unreachable_reason', 'last_seen_at', 'updated_at'];

        // Only overwrite the details the update actually carried.
        foreach (['title', 'username', 'first_name', 'last_name', 'language_code'] as $column) {
            if (array_key_exists($column, $attributes)) {
                $update[] = $column;
            }
        }

        $this->table('chats')->upsert([$values], ['bot', 'chat_id'], $update);
    }

    /**
     * {@inheritdoc}
     */
    public function markReachable(string $bot, int|string $chatId)
    {
        $this->chat($bot, $chatId)->update([
            'reachable' => true,
            'unreachable_reason' => null,
            'updated_at' => Date::now(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function markUnreachable(string $bot, int|string $chatId, ?string $reason = null)
    {
        $this->chat($bot, $chatId)->update([
            'reachable' => false,
            'unreachable_reason' => $this->limit($reason),
            'updated_at' => Date::now(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function migrate(string $bot, int|string $from, int|string $to)
    {
        $this->connection()->transaction(function () use ($bot, $from, $to) {
            if ($this->chat($bot, $to)->exists()) {
                $this->chat($bot, $from)->delete();
            } else {
                $this->chat($bot, $from)->update(['chat_id' => $to, 'type' => 'supergroup', 'updated_at' => Date::now()]);
            }

            $existing = $this->table('members')->where('bot', $bot)->where('chat_id', $to)->pluck('user_id')->all();

            $this->table('members')->where('bot', $bot)->where('chat_id', $from)
                ->when($existing !== [], fn ($query) => $query->whereNotIn('user_id', $existing))
                ->update(['chat_id' => $to]);

            $this->table('members')->where('bot', $bot)->where('chat_id', $from)->delete();

            $this->table('targets')->where('bot', $bot)->where('chat_id', $from)->update(['chat_id' => $to]);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function reachable(string $bot, array $types = [], int $chunk = 1000, ?ChatCriteria $criteria = null)
    {
        foreach ($this->reachableQuery($bot, $types, $criteria)->select(['id', 'chat_id'])->lazyById($chunk, 'id') as $row) {
            yield $this->chatId($row->chat_id);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function count(string $bot, array $types = [], ?ChatCriteria $criteria = null)
    {
        return $this->reachableQuery($bot, $types, $criteria)->count();
    }

    /**
     * {@inheritdoc}
     */
    public function filter(string $bot, array $chatIds, ChatCriteria $criteria)
    {
        if ($chatIds === []) {
            return [];
        }

        $matching = array_fill_keys(array_map('strval', $this->reachableQuery($bot, [], $criteria)
            ->whereIn('chat_id', array_values(array_filter($chatIds, 'is_numeric')))
            ->pluck('chat_id')
            ->all()), true);

        return array_values(array_filter($chatIds, fn ($chatId) => isset($matching[(string) $chatId])));
    }

    /**
     * {@inheritdoc}
     */
    public function find(string $bot, array $chatIds)
    {
        $numeric = array_values(array_filter($chatIds, 'is_numeric'));

        if ($numeric === []) {
            return [];
        }

        $details = [];

        foreach ($this->table('chats')->where('bot', $bot)->whereIn('chat_id', $numeric)->get(self::DETAILS) as $row) {
            $row = (array) $row;
            $row['chat_id'] = $this->chatId($row['chat_id']);
            $row['reachable'] = (bool) $row['reachable'];
            $row['tags'] = $this->explode($row['tags'] ?? null);

            $details[(string) $row['chat_id']] = $row;
        }

        return $details;
    }

    /**
     * {@inheritdoc}
     */
    public function touchMember(string $bot, int|string $chatId, int|string $userId)
    {
        $now = Date::now();

        $updated = $this->member($bot, $chatId, $userId)->update(['last_seen_at' => $now, 'updated_at' => $now]);

        if ($updated === 0) {
            $this->rememberMember($bot, $chatId, $userId, 'member');

            return;
        }

        $this->member($bot, $chatId, $userId)
            ->whereIn('status', ['left', 'kicked'])
            ->update(['status' => 'member']);
    }

    /**
     * {@inheritdoc}
     */
    public function rememberMember(string $bot, int|string $chatId, int|string $userId, string $status)
    {
        $now = Date::now();

        $this->table('members')->upsert([[
            'bot' => $bot,
            'chat_id' => $chatId,
            'user_id' => $userId,
            'status' => $status,
            'last_seen_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]], ['bot', 'chat_id', 'user_id'], ['status', 'last_seen_at', 'updated_at']);
    }

    /**
     * {@inheritdoc}
     */
    public function members(string $bot, int|string $chatId, ?array $statuses = null, int $chunk = 1000)
    {
        $query = $this->table('members')
            ->select(['id', 'user_id'])
            ->where('bot', $bot)
            ->where('chat_id', $chatId)
            ->whereIn('status', $statuses ?? ChatCriteria::PRESENT);

        foreach ($query->lazyById($chunk, 'id') as $row) {
            yield $this->chatId($row->user_id);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function tag(string $bot, array $chatIds, array $tags)
    {
        $this->updateTags($bot, $chatIds, fn (array $current) => array_unique(array_merge($current, $tags)));
    }

    /**
     * {@inheritdoc}
     */
    public function untag(string $bot, array $chatIds, array $tags)
    {
        $this->updateTags($bot, $chatIds, fn (array $current) => array_diff($current, $tags));
    }

    /**
     * {@inheritdoc}
     */
    public function tags(string $bot, int|string $chatId)
    {
        return $this->explode($this->chat($bot, $chatId)->value('tags'));
    }

    /**
     * {@inheritdoc}
     */
    public function rememberTarget(string $broadcastId, string $bot, int|string $chatId, array $messageIds = [])
    {
        $this->table('targets')->upsert([[
            'broadcast_id' => $broadcastId,
            'bot' => $bot,
            'chat_id' => $chatId,
            'message_ids' => json_encode(array_values(array_map('intval', $messageIds))),
            'created_at' => Date::now(),
        ]], ['broadcast_id', 'chat_id'], ['message_ids']);
    }

    /**
     * {@inheritdoc}
     */
    public function messagesOf(string $broadcastId, array $chatIds)
    {
        $messages = [];

        $rows = $this->table('targets')
            ->where('broadcast_id', $broadcastId)
            ->whereIn('chat_id', array_values(array_filter($chatIds, 'is_numeric')))
            ->get(['chat_id', 'message_ids']);

        foreach ($rows as $row) {
            $messages[(string) $this->chatId($row->chat_id)] = (array) json_decode($row->message_ids ?: '[]', true);
        }

        return $messages;
    }

    /**
     * {@inheritdoc}
     */
    public function recipientsOf(string $broadcastId, int $chunk = 1000)
    {
        $query = $this->table('targets')->select(['id', 'chat_id'])->where('broadcast_id', $broadcastId);

        foreach ($query->lazyById($chunk, 'id') as $row) {
            yield $this->chatId($row->chat_id);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function forget(string $broadcastId)
    {
        $this->table('targets')->where('broadcast_id', $broadcastId)->delete();
    }

    /**
     * Get a query for the reachable chats of the given types matching the criteria.
     *
     * @param  string  $bot
     * @param  array  $types
     * @param  \LaraGram\Broadcasting\Telegram\ChatCriteria|null  $criteria
     * @return \LaraGram\Database\Query\Builder
     */
    protected function reachableQuery(string $bot, array $types, ?ChatCriteria $criteria = null)
    {
        $chats = $this->tables['chats'];

        $query = $this->table('chats')
            ->where("{$chats}.bot", $bot)
            ->where("{$chats}.reachable", true)
            ->when($types !== [], fn ($query) => $query->whereIn("{$chats}.type", $types));

        foreach ($criteria?->conditions() ?? [] as $condition) {
            $this->applyCondition($query, $condition);
        }

        return $query;
    }

    /**
     * Apply a criteria condition to the chats query.
     *
     * @param  \LaraGram\Database\Query\Builder  $query
     * @param  array  $condition
     * @return void
     */
    protected function applyCondition($query, array $condition): void
    {
        $chats = $this->tables['chats'];

        switch ($condition[0]) {
            case 'where':
                $query->where("{$chats}.{$condition[1]}", $condition[2], $condition[3]);
                break;

            case 'whereIn':
                $query->whereIn("{$chats}.{$condition[1]}", $condition[2], 'and', $condition[3] ?? false);
                break;

            case 'whereNull':
                $query->whereNull("{$chats}.{$condition[1]}", 'and', $condition[2] ?? false);
                break;

            case 'tagged':
                $this->applyTags($query, $condition[1], $condition[2]);
                break;

            case 'memberOf':
                $this->applyMembership($query, 'user_id', 'chat_id', $condition[1], $condition[2], $condition[3] ?? false);
                break;

            case 'hasMember':
                $this->applyMembership($query, 'chat_id', 'user_id', $condition[1], $condition[2], $condition[3] ?? false);
                break;
        }
    }

    /**
     * Constrain the chats by their tags.
     *
     * @param  \LaraGram\Database\Query\Builder  $query
     * @param  array  $tags
     * @param  string  $mode  any | all | none
     * @return void
     */
    protected function applyTags($query, array $tags, string $mode): void
    {
        $column = $this->tables['chats'].'.tags';

        match ($mode) {
            'all' => array_map(fn ($tag) => $query->where($column, 'like', '%'.$this->wrap($tag).'%'), $tags),
            'none' => $query->where(function ($query) use ($column, $tags) {
                $query->whereNull($column)->orWhere(function ($query) use ($column, $tags) {
                    foreach ($tags as $tag) {
                        $query->where($column, 'not like', '%'.$this->wrap($tag).'%');
                    }
                });
            }),
            default => $query->where(function ($query) use ($column, $tags) {
                foreach ($tags as $tag) {
                    $query->orWhere($column, 'like', '%'.$this->wrap($tag).'%');
                }
            }),
        };
    }

    /**
     * Constrain the chats by recorded memberships.
     *
     * @param  \LaraGram\Database\Query\Builder  $query
     * @param  string  $joinColumn  The members column matching the recipient's chat id.
     * @param  string  $filterColumn  The members column matching the given ids.
     * @param  array  $ids
     * @param  array  $statuses
     * @param  bool  $not
     * @return void
     */
    protected function applyMembership($query, string $joinColumn, string $filterColumn, array $ids, array $statuses, bool $not): void
    {
        $chats = $this->tables['chats'];
        $table = $this->tables['members'];

        $query->whereExists(function ($sub) use ($table, $chats, $joinColumn, $filterColumn, $ids, $statuses) {
            $sub->selectRaw('1')
                ->from($table)
                ->whereColumn("{$table}.bot", "{$chats}.bot")
                ->whereColumn("{$table}.{$joinColumn}", "{$chats}.chat_id")
                ->whereIn("{$table}.{$filterColumn}", $ids)
                ->whereIn("{$table}.status", $statuses);
        }, 'and', $not);
    }

    /**
     * Rewrite the tags of the given chats.
     *
     * @param  string  $bot
     * @param  array  $chatIds
     * @param  callable(array): array  $callback
     * @return void
     */
    protected function updateTags(string $bot, array $chatIds, callable $callback): void
    {
        $rows = $this->table('chats')->where('bot', $bot)->whereIn('chat_id', $chatIds)->get(['chat_id', 'tags']);

        foreach ($rows as $row) {
            $tags = array_values(array_filter($callback($this->explode($row->tags))));

            $this->chat($bot, $row->chat_id)->update([
                'tags' => $tags === [] ? null : $this->implode($tags),
                'updated_at' => Date::now(),
            ]);
        }
    }

    /**
     * Wrap a tag in its delimiters.
     *
     * @param  string  $tag
     * @return string
     */
    protected function wrap(string $tag): string
    {
        return self::TAG_DELIMITER.$tag.self::TAG_DELIMITER;
    }

    /**
     * Turn a list of tags into a searchable string.
     *
     * @param  array  $tags
     * @return string
     */
    protected function implode(array $tags): string
    {
        return self::TAG_DELIMITER.implode(self::TAG_DELIMITER, $tags).self::TAG_DELIMITER;
    }

    /**
     * Read the tags stored for a chat.
     *
     * @param  string|null  $tags
     * @return array<int, string>
     */
    protected function explode(?string $tags): array
    {
        return $tags === null || $tags === ''
            ? []
            : array_values(array_filter(explode(self::TAG_DELIMITER, $tags), fn ($tag) => $tag !== ''));
    }

    /**
     * Get a query for one chat.
     *
     * @param  string  $bot
     * @param  int|string  $chatId
     * @return \LaraGram\Database\Query\Builder
     */
    protected function chat(string $bot, int|string $chatId)
    {
        return $this->table('chats')->where('bot', $bot)->where('chat_id', $chatId);
    }

    /**
     * Get a query for one membership.
     *
     * @param  string  $bot
     * @param  int|string  $chatId
     * @param  int|string  $userId
     * @return \LaraGram\Database\Query\Builder
     */
    protected function member(string $bot, int|string $chatId, int|string $userId)
    {
        return $this->table('members')->where('bot', $bot)->where('chat_id', $chatId)->where('user_id', $userId);
    }

    /**
     * Get a query builder for one of the tables.
     *
     * @param  string  $name  chats | members | targets
     * @return \LaraGram\Database\Query\Builder
     */
    protected function table(string $name)
    {
        return $this->connection()->table($this->tables[$name]);
    }

    /**
     * Get the database connection.
     *
     * @return \LaraGram\Database\Connection
     */
    protected function connection()
    {
        return $this->resolver->connection($this->connection);
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
     * Truncate a string to the column size.
     *
     * @param  mixed  $value
     * @return string|null
     */
    protected function limit(mixed $value): ?string
    {
        return is_null($value) || $value === '' ? null : mb_substr((string) $value, 0, 255);
    }
}
