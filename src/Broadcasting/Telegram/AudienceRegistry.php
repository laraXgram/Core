<?php

namespace LaraGram\Broadcasting\Telegram;

use LaraGram\Broadcasting\BroadcastException;
use LaraGram\Contracts\Container\Container;
use LaraGram\Support\Collection;

class AudienceRegistry
{
    /**
     * The registered audience resolvers, keyed by name or pattern.
     *
     * @var array<string, callable|string>
     */
    protected array $resolvers = [];

    /**
     * Create a new audience registry.
     *
     * @param  \LaraGram\Contracts\Container\Container  $app
     */
    public function __construct(protected Container $app)
    {
        //
    }

    /**
     * Register an audience resolver.
     *
     * The name may contain "{placeholders}" (e.g. "language.{code}") whose
     * values are passed to the resolver as named parameters.
     *
     * @param  string  $name
     * @param  callable|string  $resolver
     * @return $this
     */
    public function define(string $name, callable|string $resolver)
    {
        $this->resolvers[$name] = $resolver;

        return $this;
    }

    /**
     * Determine if an audience with the given name can be resolved.
     *
     * @param  string  $name
     * @return bool
     */
    public function has(string $name): bool
    {
        return $this->match($name) !== null
            || array_key_exists($name, Audience::BUILT_IN)
            || Audience::isChat($name)
            || Audience::membersOf($name) !== null
            || Audience::sentBy($name) !== null;
    }

    /**
     * Determine if the audience applies filter criteria while it is resolved.
     *
     * Other audiences are filtered against the chat repository afterwards.
     *
     * @param  string  $name
     * @return bool
     */
    public function appliesCriteria(string $name): bool
    {
        return $this->match($name) === null && array_key_exists($name, Audience::BUILT_IN);
    }

    /**
     * Get the registered audiences.
     *
     * @return \LaraGram\Support\Collection<string, callable|string>
     */
    public function all()
    {
        return new Collection($this->resolvers);
    }

    /**
     * Lazily resolve the chat identifiers of an audience.
     *
     * @param  string  $name
     * @param  string  $bot
     * @param  int  $chunk
     * @param  \LaraGram\Broadcasting\Telegram\ChatCriteria|null  $criteria  Applied by the built-in audiences only.
     * @return iterable<int, int|string>
     *
     * @throws \LaraGram\Broadcasting\BroadcastException
     */
    public function resolve(string $name, string $bot, int $chunk = 1000, ?ChatCriteria $criteria = null): iterable
    {
        if (! is_null($chatId = Audience::chatId($name))) {
            yield $chatId;

            return;
        }

        if (! is_null($match = $this->match($name))) {
            [$resolver, $parameters] = $match;

            yield from $this->normalize(
                $this->call($resolver, array_merge($parameters, ['bot' => $bot])), $chunk
            );

            return;
        }

        if (array_key_exists($name, Audience::BUILT_IN)) {
            yield from $this->chats()->reachable($bot, Audience::BUILT_IN[$name], $chunk, $criteria);

            return;
        }

        if (! is_null($chatId = Audience::membersOf($name))) {
            yield from $this->chats()->members($bot, $chatId, null, $chunk);

            return;
        }

        if (! is_null($broadcastId = Audience::sentBy($name))) {
            yield from $this->chats()->recipientsOf($broadcastId, $chunk);

            return;
        }

        throw new BroadcastException("Telegram audience [{$name}] is not defined.");
    }

    /**
     * Get the chat repository.
     *
     * @return \LaraGram\Contracts\Broadcasting\BroadcastStore
     */
    protected function chats()
    {
        return $this->app->make(\LaraGram\Contracts\Broadcasting\BroadcastStore::class);
    }

    /**
     * Find the resolver matching the audience name and extract its parameters.
     *
     * @param  string  $name
     * @return array{0: callable|string, 1: array<string, string>}|null
     */
    protected function match(string $name): ?array
    {
        if (isset($this->resolvers[$name])) {
            return [$this->resolvers[$name], []];
        }

        foreach ($this->resolvers as $pattern => $resolver) {
            if (! str_contains($pattern, '{')) {
                continue;
            }

            $regex = '/^'.preg_replace('/\\\{(\w+)\\\}/', '(?<$1>[^\.]+)', preg_quote($pattern, '/')).'$/';

            if (preg_match($regex, $name, $matches)) {
                return [$resolver, array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY)];
            }
        }

        return null;
    }

    /**
     * Invoke an audience resolver through the container.
     *
     * @param  callable|string  $resolver
     * @param  array  $parameters
     * @return mixed
     */
    protected function call(callable|string $resolver, array $parameters)
    {
        if (is_string($resolver) && ! is_callable($resolver)) {
            $instance = $this->app->make($resolver);

            $resolver = method_exists($instance, 'resolve') ? [$instance, 'resolve'] : $instance;
        }

        return $this->app->call($resolver, $parameters);
    }

    /**
     * Turn a resolver result into a lazy stream of chat identifiers.
     *
     * @param  mixed  $result
     * @param  int  $chunk
     * @return iterable<int, int|string>
     */
    protected function normalize(mixed $result, int $chunk): iterable
    {
        if ($result instanceof \LaraGram\Database\Eloquent\Builder ||
            $result instanceof \LaraGram\Database\Eloquent\Relations\Relation) {
            $result = $result->lazyById($chunk);
        } elseif ($result instanceof \LaraGram\Database\Query\Builder) {
            $result = $result->cursor();
        } elseif (is_scalar($result)) {
            $result = [$result];
        }

        foreach ($result ?? [] as $item) {
            if (! is_null($chatId = static::chatIdFrom($item))) {
                yield $chatId;
            }
        }
    }

    /**
     * Extract a chat identifier from a resolved item.
     *
     * Scalars are used as-is. Arrays and objects are read in this order:
     * broadcastChatId(), chat_id, user_id, id.
     *
     * @param  mixed  $item
     * @return int|string|null
     */
    public static function chatIdFrom(mixed $item): int|string|null
    {
        $value = match (true) {
            is_object($item) && method_exists($item, 'broadcastChatId') => $item->broadcastChatId(),
            is_array($item) => $item['chat_id'] ?? $item['user_id'] ?? $item['id'] ?? null,
            is_object($item) => $item->chat_id ?? $item->user_id ?? $item->id ?? null,
            default => $item,
        };

        if (is_int($value) || (is_string($value) && preg_match('/^-?\d+$/', $value))) {
            return (int) $value;
        }

        return is_string($value) && str_starts_with($value, '@') ? $value : null;
    }
}
