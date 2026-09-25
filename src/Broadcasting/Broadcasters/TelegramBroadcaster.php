<?php

namespace LaraGram\Broadcasting\Broadcasters;

use Generator;
use LaraGram\Broadcasting\BroadcastException;
use LaraGram\Broadcasting\Telegram\Action;
use LaraGram\Broadcasting\Telegram\AudienceRegistry;
use LaraGram\Broadcasting\Telegram\ChatCriteria;
use LaraGram\Broadcasting\Telegram\Events\BroadcastStarted;
use LaraGram\Broadcasting\Telegram\Progress;
use LaraGram\Broadcasting\Telegram\SendBroadcastChunk;
use LaraGram\Broadcasting\Telegram\Sender;
use LaraGram\Broadcasting\Telegram\TelegramBroadcast;
use LaraGram\Contracts\Broadcasting\BroadcastStore;
use LaraGram\Contracts\Container\Container;
use LaraGram\Foundation\Http\Exceptions\AccessDeniedHttpException;
use LaraGram\Support\Str;
use Throwable;

class TelegramBroadcaster extends Broadcaster
{
    /**
     * The number of identifiers filtered against the chat repository at once.
     *
     * @var int
     */
    protected const FILTER_BATCH = 500;

    /**
     * Create a new broadcaster instance.
     *
     * @param  \LaraGram\Contracts\Container\Container  $app
     * @param  \LaraGram\Broadcasting\Telegram\AudienceRegistry  $audiences
     * @param  array  $config
     */
    public function __construct(
        protected Container $app,
        protected AudienceRegistry $audiences,
        protected array $config = [],
    ) {
        //
    }

    /**
     * Telegram audiences cannot be subscribed to from a browser.
     *
     * @param  \LaraGram\Http\Request  $request
     * @return mixed
     *
     * @throws \LaraGram\Foundation\Http\Exceptions\AccessDeniedHttpException
     */
    public function auth($request)
    {
        throw new AccessDeniedHttpException;
    }

    /**
     * {@inheritdoc}
     */
    public function validAuthenticationResponse($request, $result)
    {
        //
    }

    /**
     * Deliver the Bot API calls described by the event to every recipient.
     *
     * The event name is the Bot API method and the payload its parameters,
     * unless the payload holds actions ("method" or "steps" with "options").
     *
     * @param  array  $channels
     * @param  string  $event
     * @param  array  $payload
     * @return void
     *
     * @throws \LaraGram\Broadcasting\BroadcastException
     */
    public function broadcast(array $channels, $event, array $payload = [])
    {
        $channels = $this->prepareChannels($channels);

        if (empty($channels)) {
            return;
        }

        [$steps, $options] = Action::parse((string) $event, $payload);

        foreach ($steps as $step) {
            $options = array_merge($step->options, $options);
            $step->options = [];
        }

        $options = $this->options($options);
        $id = $options['id'];

        $progress = Progress::for($id);

        $progress->start($steps[0]->method, $options['bot'], $channels, $options['report_to']);

        if ($progress->cancelled()) {
            $progress->seal(0);

            SendBroadcastChunk::completeIfDone($id);

            return;
        }

        $this->app->make('events')->dispatch(
            new BroadcastStarted($id, $options['bot'], $steps[0]->method, $channels)
        );

        if (! empty($options['window'])) {
            TelegramBroadcast::ensureQueueSupportsDelays($options['queue_connection'], 'Broadcasts with a delivery window');
        }

        $total = 0;

        try {
            foreach ($this->chunks($channels, $options) as $chatIds) {
                $total += count($chatIds);

                if (! $options['now']) {
                    $this->dispatchChunk($id, $steps, $chatIds, $options);

                    continue;
                }

                if ($remaining = $this->app->make(Sender::class)->deliver($id, $steps, $chatIds, $options)) {
                    [$later, $delay] = $remaining;

                    $progress->waitUntil(time() + $delay);

                    $this->dispatchChunk($id, $steps, $later, $options, $delay);
                }
            }
        } finally {
            $progress->seal($total);

            SendBroadcastChunk::completeIfDone($id);
        }
    }

    /**
     * Count the recipients the channels would reach, without sending anything.
     *
     * Live membership checks are not applied.
     *
     * @param  array  $channels
     * @param  array  $options
     * @return int
     */
    public function count(array $channels, array $options = []): int
    {
        $options = $this->options(array_merge($options, ['id' => 'count']));

        $count = 0;

        foreach ($this->chunks($this->prepareChannels($channels), $options) as $chatIds) {
            $count += count($chatIds);
        }

        return $count;
    }

    /**
     * Resolve the recipients of the channels as chunks of unique chat ids.
     *
     * @param  array<int, string>  $channels
     * @param  array  $options
     * @return \Generator<int, array<int, int|string>>
     */
    public function chunks(array $channels, array $options): Generator
    {
        $size = max(1, (int) $options['chunk']);
        $limit = $options['limit'] !== null ? max(0, (int) $options['limit']) : null;
        $except = array_fill_keys(array_map('strval', $options['except']), true);
        $criteria = ChatCriteria::fromArray($options['criteria']);
        $dedupe = count($channels) > 1;
        $seen = [];
        $chunk = [];
        $total = 0;

        if ($limit === 0) {
            return;
        }

        foreach ($channels as $channel) {
            foreach ($this->recipients($channel, $options['bot'], $criteria) as $chatId) {
                $key = (string) $chatId;

                if (isset($except[$key]) || ($dedupe && isset($seen[$key]))) {
                    continue;
                }

                if ($dedupe) {
                    $seen[$key] = true;
                }

                $chunk[] = $chatId;
                $total++;

                if (count($chunk) >= $size) {
                    yield $chunk;

                    $chunk = [];
                }

                if ($limit !== null && $total >= $limit) {
                    break 2;
                }
            }
        }

        if ($chunk !== []) {
            yield $chunk;
        }
    }

    /**
     * Resolve the recipients of one channel, applying the criteria.
     *
     * @param  string  $channel
     * @param  string  $bot
     * @param  \LaraGram\Broadcasting\Telegram\ChatCriteria  $criteria
     * @return \Generator<int, int|string>
     */
    protected function recipients(string $channel, string $bot, ChatCriteria $criteria): Generator
    {
        if ($criteria->isEmpty() || $this->audiences->appliesCriteria($channel)) {
            yield from $this->audiences->resolve($channel, $bot, 1000, $criteria);

            return;
        }

        $repository = $this->app->make(BroadcastStore::class);
        $batch = [];

        foreach ($this->audiences->resolve($channel, $bot, 1000) as $chatId) {
            $batch[] = $chatId;

            if (count($batch) >= static::FILTER_BATCH) {
                yield from $repository->filter($bot, $batch, $criteria);

                $batch = [];
            }
        }

        if ($batch !== []) {
            yield from $repository->filter($bot, $batch, $criteria);
        }
    }

    /**
     * Queue the delivery of a chunk of recipients.
     *
     * @param  string  $id
     * @param  array<int, \LaraGram\Broadcasting\Telegram\Action>  $steps
     * @param  array  $chatIds
     * @param  array  $options
     * @param  int  $delay
     * @return void
     */
    protected function dispatchChunk(string $id, array $steps, array $chatIds, array $options, int $delay = 0): void
    {
        $job = (new SendBroadcastChunk($id, $steps, $chatIds, $options))
            ->onConnection($options['queue_connection'])
            ->onQueue($options['queue']);

        if ($delay > 0) {
            $job->delay($delay);
        }

        $this->app->make(\LaraGram\Contracts\Bus\Dispatcher::class)->dispatch($job);
    }

    /**
     * Normalize and validate the channels.
     *
     * @param  array  $channels
     * @return array<int, string>
     *
     * @throws \LaraGram\Broadcasting\BroadcastException
     */
    protected function prepareChannels(array $channels): array
    {
        $channels = array_values(array_unique($this->formatChannels($channels)));

        foreach ($channels as $channel) {
            if (! $this->audiences->has($channel)) {
                throw new BroadcastException("Telegram audience [{$channel}] is not defined.");
            }
        }

        return $channels;
    }

    /**
     * Build the delivery options from the connection config and the broadcast.
     *
     * @param  array  $options
     * @return array
     */
    public function options(array $options): array
    {
        $options = array_merge([
            'bot' => $this->config['bot'] ?? null,
            'current_bot' => null,
            'chunk' => $this->config['chunk'] ?? 100,
            'queue_connection' => $this->config['queue_connection'] ?? null,
            'queue' => $this->config['queue'] ?? null,
            'anti_flood' => $this->config['anti_flood'] ?? 'broadcast',
            'rate' => $this->config['rate'] ?? 25,
            'per_second' => null,
            'retries' => $this->config['retries'] ?? 3,
            'target' => 'chat_id',
            'except' => [],
            'limit' => null,
            'criteria' => [],
            'checks' => [],
            'window' => null,
            'once' => null,
            'localized' => false,
            'recallable' => $this->app->make('config')->get('broadcasting.recall.enabled', false),
            'messages_of' => null,
            'message_index' => 0,
            'report_to' => [],
            'now' => false,
            'id' => null,
        ], array_filter($options, fn ($value) => $value !== null));

        $options['id'] = (string) ($options['id'] ?: Str::ulid());
        $options['bot'] = $this->resolveBot($options['bot'] ?: $options['current_bot']);
        $options['except'] = array_values((array) $options['except']);
        $options['report_to'] = array_values((array) $options['report_to']);

        unset($options['current_bot']);

        return $options;
    }

    /**
     * Resolve the bot connection deliveries are sent through.
     *
     * @param  string|null  $bot
     * @return string
     *
     * @throws \LaraGram\Broadcasting\BroadcastException
     */
    protected function resolveBot(?string $bot): string
    {
        if (! empty($bot) && $bot !== 'auto') {
            return $bot;
        }

        $config = $this->app->make('config');

        $default = $config->get('bot.default');

        if (! empty($default) && $default !== 'auto') {
            return (string) $default;
        }

        try {
            if (($current = \LaraGram\Request\Request::getDefaultConnection()) && $current !== 'auto') {
                return $current;
            }
        } catch (Throwable) {
            //
        }

        $connections = array_keys((array) $config->get('bot.connections', []));

        if (count($connections) === 1) {
            return (string) $connections[0];
        }

        throw new BroadcastException(
            'Unable to determine which bot sends the broadcast. Call ->bot($connection) or set the "bot" option of the telegram broadcast connection.'
        );
    }
}
