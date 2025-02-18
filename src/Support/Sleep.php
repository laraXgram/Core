<?php

namespace LaraGram\Support;

use Closure;
use DateInterval;
use DateTime;
use DateTimeZone;
use LaraGram\Support\Traits\Macroable;
use RuntimeException;

class Sleep
{
    use Macroable;

    /**
     * The fake sleep callbacks.
     *
     * @var array
     */
    public static $fakeSleepCallbacks = [];

    /**
     * Keep Carbon's "now" in sync when sleeping.
     *
     * @var bool
     */
    protected static $syncWithCarbon = false;

    /**
     * The total duration to sleep.
     *
     * @var \DateInterval
     */
    public $duration;

    /**
     * The callback that determines if sleeping should continue.
     *
     * @var \Closure
     */
    public $while;

    /**
     * The pending duration to sleep.
     *
     * @var int|float|null
     */
    protected $pending = null;

    /**
     * Indicates that all sleeping should be faked.
     *
     * @var bool
     */
    protected static $fake = false;

    /**
     * The sequence of sleep durations encountered while faking.
     *
     * @var array<int, \DateInterval>
     */
    protected static $sequence = [];

    /**
     * Indicates if the instance should sleep.
     *
     * @var bool
     */
    protected $shouldSleep = true;

    /**
     * Indicates if the instance already slept via `then()`.
     *
     * @var bool
     */
    protected $alreadySlept = false;

    /**
     * Create a new class instance.
     *
     * @param  int|float|\DateInterval  $duration
     * @return void
     */
    public function __construct($duration)
    {
        $this->duration($duration);
    }

    /**
     * Sleep for the given duration.
     *
     * @param  \DateInterval|int|float  $duration
     * @return static
     */
    public static function for($duration)
    {
        return new static($duration);
    }

    /**
     * Sleep until the given timestamp.
     *
     * @param  \DateTimeInterface|int|float|numeric-string  $timestamp
     * @return static
     */
    public static function until($timestamp)
    {
        if (is_numeric($timestamp)) {
            $timestamp = DateTime::createFromFormat('U', $timestamp, new DateTimeZone(date_default_timezone_get()));
        }

        return new static((new DateTime())->diff($timestamp));
    }

    /**
     * Sleep for the given number of microseconds.
     *
     * @param  int  $duration
     * @return static
     */
    public static function usleep($duration)
    {
        return (new static($duration))->microseconds();
    }

    /**
     * Sleep for the given number of seconds.
     *
     * @param  int|float  $duration
     * @return static
     */
    public static function sleep($duration)
    {
        return (new static($duration))->seconds();
    }

    /**
     * Sleep for the given duration. Replaces any previously defined duration.
     *
     * @param  \DateInterval|int|float  $duration
     * @return $this
     */
    protected function duration($duration)
    {
        if (! $duration instanceof DateInterval) {
            $this->duration = new DateInterval('PT0S');

            $this->pending = $duration;
        } else {
            if ($duration->invert) {
                $duration = new DateInterval('PT0S');
            }

            $this->duration = $duration;
            $this->pending = null;
        }

        return $this;
    }

    /**
     * Sleep for the given number of minutes.
     *
     * @return $this
     */
    public function minutes()
    {
        $pendingMinutes = $this->pullPending();

        if ($pendingMinutes) {
            $dateTime = new DateTime();
            $dateTime->add(new DateInterval("PT{$pendingMinutes}M"));
            $this->duration = $dateTime->diff(new DateTime('0000-01-01 00:00:00'));
        }

        return $this;
    }

    /**
     * Sleep for one minute.
     *
     * @return $this
     */
    public function minute()
    {
        return $this->minutes();
    }

    /**
     * Sleep for the given number of seconds.
     *
     * @return $this
     */
    public function seconds()
    {
        $pendingSeconds = $this->pullPending();

        if ($pendingSeconds) {
            $dateTime = new DateTime();
            $dateTime->add(new DateInterval("PT{$pendingSeconds}S"));
            $this->duration = $dateTime->diff(new DateTime('0000-01-01 00:00:00'));
        }

        return $this;
    }

    /**
     * Sleep for one second.
     *
     * @return $this
     */
    public function second()
    {
        return $this->seconds();
    }

    /**
     * Sleep for the given number of milliseconds.
     *
     * @return $this
     */
    public function milliseconds()
    {
        $pendingMilliseconds = $this->pullPending();

        if ($pendingMilliseconds) {
            $dateTime = new DateTime();
            $dateTime->add(new DateInterval("PT" . ($pendingMilliseconds / 1000) . "S"));
            $this->duration = $dateTime->diff(new DateTime('0000-01-01 00:00:00'));
        }

        return $this;
    }

    /**
     * Sleep for one millisecond.
     *
     * @return $this
     */
    public function millisecond()
    {
        return $this->milliseconds();
    }

    /**
     * Sleep for the given number of microseconds.
     *
     * @return $this
     */
    public function microseconds()
    {
        $pendingMicroseconds = $this->pullPending();

        if ($pendingMicroseconds) {
            $dateTime = new DateTime();
            $dateTime->add(new DateInterval("PT" . ($pendingMicroseconds / 1000000) . "S"));
            $this->duration = $dateTime->diff(new DateTime('0000-01-01 00:00:00'));
        }

        return $this;
    }

    /**
     * Sleep for on microsecond.
     *
     * @return $this
     */
    public function microsecond()
    {
        return $this->microseconds();
    }

    /**
     * Add additional time to sleep for.
     *
     * @param  int|float  $duration
     * @return $this
     */
    public function and($duration)
    {
        $this->pending = $duration;

        return $this;
    }

    /**
     * Sleep while a given callback returns "true".
     *
     * @param  \Closure  $callback
     * @return $this
     */
    public function while(Closure $callback)
    {
        $this->while = $callback;

        return $this;
    }

    /**
     * Specify a callback that should be executed after sleeping.
     *
     * @param  callable  $then
     * @return mixed
     */
    public function then(callable $then)
    {
        $this->goodnight();

        $this->alreadySlept = true;

        return $then();
    }

    /**
     * Handle the object's destruction.
     *
     * @return void
     */
    public function __destruct()
    {
        $this->goodnight();
    }

    /**
     * Handle the object's destruction.
     *
     * @return void
     */
    protected function goodnight()
    {
        if ($this->alreadySlept || ! $this->shouldSleep) {
            return;
        }

        if ($this->pending !== null) {
            throw new RuntimeException('Unknown duration unit.');
        }

        if (static::$fake) {
            static::$sequence[] = $this->duration;

            if (static::$syncWithCarbon) {
                static::$testNow = (new DateTime())->add($this->duration);
            }

            foreach (static::$fakeSleepCallbacks as $callback) {
                $callback($this->duration);
            }

            return;
        }

        $remaining = clone $this->duration;

        $seconds = (int) $remaining->s;

        $while = $this->while ?: function () {
            static $return = [true, false];

            return array_shift($return);
        };

        while ($while()) {
            if ($seconds > 0) {
                sleep($seconds);
                $remaining->s = max(0, $remaining->s - $seconds);
            }

            $microseconds = (int) ($remaining->f * 1000000);

            if ($microseconds > 0) {
                usleep($microseconds);
            }
        }
    }

    /**
     * Resolve the pending duration.
     *
     * @return HigherOrderTapProxy|int
     */
    protected function pullPending()
    {
        if ($this->pending === null) {
            $this->shouldNotSleep();

            throw new RuntimeException('No duration specified.');
        }

        if ($this->pending < 0) {
            $this->pending = 0;
        }

        return tap($this->pending, function () {
            $this->pending = null;
        });
    }

    /**
     * Stay awake and capture any attempts to sleep.
     *
     * @param  bool  $value
     * @param  bool  $syncWithCarbon
     * @return void
     */
    public static function fake($value = true, $syncWithCarbon = false)
    {
        static::$fake = $value;

        static::$sequence = [];
        static::$fakeSleepCallbacks = [];
        static::$syncWithCarbon = $syncWithCarbon;
    }

    /**
     * Indicate that the instance should not sleep.
     *
     * @return $this
     */
    protected function shouldNotSleep()
    {
        $this->shouldSleep = false;

        return $this;
    }

    /**
     * Only sleep when the given condition is true.
     *
     * @param  (\Closure($this): bool)|bool  $condition
     * @return $this
     */
    public function when($condition)
    {
        $this->shouldSleep = (bool) value($condition, $this);

        return $this;
    }

    /**
     * Don't sleep when the given condition is true.
     *
     * @param  (\Closure($this): bool)|bool  $condition
     * @return $this
     */
    public function unless($condition)
    {
        return $this->when(! value($condition, $this));
    }

    /**
     * Specify a callback that should be invoked when faking sleep within a test.
     *
     * @param  callable  $callback
     * @return void
     */
    public static function whenFakingSleep($callback)
    {
        static::$fakeSleepCallbacks[] = $callback;
    }

    /**
     * Indicate that Carbon's "now" should be kept in sync when sleeping.
     *
     * @return void
     */
    public static function syncWithCarbon($value = true)
    {
        static::$syncWithCarbon = $value;
    }
}
