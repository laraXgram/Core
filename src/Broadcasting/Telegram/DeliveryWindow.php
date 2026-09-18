<?php

namespace LaraGram\Broadcasting\Telegram;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

class DeliveryWindow
{
    /**
     * Create a new delivery window.
     *
     * @param  string  $from  "HH:MM"
     * @param  string  $to  "HH:MM" (may be earlier than $from for overnight windows)
     * @param  string|null  $timezone
     */
    public function __construct(
        public readonly string $from,
        public readonly string $to,
        public readonly ?string $timezone = null,
    ) {
        foreach ([$from, $to] as $time) {
            if (! preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $time)) {
                throw new InvalidArgumentException("Invalid delivery window time [{$time}], expected HH:MM.");
            }
        }
    }

    /**
     * Create a delivery window from its array form.
     *
     * @param  array|null  $window
     * @return static|null
     */
    public static function fromArray(?array $window)
    {
        return empty($window) ? null : new static($window['from'], $window['to'], $window['timezone'] ?? null);
    }

    /**
     * Get the array form of the window.
     *
     * @return array{from: string, to: string, timezone: string|null}
     */
    public function toArray(): array
    {
        return ['from' => $this->from, 'to' => $this->to, 'timezone' => $this->timezone];
    }

    /**
     * Determine if deliveries are allowed at the given moment.
     *
     * @param  int|null  $timestamp
     * @return bool
     */
    public function isOpen(?int $timestamp = null): bool
    {
        return $this->secondsUntilOpen($timestamp) === 0;
    }

    /**
     * Get the seconds until deliveries are allowed (0 when the window is open).
     *
     * @param  int|null  $timestamp
     * @return int
     */
    public function secondsUntilOpen(?int $timestamp = null): int
    {
        $now = (new DateTimeImmutable('@'.($timestamp ?? time())))->setTimezone($this->zone());

        $minutes = (int) $now->format('H') * 60 + (int) $now->format('i');
        $from = $this->minutes($this->from);
        $to = $this->minutes($this->to);

        $open = $from <= $to
            ? $minutes >= $from && $minutes < $to
            : $minutes >= $from || $minutes < $to;

        if ($open) {
            return 0;
        }

        $wait = ($from - $minutes + 1440) % 1440;

        return max(1, $wait * 60 - (int) $now->format('s'));
    }

    /**
     * Get the window's timezone.
     *
     * @return \DateTimeZone
     */
    protected function zone(): DateTimeZone
    {
        return new DateTimeZone($this->timezone ?: date_default_timezone_get());
    }

    /**
     * Convert "HH:MM" into minutes after midnight.
     *
     * @param  string  $time
     * @return int
     */
    protected function minutes(string $time): int
    {
        [$hours, $minutes] = array_map('intval', explode(':', $time));

        return $hours * 60 + $minutes;
    }
}
