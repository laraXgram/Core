<?php

namespace LaraGram\Broadcasting\Telegram;

use LaraGram\Broadcasting\Telegram\Events\ChatMigrated;
use LaraGram\Broadcasting\Telegram\Events\ChatUnreachable;
use LaraGram\Broadcasting\Telegram\Events\DeliveryFailed;
use LaraGram\Contracts\Broadcasting\BroadcastStore;
use LaraGram\Contracts\Container\Container;
use LaraGram\Laraquest\Mode;
use LaraGram\Request\Request;
use ReflectionMethod;
use Throwable;

class Sender
{
    /**
     * Error descriptions meaning the chat can no longer be reached.
     *
     * @var array<int, string>
     */
    protected const UNREACHABLE = [
        'bot was blocked',
        'bot was kicked',
        'bot is not a member',
        'user is deactivated',
        'chat not found',
        'peer_id_invalid',
        'group chat was deactivated',
        'have no rights to send a message',
        'need administrator rights in the channel chat',
    ];

    /**
     * How often (in recipients) the cancellation flag is checked.
     *
     * @var int
     */
    protected const CANCEL_CHECK_EVERY = 25;

    /**
     * How long a send-once key is kept, in seconds (five years).
     *
     * @var int
     */
    protected const ONCE_TTL = 157680000;

    /**
     * The parameter names of known Bot API methods.
     *
     * @var array<string, array<int, string>|null>
     */
    protected static array $parameterNames = [];

    /**
     * Create a new broadcast sender.
     *
     * @param  \LaraGram\Contracts\Container\Container  $app
     * @param  \LaraGram\Broadcasting\Telegram\TemplateRenderer  $templates
     */
    public function __construct(protected Container $app, protected TemplateRenderer $templates)
    {
        //
    }

    /**
     * Deliver the steps to the given recipients, recording the outcome.
     *
     * @param  string  $id
     * @param  array<int, \LaraGram\Broadcasting\Telegram\Action>  $steps
     * @param  array<int, int|string>  $chatIds
     * @param  array  $options
     * @return array{0: array<int, int|string>, 1: int}|null
     */
    public function deliver(string $id, array $steps, array $chatIds, array $options): ?array
    {
        $chatIds = array_values($chatIds);
        $progress = Progress::for($id);
        $window = DeliveryWindow::fromArray($options['window'] ?? null);
        $interval = $this->pacingInterval($options);
        $next = microtime(true);

        $details = $this->needsDetails($steps, $options)
            ? $this->chats(fn ($chats) => $chats->find($options['bot'], $chatIds), [])
            : [];

        $messages = ! empty($options['messages_of'])
            ? $this->chats(fn ($chats) => $chats->messagesOf($options['messages_of'], $chatIds), [])
            : null;

        $rendered = [];

        foreach ($chatIds as $index => $chatId) {
            if ($index % static::CANCEL_CHECK_EVERY === 0 && $progress->cancelled()) {
                $progress->increment('skipped', count($chatIds) - $index);

                return null;
            }

            if ($window && ($wait = $window->secondsUntilOpen()) > 0) {
                return [array_slice($chatIds, $index), $wait];
            }

            if ($interval > 0) {
                if (($delay = $next - microtime(true)) > 0) {
                    usleep((int) ($delay * 1_000_000));
                }

                $next = max($next, microtime(true)) + $interval;
            }

            $recipient = Recipient::make($chatId, $details[(string) $chatId] ?? null);

            $messageIds = $messages === null ? null : ($messages[(string) $chatId] ?? []);

            $progress->increment($this->deliverTo($id, $steps, $recipient, $options, $messageIds, $rendered));
        }

        return null;
    }

    /**
     * Deliver every step to one recipient.
     *
     * @param  string  $id
     * @param  array<int, \LaraGram\Broadcasting\Telegram\Action>  $steps
     * @param  \LaraGram\Broadcasting\Telegram\Recipient  $recipient
     * @param  array  $options
     * @param  array<int, int>|null  $messageIds  The messages of a previous broadcast, when targeting them.
     * @param  array  $rendered  Templates rendered once per chunk.
     * @return string  sent | failed | unreachable | skipped
     */
    public function deliverTo(string $id, array $steps, Recipient $recipient, array $options, ?array $messageIds = null, array &$rendered = []): string
    {
        $bot = (string) $options['bot'];
        $chatId = $recipient->chat_id;

        if ($messageIds === []) {
            return 'skipped';
        }

        if (! empty($options['once']) && ! $this->claimOnce($options['once'], $bot, $chatId)) {
            return 'skipped';
        }

        try {
            if (! $this->passesMembershipChecks($recipient, $options)) {
                $this->releaseOnce($options, $bot, $chatId);

                return 'skipped';
            }

            $calls = $this->calls($id, $steps, $recipient, $options, $messageIds, $rendered);
        } catch (Throwable $e) {
            report($e);

            $this->releaseOnce($options, $bot, $chatId);

            $this->dispatch(new DeliveryFailed($id, $bot, $chatId, $steps[0]->method ?? 'unknown', 0, $e->getMessage()));

            return 'failed';
        }

        $sent = [];

        for ($index = 0, $count = count($calls); $index < $count; $index++) {
            $call = $calls[$index];

            [$outcome, $response, $newChatId] = $this->send($id, $call['method'], $call['parameters'], $chatId, $options);

            if ((string) $newChatId !== (string) $chatId) {
                $calls = $this->replaceChatId($calls, $chatId, $newChatId);
                $chatId = $newChatId;
            }

            if ($outcome !== 'sent') {
                $this->rememberTarget($id, $bot, $chatId, $sent, $options);

                if ($outcome === 'failed') {
                    $this->releaseOnce($options, $bot, $chatId);
                }

                return $outcome;
            }

            $sent = array_merge($sent, $this->messageIds($response));
        }

        $this->rememberTarget($id, $bot, $chatId, $sent, $options);

        return 'sent';
    }

    /**
     * Build the Bot API calls for one recipient.
     *
     * @param  string  $id
     * @param  array<int, \LaraGram\Broadcasting\Telegram\Action>  $steps
     * @param  \LaraGram\Broadcasting\Telegram\Recipient  $recipient
     * @param  array  $options
     * @param  array<int, int>|null  $messageIds
     * @param  array  $rendered
     * @return array<int, array{method: string, parameters: array}>
     */
    public function calls(string $id, array $steps, Recipient $recipient, array $options, ?array $messageIds = null, array &$rendered = []): array
    {
        $bot = (string) $options['bot'];
        $target = (string) ($options['target'] ?? 'chat_id');
        $calls = [];

        foreach ($steps as $index => $step) {
            if ($step->isTemplate()) {
                $templateCalls = ($step->template['per_recipient'] ?? true)
                    ? $this->templates->render($step, $recipient, $bot, $id, (bool) ($options['localized'] ?? false))
                    : ($rendered[$index] ??= $this->templates->render($step, $recipient, $bot, $id, false));

                foreach ($templateCalls as $call) {
                    $call['parameters'][$step->target ?? 'chat_id'] = $recipient->chat_id;

                    $calls[] = $call;
                }

                continue;
            }

            $calls[] = ['method' => $step->method, 'parameters' => $step->parametersFor($recipient->chat_id, $target)];
        }

        foreach ($calls as &$call) {
            if ($messageIds !== null) {
                $call['parameters'] = $this->injectMessageIds($call['method'], $call['parameters'], $messageIds, (int) ($options['message_index'] ?? 0));
            }
        }

        return $calls;
    }

    /**
     * Make one Bot API call, retrying rate limits and following group migrations.
     *
     * @param  string  $id
     * @param  string  $method
     * @param  array  $parameters
     * @param  int|string  $chatId
     * @param  array  $options
     * @return array{0: string, 1: mixed, 2: int|string}  [outcome, response, chat id]
     */
    public function send(string $id, string $method, array $parameters, int|string $chatId, array $options): array
    {
        $bot = (string) $options['bot'];
        $retries = (int) ($options['retries'] ?? 3);
        $migrated = false;

        for ($attempt = 0; ; $attempt++) {
            try {
                $response = $this->request($options)->call($method, $parameters);
            } catch (Throwable $e) {
                report($e);

                $this->dispatch(new DeliveryFailed($id, $bot, $chatId, $method, 0, $e->getMessage()));

                return ['failed', null, $chatId];
            }

            // Anything but an explicit Bot API error counts as delivered (for
            // example an intercepted call that returns no response at all).
            if (! is_array($response) || ($response['ok'] ?? true) !== false) {
                return ['sent', $response, $chatId];
            }

            $code = (int) ($response['error_code'] ?? 0);
            $description = (string) ($response['description'] ?? '');

            if ($code === 429 && $attempt < $retries) {
                $this->wait((float) ($response['parameters']['retry_after'] ?? 1));

                continue;
            }

            if (! $migrated && isset($response['parameters']['migrate_to_chat_id'])) {
                $to = $response['parameters']['migrate_to_chat_id'];

                $this->chats(fn ($chats) => $chats->migrate($bot, $chatId, $to));

                $this->dispatch(new ChatMigrated($bot, $chatId, $to));

                $parameters = $this->replaceValue($parameters, $chatId, $to);
                [$chatId, $migrated] = [$to, true];

                continue;
            }

            if ($this->isUnreachable($code, $description)) {
                $this->chats(fn ($chats) => $chats->markUnreachable($bot, $chatId, $description));

                $this->dispatch(new ChatUnreachable($id, $bot, $chatId, $code, $description));

                return ['unreachable', $response, $chatId];
            }

            $this->dispatch(new DeliveryFailed($id, $bot, $chatId, $method, $code, $description));

            return ['failed', $response, $chatId];
        }
    }

    /**
     * Create the request that delivers a call.
     *
     * @param  array  $options
     * @return \LaraGram\Request\Request
     */
    protected function request(array $options): Request
    {
        $request = (new Request)
            ->connection((string) $options['bot'])
            ->mode(Mode::CURL);

        if (! empty($options['anti_flood'])) {
            $request->antiFloodWith((string) $options['anti_flood']);
        }

        return $request;
    }

    /**
     * Determine if the recipient passes the live membership checks.
     *
     * @param  \LaraGram\Broadcasting\Telegram\Recipient  $recipient
     * @param  array  $options
     * @return bool
     */
    protected function passesMembershipChecks(Recipient $recipient, array $options): bool
    {
        foreach ((array) ($options['checks'] ?? []) as $check) {
            $response = $this->request($options)->call('getChatMember', [
                'chat_id' => $check['chat'],
                'user_id' => $recipient->chat_id,
            ]);

            $member = is_array($response) && is_array($response['result'] ?? null) ? $response['result'] : [];
            $status = $member['status'] ?? 'left';

            $present = in_array($status, $check['statuses'] ?? ChatCriteria::PRESENT, true)
                && ($status !== 'restricted' || ($member['is_member'] ?? true));

            if ($present !== (bool) ($check['member'] ?? true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Fill in the message identifiers of a previous broadcast.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @param  array<int, int>  $messageIds
     * @param  int  $index
     * @return array
     */
    protected function injectMessageIds(string $method, array $parameters, array $messageIds, int $index): array
    {
        if ($method === 'deleteMessages') {
            $parameters['message_ids'] ??= array_values($messageIds);
        } elseif (! isset($parameters['message_id']) && $this->methodAccepts($method, 'message_id', true)) {
            $parameters['message_id'] = $messageIds[$index] ?? $messageIds[0];
        }

        return $parameters;
    }

    /**
     * Determine if a Bot API method accepts the given parameter.
     *
     * @param  string  $method
     * @param  string  $parameter
     * @param  bool  $unknown  The answer for methods Laraquest does not know.
     * @return bool
     */
    protected function methodAccepts(string $method, string $parameter, bool $unknown = false): bool
    {
        if (! array_key_exists($method, static::$parameterNames)) {
            static::$parameterNames[$method] = method_exists(Request::class, $method)
                ? array_map(fn ($p) => $p->getName(), (new ReflectionMethod(Request::class, $method))->getParameters())
                : null;
        }

        return static::$parameterNames[$method] === null
            ? $unknown
            : in_array($parameter, static::$parameterNames[$method], true);
    }

    /**
     * Extract the identifiers of the messages a call sent.
     *
     * @param  mixed  $response
     * @return array<int, int>
     */
    protected function messageIds(mixed $response): array
    {
        $result = is_array($response) ? ($response['result'] ?? null) : null;

        if (! is_array($result)) {
            return [];
        }

        if (isset($result['message_id'])) {
            return [(int) $result['message_id']];
        }

        return array_values(array_map('intval', array_filter(array_column($result, 'message_id'))));
    }

    /**
     * Record the chat and the messages sent to it, when the broadcast is recallable.
     *
     * @param  string  $id
     * @param  string  $bot
     * @param  int|string  $chatId
     * @param  array<int, int>  $messageIds
     * @param  array  $options
     * @return void
     */
    protected function rememberTarget(string $id, string $bot, int|string $chatId, array $messageIds, array $options): void
    {
        if (($options['recallable'] ?? false) && $messageIds !== []) {
            $this->chats(fn ($chats) => $chats->rememberTarget($id, $bot, $chatId, $messageIds));
        }
    }

    /**
     * Claim the send-once key of a recipient.
     *
     * @param  string  $key
     * @param  string  $bot
     * @param  int|string  $chatId
     * @return bool
     */
    protected function claimOnce(string $key, string $bot, int|string $chatId): bool
    {
        return (bool) Progress::store()->add($this->onceKey($key, $bot, $chatId), true, static::ONCE_TTL);
    }

    /**
     * Release the send-once key of a recipient that did not receive the broadcast.
     *
     * @param  array  $options
     * @param  string  $bot
     * @param  int|string  $chatId
     * @return void
     */
    protected function releaseOnce(array $options, string $bot, int|string $chatId): void
    {
        if (! empty($options['once'])) {
            Progress::store()->forget($this->onceKey($options['once'], $bot, $chatId));
        }
    }

    /**
     * Get the cache key of a send-once claim.
     *
     * @param  string  $key
     * @param  string  $bot
     * @param  int|string  $chatId
     * @return string
     */
    protected function onceKey(string $key, string $bot, int|string $chatId): string
    {
        return 'laragram:broadcast:once:'.$key.':'.$bot.':'.$chatId;
    }

    /**
     * Determine if the recipients' stored details are needed.
     *
     * @param  array<int, \LaraGram\Broadcasting\Telegram\Action>  $steps
     * @param  array  $options
     * @return bool
     */
    protected function needsDetails(array $steps, array $options): bool
    {
        if ($options['localized'] ?? false) {
            return true;
        }

        foreach ($steps as $step) {
            if ($step->isTemplate()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Replace a chat identifier in the parameters of the calls.
     *
     * @param  array  $calls
     * @param  int|string  $from
     * @param  int|string  $to
     * @return array
     */
    protected function replaceChatId(array $calls, int|string $from, int|string $to): array
    {
        return array_map(fn ($call) => [
            'method' => $call['method'],
            'parameters' => $this->replaceValue($call['parameters'], $from, $to),
        ], $calls);
    }

    /**
     * Replace the recipient identifier among the top-level parameters.
     *
     * @param  array  $parameters
     * @param  int|string  $from
     * @param  int|string  $to
     * @return array
     */
    protected function replaceValue(array $parameters, int|string $from, int|string $to): array
    {
        foreach (['chat_id', 'user_id'] as $key) {
            if (isset($parameters[$key]) && (string) $parameters[$key] === (string) $from) {
                $parameters[$key] = $to;
            }
        }

        return $parameters;
    }

    /**
     * Get the seconds to leave between two recipients.
     *
     * An explicit per-second rate always applies. Otherwise the default rate
     * applies only when anti-flood is not pacing the calls.
     *
     * @param  array  $options
     * @return float
     */
    protected function pacingInterval(array $options): float
    {
        if (! empty($options['per_second'])) {
            return 1 / max(0.01, (float) $options['per_second']);
        }

        try {
            if (! empty($options['anti_flood']) && $this->app->bound('antiflood') && $this->app->make('antiflood')->enabled()) {
                return 0.0;
            }
        } catch (Throwable) {
            //
        }

        $rate = (float) ($options['rate'] ?? 25);

        return $rate > 0 ? 1 / $rate : 0.0;
    }

    /**
     * Determine if an error means the chat can no longer be reached.
     *
     * @param  int  $code
     * @param  string  $description
     * @return bool
     */
    protected function isUnreachable(int $code, string $description): bool
    {
        if ($code === 403) {
            return true;
        }

        $description = strtolower($description);

        foreach (static::UNREACHABLE as $needle) {
            if (str_contains($description, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Wait before retrying a rate limited call.
     *
     * @param  float  $seconds
     * @return void
     */
    protected function wait(float $seconds): void
    {
        usleep((int) (min(max($seconds, 0.1), 300) * 1_000_000));
    }

    /**
     * Use the chat repository without letting a storage error break the delivery.
     *
     * @param  callable(\LaraGram\Contracts\Broadcasting\BroadcastStore): mixed  $callback
     * @param  mixed  $default
     * @return mixed
     */
    protected function chats(callable $callback, mixed $default = null): mixed
    {
        try {
            return $callback($this->app->make(BroadcastStore::class));
        } catch (Throwable $e) {
            report($e);

            return $default;
        }
    }

    /**
     * Dispatch a broadcast event without letting a listener break the delivery.
     *
     * @param  object  $event
     * @return void
     */
    protected function dispatch(object $event): void
    {
        try {
            $this->app->make('events')->dispatch($event);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
