<?php

namespace LaraGram\Support\Facades;

use LaraGram\Console\Scheduling\Schedule as ConsoleSchedule;

/**
 * @method static \LaraGram\Console\Scheduling\CallbackEvent call(string|callable $callback, array $parameters = [])
 * @method static \LaraGram\Console\Scheduling\Event command(string $command, array $parameters = [])
 * @method static \LaraGram\Console\Scheduling\CallbackEvent job(object|string $job, string|null $queue = null, string|null $connection = null)
 * @method static \LaraGram\Console\Scheduling\Event exec(string $command, array $parameters = [])
 * @method static void group(\Closure $events)
 * @method static string compileArrayInput(string|int $key, array $value)
 * @method static bool serverShouldRun(\LaraGram\Console\Scheduling\Event $event, \DateTimeInterface $time)
 * @method static \LaraGram\Support\Collection dueEvents(\LaraGram\Contracts\Foundation\Application $app)
 * @method static \LaraGram\Console\Scheduling\Event[] events()
 * @method static \LaraGram\Console\Scheduling\Schedule useCache(string $store)
 * @method static void macro(string $name, object|callable $macro)
 * @method static void mixin(object $mixin, bool $replace = true)
 * @method static bool hasMacro(string $name)
 * @method static void flushMacros()
 * @method static mixed macroCall(string $method, array $parameters)
 * @method static \LaraGram\Console\Scheduling\PendingEventAttributes withoutOverlapping(int $expiresAt = 1440)
 * @method static void mergeAttributes(\LaraGram\Console\Scheduling\Event $event)
 * @method static \LaraGram\Console\Scheduling\PendingEventAttributes user(string $user)
 * @method static \LaraGram\Console\Scheduling\PendingEventAttributes environments(array|mixed $environments)
 * @method static \LaraGram\Console\Scheduling\PendingEventAttributes evenInMaintenanceMode()
 * @method static \LaraGram\Console\Scheduling\PendingEventAttributes onOneServer()
 * @method static \LaraGram\Console\Scheduling\PendingEventAttributes runInBackground()
 * @method static \LaraGram\Console\Scheduling\PendingEventAttributes when(\Closure|bool $callback)
 * @method static \LaraGram\Console\Scheduling\PendingEventAttributes skip(\Closure|bool $callback)
 * @method static \LaraGram\Console\Scheduling\PendingEventAttributes name(string $description)
 * @method static \LaraGram\Console\Scheduling\PendingEventAttributes description(string $description)
 * @method static \LaraGram\Console\Scheduling\PendingEventAttributes cron(string $expression)
 * @method static \LaraGram\Console\Scheduling\PendingEventAttributes between(string $startTime, string $endTime)
 * @method static \LaraGram\Console\Scheduling\PendingEventAttributes unlessBetween(string $startTime, string $endTime)
 * @method static \LaraGram\Console\Scheduling\PendingEventAttributes everySecond()
 * @method static \LaraGram\Console\Scheduling\PendingEventAttributes everyTwoSeconds()
 * @method static \LaraGram\Console\Scheduling\PendingEventAttributes everyFiveSeconds()
 * @method static \LaraGram\Console\Scheduling\PendingEventAttributes everyTenSeconds()
 * @method static \LaraGram\Console\Scheduling\PendingEventAttributes everyFifteenSeconds()
 * @method static \LaraGram\Console\Scheduling\PendingEventAttributes everyTwentySeconds()
 * @method static \LaraGram\Console\Scheduling\PendingEventAttributes everyThirtySeconds()
 * @method static \LaraGram\Console\Scheduling\PendingEventAttributes everyMinute()
 * @method static \LaraGram\Console\Scheduling\PendingEventAttributes everyTwoMinutes()
 * @method static \LaraGram\Console\Scheduling\PendingEventAttributes everyThreeMinutes()
 * @method static \LaraGram\Console\Scheduling\PendingEventAttributes everyFourMinutes()
 * @method static \LaraGram\Console\Scheduling\PendingEventAttributes everyFiveMinutes()
 * @method static \LaraGram\Console\Scheduling\PendingEventAttributes everyTenMinutes()
 * @method static \LaraGram\Console\Scheduling\PendingEventAttributes everyFifteenMinutes()
 * @method static \LaraGram\Console\Scheduling\PendingEventAttributes everyThirtyMinutes()
 * @method static \LaraGram\Console\Scheduling\PendingEventAttributes hourly()
 * @method static \LaraGram\Console\Scheduling\PendingEventAttributes hourlyAt(array|string|int|int[] $offset)
 * @method static \LaraGram\Console\Scheduling\PendingEventAttributes everyOddHour(array|string|int $offset = 0)
 * @method static \LaraGram\Console\Scheduling\PendingEventAttributes everyTwoHours(array|string|int $offset = 0)
 * @method static \LaraGram\Console\Scheduling\PendingEventAttributes everyThreeHours(array|string|int $offset = 0)
 * @method static \LaraGram\Console\Scheduling\PendingEventAttributes everyFourHours(array|string|int $offset = 0)
 * @method static \LaraGram\Console\Scheduling\PendingEventAttributes everySixHours(array|string|int $offset = 0)
 * @method static \LaraGram\Console\Scheduling\PendingEventAttributes daily()
 * @method static \LaraGram\Console\Scheduling\PendingEventAttributes at(string $time)
 * @method static \LaraGram\Console\Scheduling\PendingEventAttributes dailyAt(string $time)
 * @method static \LaraGram\Console\Scheduling\PendingEventAttributes twiceDaily(int $first = 1, int $second = 13)
 * @method static \LaraGram\Console\Scheduling\PendingEventAttributes twiceDailyAt(int $first = 1, int $second = 13, int $offset = 0)
 * @method static \LaraGram\Console\Scheduling\PendingEventAttributes weekdays()
 * @method static \LaraGram\Console\Scheduling\PendingEventAttributes weekends()
 * @method static \LaraGram\Console\Scheduling\PendingEventAttributes mondays()
 * @method static \LaraGram\Console\Scheduling\PendingEventAttributes tuesdays()
 * @method static \LaraGram\Console\Scheduling\PendingEventAttributes wednesdays()
 * @method static \LaraGram\Console\Scheduling\PendingEventAttributes thursdays()
 * @method static \LaraGram\Console\Scheduling\PendingEventAttributes fridays()
 * @method static \LaraGram\Console\Scheduling\PendingEventAttributes saturdays()
 * @method static \LaraGram\Console\Scheduling\PendingEventAttributes sundays()
 * @method static \LaraGram\Console\Scheduling\PendingEventAttributes weekly()
 * @method static \LaraGram\Console\Scheduling\PendingEventAttributes weeklyOn(array|mixed $dayOfWeek, string $time = '0:0')
 * @method static \LaraGram\Console\Scheduling\PendingEventAttributes monthly()
 * @method static \LaraGram\Console\Scheduling\PendingEventAttributes monthlyOn(int $dayOfMonth = 1, string $time = '0:0')
 * @method static \LaraGram\Console\Scheduling\PendingEventAttributes twiceMonthly(int $first = 1, int $second = 16, string $time = '0:0')
 * @method static \LaraGram\Console\Scheduling\PendingEventAttributes lastDayOfMonth(string $time = '0:0')
 * @method static \LaraGram\Console\Scheduling\PendingEventAttributes quarterly()
 * @method static \LaraGram\Console\Scheduling\PendingEventAttributes quarterlyOn(int $dayOfQuarter = 1, string $time = '0:0')
 * @method static \LaraGram\Console\Scheduling\PendingEventAttributes yearly()
 * @method static \LaraGram\Console\Scheduling\PendingEventAttributes yearlyOn(int $month = 1, int|string $dayOfMonth = 1, string $time = '0:0')
 * @method static \LaraGram\Console\Scheduling\PendingEventAttributes days(array|mixed $days)
 * @method static \LaraGram\Console\Scheduling\PendingEventAttributes timezone(\DateTimeZone|string $timezone)
 *
 * @see \LaraGram\Console\Scheduling\Schedule
 */
class Schedule extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return ConsoleSchedule::class;
    }
}
