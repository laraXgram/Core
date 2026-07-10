<?php

namespace LaraGram\Support;

use LaraGram\Tempora\TemporaInterface;
use LaraGram\Tempora\TemporaInterval;
use LaraGram\Support\Defer\DeferredCallback;
use LaraGram\Support\Defer\DeferredCallbackCollection;
use LaraGram\Support\Facades\Date;
use LaraGram\Console\Process\PhpExecutableFinder;

if (! function_exists('LaraGram\Support\defer')) {
    /**
     * Defer execution of the given callback.
     *
     * @param  callable|null  $callback
     * @param  string|null  $name
     * @param  bool  $always
     * @return ($callback is null ? \LaraGram\Support\Defer\DeferredCallbackCollection : \LaraGram\Support\Defer\DeferredCallback)
     */
    function defer(?callable $callback = null, ?string $name = null, bool $always = false): DeferredCallback|DeferredCallbackCollection
    {
        if ($callback === null) {
            return app(DeferredCallbackCollection::class);
        }

        return tap(
            new DeferredCallback($callback, $name, $always),
            fn ($deferred) => app(DeferredCallbackCollection::class)[] = $deferred
        );
    }
}

if (! function_exists('LaraGram\Support\php_binary')) {
    /**
     * Determine the PHP Binary.
     */
    function php_binary(): string
    {
        return (new PhpExecutableFinder)->find(false) ?: 'php';
    }
}

if (! function_exists('LaraGram\Support\commander_binary')) {
    /**
     * Determine the proper Commander executable.
     */
    function commander_binary(): string
    {
        return defined('COMMANDER_BINARY') ? COMMANDER_BINARY : 'laragram';
    }
}

// Time functions...

if (! function_exists('LaraGram\Support\now')) {
    /**
     * Create a new Tempora instance for the current time.
     *
     * @param  \DateTimeZone|\UnitEnum|string|null  $tz
     * @return \LaraGram\Support\Tempora
     */
    function now($tz = null): TemporaInterface
    {
        return Date::now(enum_value($tz));
    }
}

if (! function_exists('LaraGram\Support\microseconds')) {
    /**
     * Get the current date / time plus the given number of microseconds.
     */
    function microseconds(int|float $microseconds): TemporaInterval
    {
        return TemporaInterval::microseconds($microseconds);
    }
}

if (! function_exists('LaraGram\Support\milliseconds')) {
    /**
     * Get the current date / time plus the given number of milliseconds.
     */
    function milliseconds(int|float $milliseconds): TemporaInterval
    {
        return TemporaInterval::milliseconds($milliseconds);
    }
}

if (! function_exists('LaraGram\Support\seconds')) {
    /**
     * Get the current date / time plus the given number of seconds.
     */
    function seconds(int|float $seconds): TemporaInterval
    {
        return TemporaInterval::seconds($seconds);
    }
}

if (! function_exists('LaraGram\Support\minutes')) {
    /**
     * Get the current date / time plus the given number of minutes.
     */
    function minutes(int|float $minutes): TemporaInterval
    {
        return TemporaInterval::minutes($minutes);
    }
}

if (! function_exists('LaraGram\Support\hours')) {
    /**
     * Get the current date / time plus the given number of hours.
     */
    function hours(int|float $hours): TemporaInterval
    {
        return TemporaInterval::hours($hours);
    }
}

if (! function_exists('LaraGram\Support\days')) {
    /**
     * Get the current date / time plus the given number of days.
     */
    function days(int|float $days): TemporaInterval
    {
        return TemporaInterval::days($days);
    }
}

if (! function_exists('LaraGram\Support\weeks')) {
    /**
     * Get the current date / time plus the given number of weeks.
     */
    function weeks(int $weeks): TemporaInterval
    {
        return TemporaInterval::weeks($weeks);
    }
}

if (! function_exists('LaraGram\Support\months')) {
    /**
     * Get the current date / time plus the given number of months.
     */
    function months(int $months): TemporaInterval
    {
        return TemporaInterval::months($months);
    }
}

if (! function_exists('LaraGram\Support\years')) {
    /**
     * Get the current date / time plus the given number of years.
     */
    function years(int $years): TemporaInterval
    {
        return TemporaInterval::years($years);
    }
}
