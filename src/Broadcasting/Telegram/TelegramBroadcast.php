<?php

namespace LaraGram\Broadcasting\Telegram;

use DateInterval;
use DateTimeInterface;
use LaraGram\Broadcasting\Broadcasters\TelegramBroadcaster;
use LaraGram\Broadcasting\BroadcastException;
use LaraGram\Broadcasting\BroadcastManager;
use LaraGram\Broadcasting\InteractsWithBroadcasting;
use LaraGram\Container\Container;
use LaraGram\Contracts\Broadcasting\ShouldBroadcast;
use LaraGram\Support\Arr;
use LaraGram\Support\Str;
use LaraGram\Support\Traits\Conditionable;

class TelegramBroadcast implements ShouldBroadcast
{
    use Conditionable, InteractsWithBroadcasting;

    /**
     * The steps delivered to every recipient, in order.
     *
     * @var array<int, \LaraGram\Broadcasting\Telegram\Action>
     */
    protected array $steps = [];

    /**
     * The identifier chosen with identifiedBy().
     */
    protected ?string $explicitId = null;

    /**
     * The identifier of the last send.
     */
    protected ?string $id = null;

    /**
     * The delay before a queued broadcast starts.
     */
    protected DateTimeInterface|DateInterval|int|null $delay = null;

    /**
     * Indicates if the broadcast is delivered in the current process.
     */
    protected bool $shouldBroadcastNow = false;

    /**
     * The queue connection the broadcast job is pushed to.
     */
    public ?string $connection = null;

    /**
     * The queue the broadcast job is pushed to.
     */
    public ?string $queue = null;

    /**
     * Create a new Telegram broadcast.
     *
     * @param  array<int, \LaraGram\Broadcasting\Channel|string>  $channels
     * @param  \LaraGram\Broadcasting\Telegram\ChatCriteria  $criteria
     * @param  array<string, mixed>  $options
     * @param  string|null  $broadcaster  The broadcast connection using the telegram driver.
     */
    public function __construct(
        protected array $channels,
        protected ChatCriteria $criteria,
        protected array $options = [],
        ?string $broadcaster = 'telegram',
    ) {
        $this->broadcastVia($broadcaster);
    }

    /**
     * Add another Bot API method or template, delivered after the previous ones.
     */
    public function next(): NextStep
    {
        return new NextStep($this);
    }

    /**
     * Render templates in each recipient's language (their Telegram language_code).
     */
    public function localized(bool $localized = true): static
    {
        $this->options['localized'] = $localized;

        return $this;
    }

    /**
     * Send through the given bot connection (config/bot.php).
     */
    public function bot(string $connection): static
    {
        $this->options['bot'] = $connection;

        return $this;
    }

    /**
     * Use the given broadcast connection (a connection using the telegram driver).
     */
    public function via(string $connection): static
    {
        return $this->broadcastVia($connection);
    }

    /**
     * Name the parameter that receives each recipient in every step.
     */
    public function target(string $parameter): static
    {
        foreach ($this->steps as $step) {
            $step->target = $parameter;
        }

        return $this;
    }

    /**
     * Deliver only between the given hours; outside them, deliveries wait for the next window.
     *
     * @param  string  $from  "HH:MM"
     * @param  string  $to  "HH:MM" (may be earlier than $from for overnight windows)
     * @param  string|null  $timezone  Defaults to the application timezone.
     */
    public function between(string $from, string $to, ?string $timezone = null): static
    {
        $this->options['window'] = (new DeliveryWindow($from, $to, $timezone))->toArray();

        return $this;
    }

    /**
     * Send each recipient this broadcast only once, across every broadcast using the key.
     */
    public function once(string $key): static
    {
        $this->options['once'] = $key;

        return $this;
    }

    /**
     * Remember the broadcast so it can be edited, pinned or undone later.
     *
     * The chats it reaches and the messages it sends there are recorded, and
     * its calls are kept so Broadcast::recall() knows what to undo.
     */
    public function recallable(bool $recallable = true): static
    {
        $this->options['recallable'] = $recallable;

        return $this;
    }

    /**
     * Send a summary to the given chats when the broadcast completes.
     *
     * @param  array|int|string  $chatIds
     */
    public function reportTo(array|int|string $chatIds): static
    {
        $this->options['report_to'] = array_values(Arr::wrap($chatIds));

        return $this;
    }

    /**
     * Set how many recipients each queued job delivers to.
     */
    public function chunk(int $size): static
    {
        $this->options['chunk'] = max(1, $size);

        return $this;
    }

    /**
     * Deliver at most the given number of recipients per second (per worker).
     */
    public function perSecond(float $rate): static
    {
        $this->options['per_second'] = $rate;

        return $this;
    }

    /**
     * Pace the deliveries with the given anti-flood scope, or null for none.
     */
    public function antiFlood(?string $scope): static
    {
        $this->options['anti_flood'] = $scope ?? '';

        return $this;
    }

    /**
     * Set the queue the broadcast and its delivery jobs are pushed to.
     */
    public function onQueue(?string $queue): static
    {
        $this->queue = $queue;
        $this->options['queue'] = $queue;

        return $this;
    }

    /**
     * Set the queue connection the broadcast and its delivery jobs are pushed to.
     */
    public function onConnection(?string $connection): static
    {
        $this->connection = $connection;
        $this->options['queue_connection'] = $connection;

        return $this;
    }

    /**
     * Set the identifier used to track the broadcast.
     */
    public function identifiedBy(string $id): static
    {
        $this->explicitId = $id;

        return $this;
    }

    /**
     * Get the identifier of the last send (or the chosen identifier).
     */
    public function id(): ?string
    {
        return $this->id ?? $this->explicitId;
    }

    /**
     * Deliver the broadcast in the current process and return its identifier.
     */
    public function send(): string
    {
        return $this->dispatchBroadcast(true, null);
    }

    /**
     * Queue the broadcast and return its identifier.
     */
    public function queue(): string
    {
        return $this->dispatchBroadcast(false, null);
    }

    /**
     * Queue the broadcast to start at the given moment (or after the given delay).
     *
     * @param  \DateTimeInterface|\DateInterval|int  $when  A moment, an interval, or seconds from now.
     */
    public function later(DateTimeInterface|DateInterval|int $when): string
    {
        return $this->dispatchBroadcast(false, $when);
    }

    /**
     * Deliver the broadcast right now to the given chats only, ignoring filters and limits.
     *
     * Use it to preview a broadcast in your own chat before sending it to everyone.
     *
     * @param  array|int|string  $chatIds
     */
    public function test(array|int|string $chatIds): string
    {
        $test = clone $this;

        $test->channels = array_map('strval', Arr::wrap($chatIds));
        $test->criteria = new ChatCriteria;
        $test->explicitId = null;

        unset($test->options['checks'], $test->options['limit'], $test->options['except'], $test->options['once'], $test->options['window'], $test->options['report_to']);

        return $test->send();
    }

    /**
     * Count the recipients the broadcast would reach right now (live checks excluded).
     */
    public function count(): int
    {
        $manager = Container::getInstance()->make(BroadcastManager::class);

        $broadcaster = $manager->connection($this->broadcastConnections()[0] ?? $manager->getTelegramConnection());

        if (! $broadcaster instanceof TelegramBroadcaster) {
            throw new BroadcastException('Only broadcasts sent through a telegram connection can be counted.');
        }

        return $broadcaster->count($this->channels, $this->deliveryOptions());
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \LaraGram\Broadcasting\Channel|string>
     */
    public function broadcastOn(): array
    {
        return $this->channels;
    }

    /**
     * Get the name the event should broadcast as (the first Bot API method).
     */
    public function broadcastAs(): string
    {
        return $this->ensureSteps()[0]->method;
    }

    /**
     * Get the payload the event should broadcast with.
     *
     * @return array{steps: array, options: array}
     */
    public function broadcastWith(): array
    {
        return [
            'steps' => array_map(fn (Action $step) => $step->toArray(), $this->ensureSteps()),
            'options' => array_merge($this->deliveryOptions(), [
                'id' => $this->id ?? $this->explicitId,
                'now' => $this->shouldBroadcastNow,
            ]),
        ];
    }

    /**
     * Determine if the event should be broadcast synchronously.
     */
    public function shouldBroadcastNow(): bool
    {
        return $this->shouldBroadcastNow;
    }

    /**
     * Get the delay before the queued broadcast starts.
     */
    public function broadcastDelay(): DateTimeInterface|DateInterval|int|null
    {
        return $this->shouldBroadcastNow ? null : $this->delay;
    }

    /**
     * Fail when the queue connection runs jobs immediately and ignores delays.
     *
     * @param  string|null  $connection
     * @param  string  $feature
     * @return void
     *
     * @throws \LaraGram\Broadcasting\BroadcastException
     */
    public static function ensureQueueSupportsDelays(?string $connection, string $feature): void
    {
        $app = Container::getInstance();
        $config = $app->make('config');

        $connection ??= $config->get('broadcasting.connections.'.$app->make(BroadcastManager::class)->getTelegramConnection().'.queue_connection');
        $connection ??= $config->get('queue.default');

        if (in_array($config->get("queue.connections.{$connection}.driver"), ['sync', 'null', 'deferred', 'background'], true)) {
            throw new BroadcastException(
                "{$feature} need a queue connection that supports delays, but [{$connection}] runs jobs immediately. Use a database, redis or beanstalkd queue."
            );
        }
    }

    /**
     * Prepare the object for cloning.
     *
     * @return void
     */
    public function __clone()
    {
        $this->criteria = clone $this->criteria;
        $this->steps = array_map(fn (Action $step) => clone $step, $this->steps);
    }

    /**
     * Add a step (called by Recipients and NextStep).
     *
     * @param  \LaraGram\Broadcasting\Telegram\Action  $action
     * @return $this
     */
    protected function addStep(Action $action): static
    {
        $this->steps[] = $action;

        return $this;
    }

    /**
     * Record the broadcast and hand it to the broadcaster.
     *
     * @param  bool  $now
     * @param  \DateTimeInterface|\DateInterval|int|null  $delay
     * @return string
     */
    protected function dispatchBroadcast(bool $now, DateTimeInterface|DateInterval|int|null $delay): string
    {
        $this->ensureSteps();

        if ($delay !== null) {
            static::ensureQueueSupportsDelays($this->connection, 'Broadcasts sent later');
        }

        if (isset($this->options['window'])) {
            static::ensureQueueSupportsDelays($this->connection, 'Broadcasts with a delivery window');
        }

        $this->shouldBroadcastNow = $now;
        $this->delay = $delay;
        $this->id = $this->explicitId ?? (string) Str::ulid();

        $recallable = $this->options['recallable']
            ?? Container::getInstance()->make('config')->get('broadcasting.recall.enabled', false);

        Progress::for($this->id)->queue(
            $this->steps[0]->method,
            $this->options['bot'] ?? $this->options['current_bot'] ?? null,
            array_map('strval', $this->channels),
            $this->scheduledAt($delay),
            $this->options['report_to'] ?? [],
            $recallable ? array_map(fn (Action $step) => $step->toArray(), $this->steps) : [],
            $recallable ? (int) Container::getInstance()->make('config')->get('broadcasting.recall.ttl', 2592000) : null,
        );

        try {
            broadcast($this);
        } finally {
            $this->shouldBroadcastNow = false;
            $this->delay = null;
        }

        return $this->id;
    }

    /**
     * Get the delivery options sent with the payload.
     *
     * @return array
     */
    protected function deliveryOptions(): array
    {
        return array_merge($this->options, ['criteria' => $this->criteria->toArray()]);
    }

    /**
     * Get the steps, failing when none was chosen.
     *
     * @return array<int, \LaraGram\Broadcasting\Telegram\Action>
     *
     * @throws \LaraGram\Broadcasting\BroadcastException
     */
    protected function ensureSteps(): array
    {
        if ($this->steps === []) {
            throw new BroadcastException('The broadcast has nothing to send.');
        }

        return $this->steps;
    }

    /**
     * Get the moment a delay points to.
     */
    protected function scheduledAt(DateTimeInterface|DateInterval|int|null $delay): ?int
    {
        return match (true) {
            $delay === null => null,
            $delay instanceof DateTimeInterface => $delay->getTimestamp(),
            $delay instanceof DateInterval => (new \DateTimeImmutable)->add($delay)->getTimestamp(),
            default => time() + $delay,
        };
    }
}
