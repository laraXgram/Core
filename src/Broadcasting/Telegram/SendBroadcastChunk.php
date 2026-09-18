<?php

namespace LaraGram\Broadcasting\Telegram;

use LaraGram\Broadcasting\Telegram\Events\BroadcastCompleted;
use LaraGram\Bus\Queueable;
use LaraGram\Contracts\Queue\ShouldQueue;
use LaraGram\Laraquest\Mode;
use LaraGram\Queue\InteractsWithQueue;
use LaraGram\Request\Request;
use Throwable;

class SendBroadcastChunk implements ShouldQueue
{
    use InteractsWithQueue, Queueable;

    /**
     * The job is never retried: a retry would message recipients twice.
     *
     * @var int
     */
    public $tries = 1;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout;

    /**
     * Create a new job instance.
     *
     * @param  string  $id  The broadcast identifier.
     * @param  array<int, \LaraGram\Broadcasting\Telegram\Action>  $steps
     * @param  array<int, int|string>  $chatIds
     * @param  array  $options
     */
    public function __construct(
        public string $id,
        public array $steps,
        public array $chatIds,
        public array $options,
    ) {
        $this->timeout = count($chatIds) * 3 * max(1, count($steps)) + 60;
    }

    /**
     * Deliver the steps to this chunk of recipients.
     *
     * @param  \LaraGram\Broadcasting\Telegram\Sender  $sender
     * @return void
     */
    public function handle(Sender $sender)
    {
        $progress = Progress::for($this->id);

        $progress->resume();

        if ($remaining = $sender->deliver($this->id, $this->steps, $this->chatIds, $this->options)) {
            [$chatIds, $delay] = $remaining;

            try {
                TelegramBroadcast::ensureQueueSupportsDelays($this->connection, 'Broadcasts with a delivery window');
            } catch (Throwable $e) {
                report($e);

                $progress->increment('skipped', count($chatIds));

                static::completeIfDone($this->id);

                return;
            }

            $progress->waitUntil(time() + $delay);

            // The delivery window closed: continue with the rest when it opens.
            dispatch((new static($this->id, $this->steps, $chatIds, $this->options))
                ->onConnection($this->connection)
                ->onQueue($this->queue)
                ->delay($delay));

            return;
        }

        static::completeIfDone($this->id);
    }

    /**
     * Count the recipients as failed when the job itself fails.
     *
     * @param  \Throwable|null  $e
     * @return void
     */
    public function failed(?Throwable $e = null): void
    {
        Progress::for($this->id)->increment('failed', count($this->chatIds));

        static::completeIfDone($this->id);
    }

    /**
     * Fire the completion event and send the report once every recipient was processed.
     *
     * @param  string  $id
     * @return void
     */
    public static function completeIfDone(string $id): void
    {
        $progress = Progress::for($id);

        if (! $progress->completeIfDone()) {
            return;
        }

        event(new BroadcastCompleted($id, $progress));

        foreach ($progress->reportTo() as $chatId) {
            try {
                (new Request)
                    ->connection((string) $progress->toArray()['bot'])
                    ->mode(Mode::CURL)
                    ->withoutAntiFlood()
                    ->sendMessage($chatId, static::report($progress));
            } catch (Throwable $e) {
                report($e);
            }
        }
    }

    /**
     * Build the completion report text.
     *
     * @param  \LaraGram\Broadcasting\Telegram\Progress  $progress
     * @return string
     */
    public static function report(Progress $progress): string
    {
        $data = $progress->toArray();

        $duration = $data['started_at'] && $data['finished_at'] ? $data['finished_at'] - $data['started_at'] : 0;

        return implode("\n", [
            "📣 Broadcast {$data['id']} {$data['status']}",
            '',
            "Recipients: {$data['total']}",
            "Sent: {$data['sent']}",
            "Unreachable: {$data['unreachable']}",
            "Failed: {$data['failed']}",
            "Skipped: {$data['skipped']}",
            'Duration: '.gmdate($duration >= 3600 ? 'G\h i\m s\s' : 'i\m s\s', $duration),
        ]);
    }

    /**
     * Get the display name for the queued job.
     *
     * @return string
     */
    public function displayName()
    {
        return static::class.' ['.implode(', ', array_map(fn ($step) => $step->method, $this->steps)).']';
    }

    /**
     * Get the tags for the queued job.
     *
     * @return array<int, string>
     */
    public function tags()
    {
        return ['broadcast', 'broadcast:'.$this->id];
    }
}
