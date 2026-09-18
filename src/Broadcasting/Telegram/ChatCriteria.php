<?php

namespace LaraGram\Broadcasting\Telegram;

use DateTimeInterface;
use InvalidArgumentException;
use LaraGram\Contracts\Support\Arrayable;
use LaraGram\Support\Arr;

class ChatCriteria implements Arrayable
{
    /**
     * The member statuses that count as being in a chat.
     *
     * @var array<int, string>
     */
    public const PRESENT = ['creator', 'administrator', 'member', 'restricted'];

    /**
     * The conditions, in the order they were added.
     *
     * @var array<int, array>
     */
    protected array $conditions = [];

    /**
     * Create a new criteria instance.
     *
     * @param  array<int, array>  $conditions
     */
    public function __construct(array $conditions = [])
    {
        $this->conditions = array_values($conditions);
    }

    /**
     * Create a criteria instance from its array form.
     *
     * @param  array|null  $conditions
     * @return static
     */
    public static function fromArray(?array $conditions)
    {
        return new static($conditions ?? []);
    }

    /**
     * Add a condition on a column of the chats table.
     *
     * @param  string  $column
     * @param  mixed  $operator
     * @param  mixed  $value
     * @return $this
     */
    public function where(string $column, mixed $operator, mixed $value = null)
    {
        if (func_num_args() === 2) {
            [$operator, $value] = ['=', $operator];
        }

        if (! in_array(strtolower((string) $operator), ['=', '!=', '<>', '<', '<=', '>', '>=', 'like', 'not like'], true)) {
            throw new InvalidArgumentException("Unsupported broadcast filter operator [{$operator}].");
        }

        return $this->push('where', $this->column($column), $operator, $this->value($value));
    }

    /**
     * Require a column to be one of the given values.
     *
     * @param  string  $column
     * @param  array  $values
     * @param  bool  $not
     * @return $this
     */
    public function whereIn(string $column, array $values, bool $not = false)
    {
        return $this->push('whereIn', $this->column($column), array_values($values), $not);
    }

    /**
     * Require a column to be null (or not null).
     *
     * @param  string  $column
     * @param  bool  $not
     * @return $this
     */
    public function whereNull(string $column, bool $not = false)
    {
        return $this->push('whereNull', $this->column($column), $not);
    }

    /**
     * Require the chat to be one of the given types.
     *
     * @param  array|string  $types  private | group | supergroup | channel
     * @return $this
     */
    public function ofType(array|string $types)
    {
        return $this->whereIn('type', Arr::wrap($types));
    }

    /**
     * Require the user's language to be one of the given codes.
     *
     * @param  array|string  $codes
     * @return $this
     */
    public function language(array|string $codes)
    {
        return $this->whereIn('language_code', Arr::wrap($codes));
    }

    /**
     * Require the chat to have sent an update since the given moment.
     *
     * @param  \DateTimeInterface|int  $since  A date, or a number of days ago.
     * @return $this
     */
    public function activeSince(DateTimeInterface|int $since)
    {
        return $this->where('last_seen_at', '>=', $this->moment($since));
    }

    /**
     * Require the chat to have sent no update since the given moment.
     *
     * @param  \DateTimeInterface|int  $since  A date, or a number of days ago.
     * @return $this
     */
    public function inactiveSince(DateTimeInterface|int $since)
    {
        return $this->where('last_seen_at', '<', $this->moment($since));
    }

    /**
     * Require the chat to have been recorded after the given moment.
     *
     * @param  \DateTimeInterface|int  $date  A date, or a number of days ago.
     * @return $this
     */
    public function joinedAfter(DateTimeInterface|int $date)
    {
        return $this->where('created_at', '>=', $this->moment($date));
    }

    /**
     * Require the chat to have been recorded before the given moment.
     *
     * @param  \DateTimeInterface|int  $date  A date, or a number of days ago.
     * @return $this
     */
    public function joinedBefore(DateTimeInterface|int $date)
    {
        return $this->where('created_at', '<', $this->moment($date));
    }

    /**
     * Require the chat to carry at least one (or all) of the given tags.
     *
     * @param  array|string  $tags
     * @param  bool  $all
     * @return $this
     */
    public function tagged(array|string $tags, bool $all = false)
    {
        return $this->push('tagged', array_values(Arr::wrap($tags)), $all ? 'all' : 'any');
    }

    /**
     * Require the chat to carry none of the given tags.
     *
     * @param  array|string  $tags
     * @return $this
     */
    public function notTagged(array|string $tags)
    {
        return $this->push('tagged', array_values(Arr::wrap($tags)), 'none');
    }

    /**
     * Require the recipient (a user) to be a recorded member of one of the given chats.
     *
     * @param  array|int|string  $chatIds
     * @param  array|null  $statuses
     * @param  bool  $not
     * @return $this
     */
    public function memberOf(array|int|string $chatIds, ?array $statuses = null, bool $not = false)
    {
        return $this->push('memberOf', array_values(Arr::wrap($chatIds)), $statuses ?? self::PRESENT, $not);
    }

    /**
     * Require the recipient (a group or channel) to have one of the given users as a recorded member.
     *
     * @param  array|int|string  $userIds
     * @param  array|null  $statuses
     * @param  bool  $not
     * @return $this
     */
    public function hasMember(array|int|string $userIds, ?array $statuses = null, bool $not = false)
    {
        return $this->push('hasMember', array_values(Arr::wrap($userIds)), $statuses ?? self::PRESENT, $not);
    }

    /**
     * Require the recipient to be recorded and reachable.
     *
     * @return $this
     */
    public function reachable()
    {
        return $this->push('reachable');
    }

    /**
     * Determine if there are no conditions.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->conditions === [];
    }

    /**
     * Get the conditions.
     *
     * @return array<int, array>
     */
    public function conditions(): array
    {
        return $this->conditions;
    }

    /**
     * Get the array form of the criteria.
     *
     * @return array<int, array>
     */
    public function toArray(): array
    {
        return $this->conditions;
    }

    /**
     * Add a condition.
     *
     * @param  string  $type
     * @param  mixed  ...$arguments
     * @return $this
     */
    protected function push(string $type, mixed ...$arguments)
    {
        $this->conditions[] = [$type, ...$arguments];

        return $this;
    }

    /**
     * Validate a column name.
     *
     * @param  string  $column
     * @return string
     */
    protected function column(string $column): string
    {
        if (! preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $column)) {
            throw new InvalidArgumentException("Invalid broadcast filter column [{$column}].");
        }

        return $column;
    }

    /**
     * Normalize a value so the criteria stays serializable.
     *
     * @param  mixed  $value
     * @return mixed
     */
    protected function value(mixed $value): mixed
    {
        return $value instanceof DateTimeInterface ? $value->format('Y-m-d H:i:s') : $value;
    }

    /**
     * Turn a date or a number of days ago into a timestamp string.
     *
     * @param  \DateTimeInterface|int  $moment
     * @return string
     */
    protected function moment(DateTimeInterface|int $moment): string
    {
        return is_int($moment)
            ? date('Y-m-d H:i:s', time() - $moment * 86400)
            : $moment->format('Y-m-d H:i:s');
    }
}
