<?php

namespace LaraGram\Broadcasting\Telegram;

use DateTimeInterface;
use LaraGram\Broadcasting\Channel;
use LaraGram\Broadcasting\Telegram\Concerns\BroadcastsBotApiMethods;
use LaraGram\Support\Arr;
use LaraGram\Support\Traits\Conditionable;
use LaraGram\Template\Template;
use Throwable;

class Recipients
{
    use BroadcastsBotApiMethods, Conditionable;

    /**
     * The audiences and chats the broadcast is sent to.
     *
     * @var array<int, \LaraGram\Broadcasting\Channel|string>
     */
    protected array $channels;

    /**
     * The conditions selecting recipients among the recorded chats.
     */
    protected ChatCriteria $criteria;

    /**
     * The recipient options (except, limit, live checks...).
     *
     * @var array<string, mixed>
     */
    protected array $options = [];

    /**
     * Create a new set of recipients.
     *
     * @param  \LaraGram\Broadcasting\Channel|array|string|int  $audiences
     * @param  string|null  $broadcaster  The broadcast connection using the telegram driver.
     */
    public function __construct(Channel|array|string|int $audiences, protected ?string $broadcaster = 'telegram')
    {
        $this->channels = array_map(
            fn ($audience) => $audience instanceof Channel ? $audience : (string) $audience,
            Arr::wrap($audiences)
        );

        $this->criteria = new ChatCriteria;

        $this->options['current_bot'] = $this->currentBot();
    }

    /**
     * Keep the recipients whose column matches the value.
     *
     * Filters select among the chats recorded by TrackChats; chats that were
     * never recorded are left out.
     */
    public function where(string $column, mixed $operator, mixed $value = null): static
    {
        func_num_args() === 2
            ? $this->criteria->where($column, $operator)
            : $this->criteria->where($column, $operator, $value);

        return $this;
    }

    /**
     * Keep the recipients whose column is one of the values.
     */
    public function whereIn(string $column, array $values): static
    {
        $this->criteria->whereIn($column, $values);

        return $this;
    }

    /**
     * Keep the recipients whose column is none of the values.
     */
    public function whereNotIn(string $column, array $values): static
    {
        $this->criteria->whereIn($column, $values, true);

        return $this;
    }

    /**
     * Keep the recipients whose column is null.
     */
    public function whereNull(string $column): static
    {
        $this->criteria->whereNull($column);

        return $this;
    }

    /**
     * Keep the recipients whose column is not null.
     */
    public function whereNotNull(string $column): static
    {
        $this->criteria->whereNull($column, true);

        return $this;
    }

    /**
     * Keep the chats of the given types (private, group, supergroup, channel).
     */
    public function ofType(array|string $types): static
    {
        $this->criteria->ofType($types);

        return $this;
    }

    /**
     * Keep the users whose Telegram language is one of the given codes.
     */
    public function language(array|string $codes): static
    {
        $this->criteria->language($codes);

        return $this;
    }

    /**
     * Keep the chats that sent an update since the date (or within the last N days).
     */
    public function activeSince(DateTimeInterface|int $since): static
    {
        $this->criteria->activeSince($since);

        return $this;
    }

    /**
     * Keep the chats that sent no update since the date (or for the last N days).
     */
    public function inactiveSince(DateTimeInterface|int $since): static
    {
        $this->criteria->inactiveSince($since);

        return $this;
    }

    /**
     * Keep the chats first recorded after the date (or within the last N days).
     */
    public function joinedAfter(DateTimeInterface|int $date): static
    {
        $this->criteria->joinedAfter($date);

        return $this;
    }

    /**
     * Keep the chats first recorded before the date (or more than N days ago).
     */
    public function joinedBefore(DateTimeInterface|int $date): static
    {
        $this->criteria->joinedBefore($date);

        return $this;
    }

    /**
     * Keep the chats carrying at least one of the tags.
     */
    public function tagged(array|string $tags): static
    {
        $this->criteria->tagged($tags);

        return $this;
    }

    /**
     * Keep the chats carrying all of the tags.
     */
    public function taggedAll(array|string $tags): static
    {
        $this->criteria->tagged($tags, true);

        return $this;
    }

    /**
     * Skip the chats carrying any of the tags.
     */
    public function notTagged(array|string $tags): static
    {
        $this->criteria->notTagged($tags);

        return $this;
    }

    /**
     * Keep the users recorded as members of any of the given groups or channels.
     *
     * @param  array|int|string  $chatIds
     * @param  array|null  $statuses  Defaults to creator, administrator, member, restricted.
     */
    public function membersOf(array|int|string $chatIds, ?array $statuses = null): static
    {
        $this->criteria->memberOf($chatIds, $statuses);

        return $this;
    }

    /**
     * Skip the users recorded as members of any of the given groups or channels.
     *
     * @param  array|int|string  $chatIds
     */
    public function notMembersOf(array|int|string $chatIds): static
    {
        $this->criteria->memberOf($chatIds, null, true);

        return $this;
    }

    /**
     * Keep the groups and channels where any of the given users is a recorded member.
     *
     * @param  array|int|string  $userIds
     * @param  array|null  $statuses  Defaults to creator, administrator, member, restricted.
     */
    public function withMember(array|int|string $userIds, ?array $statuses = null): static
    {
        $this->criteria->hasMember($userIds, $statuses);

        return $this;
    }

    /**
     * Skip the groups and channels where any of the given users is a recorded member.
     *
     * @param  array|int|string  $userIds
     */
    public function withoutMember(array|int|string $userIds): static
    {
        $this->criteria->hasMember($userIds, null, true);

        return $this;
    }

    /**
     * Keep the groups and channels administered by any of the given users.
     *
     * @param  array|int|string  $userIds
     */
    public function administeredBy(array|int|string $userIds): static
    {
        return $this->withMember($userIds, ['creator', 'administrator']);
    }

    /**
     * Keep only recorded, reachable chats (useful with ids or custom audiences).
     */
    public function reachableOnly(): static
    {
        $this->criteria->reachable();

        return $this;
    }

    /**
     * Before sending, ask Telegram whether the user is (or is not) a member of the chat.
     *
     * @param  int|string  $chatId
     * @param  bool  $member  True to keep members, false to keep non-members.
     * @param  array|null  $statuses
     */
    public function checkMembership(int|string $chatId, bool $member = true, ?array $statuses = null): static
    {
        $this->options['checks'][] = array_filter([
            'chat' => $chatId,
            'member' => $member,
            'statuses' => $statuses,
        ], fn ($value) => $value !== null);

        return $this;
    }

    /**
     * Keep the users currently subscribed to the channel (checked live).
     */
    public function subscribedTo(int|string $chatId): static
    {
        return $this->checkMembership($chatId);
    }

    /**
     * Keep the users not subscribed to the channel (checked live).
     */
    public function notSubscribedTo(int|string $chatId): static
    {
        return $this->checkMembership($chatId, false);
    }

    /**
     * Skip the given chats.
     *
     * @param  int|string|array<int, int|string>  $chatIds
     */
    public function except(int|string|array $chatIds): static
    {
        $this->options['except'] = array_values(array_unique(array_merge(
            $this->options['except'] ?? [], Arr::wrap($chatIds)
        )));

        return $this;
    }

    /**
     * Send to at most the given number of recipients.
     */
    public function limit(int $recipients): static
    {
        $this->options['limit'] = max(0, $recipients);

        return $this;
    }

    /**
     * Count the recipients right now (live membership checks excluded).
     */
    public function count(): int
    {
        return $this->broadcast()->count();
    }

    /**
     * Render a template for every recipient and deliver the Bot API calls it makes.
     *
     * @throws \LaraGram\Broadcasting\BroadcastException
     */
    public function template(Template|string $template, array $data = [], bool $perRecipient = true): TelegramBroadcast
    {
        return Action::template($template, $data, $perRecipient)->appendTo($this->broadcast());
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
        return Action::fromCall(static::class, $method, $arguments)->appendTo($this->broadcast());
    }

    /**
     * Add a Bot API call (used by the generated methods).
     *
     * @param  string  $method
     * @param  array  $parameters
     * @param  string  $recipient  The parameter receiving each recipient.
     * @return \LaraGram\Broadcasting\Telegram\TelegramBroadcast
     */
    protected function endpoint(string $method, array $parameters, string $recipient): TelegramBroadcast
    {
        return Action::fromEndpoint($method, $parameters, $recipient)->appendTo($this->broadcast());
    }

    /**
     * Start the broadcast for these recipients.
     */
    protected function broadcast(): TelegramBroadcast
    {
        return new TelegramBroadcast($this->channels, clone $this->criteria, $this->options, $this->broadcaster);
    }

    /**
     * Get the bot connection handling the current update, if any.
     */
    protected function currentBot(): ?string
    {
        try {
            $connection = \LaraGram\Laraquest\ConnectionRegistry::getDefaultConnection();

            return $connection !== 'auto' ? $connection : null;
        } catch (Throwable) {
            return null;
        }
    }
}
