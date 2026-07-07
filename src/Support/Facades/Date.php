<?php

namespace LaraGram\Support\Facades;

use LaraGram\Support\DateFactory;

/**
 * @see https://Tempora.nesbot.com/docs/
 * @see https://github.com/briannesbitt/Tempora/blob/master/src/Tempora/Factory.php
 *
 * @method static mixed use(mixed $handler)
 * @method static void useDefault()
 * @method static void useCallable(callable $callable)
 * @method static void useClass(string $dateClass)
 * @method static void useFactory(object $factory)
 * @method static bool canBeCreatedFromFormat(?string $date, string $format)
 * @method static \LaraGram\Support\Tempora|null create($year = 0, $month = 1, $day = 1, $hour = 0, $minute = 0, $second = 0, $timezone = null)
 * @method static \LaraGram\Support\Tempora createFromDate($year = null, $month = null, $day = null, $timezone = null)
 * @method static \LaraGram\Support\Tempora|null createFromFormat($format, $time, $timezone = null)
 * @method static \LaraGram\Support\Tempora|null createFromIsoFormat(string $format, string $time, $timezone = null, ?string $locale = 'en', ?\LaraGram\Tempora\Translation\TranslatorInterface $translator = null)
 * @method static \LaraGram\Support\Tempora|null createFromLocaleFormat(string $format, string $locale, string $time, $timezone = null)
 * @method static \LaraGram\Support\Tempora|null createFromLocaleIsoFormat(string $format, string $locale, string $time, $timezone = null)
 * @method static \LaraGram\Support\Tempora createFromTime($hour = 0, $minute = 0, $second = 0, $timezone = null)
 * @method static \LaraGram\Support\Tempora createFromTimeString(string $time, \DateTimeZone|string|int|null $timezone = null)
 * @method static \LaraGram\Support\Tempora createFromTimestamp(string|int|float $timestamp, \DateTimeZone|string|int|null $timezone = null)
 * @method static \LaraGram\Support\Tempora createFromTimestampMs(string|int|float $timestamp, \DateTimeZone|string|int|null $timezone = null)
 * @method static \LaraGram\Support\Tempora createFromTimestampMsUTC($timestamp)
 * @method static \LaraGram\Support\Tempora createFromTimestampUTC(string|int|float $timestamp)
 * @method static \LaraGram\Support\Tempora createMidnightDate($year = null, $month = null, $day = null, $timezone = null)
 * @method static \LaraGram\Support\Tempora|null createSafe($year = null, $month = null, $day = null, $hour = null, $minute = null, $second = null, $timezone = null)
 * @method static \LaraGram\Support\Tempora createStrict(?int $year = 0, ?int $month = 1, ?int $day = 1, ?int $hour = 0, ?int $minute = 0, ?int $second = 0, $timezone = null)
 * @method static void disableHumanDiffOption($humanDiffOption)
 * @method static void enableHumanDiffOption($humanDiffOption)
 * @method static mixed executeWithLocale(string $locale, callable $func)
 * @method static \LaraGram\Support\Tempora fromSerialized($value)
 * @method static array getAvailableLocales()
 * @method static array getAvailableLocalesInfo()
 * @method static array getDays()
 * @method static ?string getFallbackLocale()
 * @method static array getFormatsToIsoReplacements()
 * @method static int getHumanDiffOptions()
 * @method static array getIsoUnits()
 * @method static array|false getLastErrors()
 * @method static string getLocale()
 * @method static int getMidDayAt()
 * @method static string getTimeFormatByPrecision(string $unitPrecision)
 * @method static string|\Closure|null getTranslationMessageWith($translator, string $key, ?string $locale = null, ?string $default = null)
 * @method static \LaraGram\Support\Tempora|null getTestNow()
 * @method static \LaraGram\Tempora\Translation\TranslatorInterface getTranslator()
 * @method static int getWeekEndsAt(?string $locale = null)
 * @method static int getWeekStartsAt(?string $locale = null)
 * @method static array getWeekendDays()
 * @method static bool hasFormat(string $date, string $format)
 * @method static bool hasFormatWithModifiers(string $date, string $format)
 * @method static bool hasMacro($name)
 * @method static bool hasRelativeKeywords(?string $time)
 * @method static bool hasTestNow()
 * @method static \LaraGram\Support\Tempora instance(\DateTimeInterface $date)
 * @method static bool isImmutable()
 * @method static bool isModifiableUnit($unit)
 * @method static bool isMutable()
 * @method static bool isStrictModeEnabled()
 * @method static bool localeHasDiffOneDayWords(string $locale)
 * @method static bool localeHasDiffSyntax(string $locale)
 * @method static bool localeHasDiffTwoDayWords(string $locale)
 * @method static bool localeHasPeriodSyntax($locale)
 * @method static bool localeHasShortUnits(string $locale)
 * @method static void macro(string $name, ?callable $macro)
 * @method static \LaraGram\Support\Tempora|null make($var, \DateTimeZone|string|null $timezone = null)
 * @method static void mixin(object|string $mixin)
 * @method static \LaraGram\Support\Tempora now(\DateTimeZone|string|int|null $timezone = null)
 * @method static \LaraGram\Support\Tempora parse(\DateTimeInterface|\LaraGram\Tempora\WeekDay|\LaraGram\Tempora\Month|string|int|float|null $time, \DateTimeZone|string|int|null $timezone = null)
 * @method static \LaraGram\Support\Tempora parseFromLocale(string $time, ?string $locale = null, \DateTimeZone|string|int|null $timezone = null)
 * @method static string pluralUnit(string $unit)
 * @method static \LaraGram\Support\Tempora|null rawCreateFromFormat(string $format, string $time, $timezone = null)
 * @method static \LaraGram\Support\Tempora rawParse(\DateTimeInterface|\LaraGram\Tempora\WeekDay|\LaraGram\Tempora\Month|string|int|float|null $time, \DateTimeZone|string|int|null $timezone = null)
 * @method static void resetMonthsOverflow()
 * @method static void resetToStringFormat()
 * @method static void resetYearsOverflow()
 * @method static void serializeUsing($callback)
 * @method static void setFallbackLocale(string $locale)
 * @method static void setHumanDiffOptions($humanDiffOptions)
 * @method static void setLocale(string $locale)
 * @method static void setMidDayAt($hour)
 * @method static void setTestNow(mixed $testNow = null)
 * @method static void setTestNowAndTimezone(mixed $testNow = null, $timezone = null)
 * @method static void setToStringFormat(string|\Closure|null $format)
 * @method static void setTranslator(\LaraGram\Tempora\Translation\TranslatorInterface $translator)
 * @method static void setWeekEndsAt($day)
 * @method static void setWeekStartsAt($day)
 * @method static void setWeekendDays($days)
 * @method static bool shouldOverflowMonths()
 * @method static bool shouldOverflowYears()
 * @method static string singularUnit(string $unit)
 * @method static void sleep(int|float $seconds)
 * @method static \LaraGram\Support\Tempora today(\DateTimeZone|string|int|null $timezone = null)
 * @method static \LaraGram\Support\Tempora tomorrow(\DateTimeZone|string|int|null $timezone = null)
 * @method static string translateTimeString(string $timeString, ?string $from = null, ?string $to = null, int $mode = \LaraGram\Tempora\TemporaInterface::TRANSLATE_ALL)
 * @method static string translateWith(\LaraGram\Tempora\Translation\TranslatorInterface $translator, string $key, array $parameters = [], $number = null)
 * @method static void useMonthsOverflow($monthsOverflow = true)
 * @method static void useStrictMode($strictModeEnabled = true)
 * @method static void useYearsOverflow($yearsOverflow = true)
 * @method static mixed withTestNow(mixed $testNow, callable $callback)
 * @method static static withTimeZone(\DateTimeZone|string|int|null $timezone)
 * @method static \LaraGram\Support\Tempora yesterday(\DateTimeZone|string|int|null $timezone = null)
 *
 * @see \LaraGram\Support\DateFactory
 */
class Date extends Facade
{
    const DEFAULT_FACADE = DateFactory::class;

    /**
     * Get the registered name of the component.
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    protected static function getFacadeAccessor()
    {
        return 'date';
    }

    /**
     * Resolve the facade root instance from the container.
     *
     * @param  string  $name
     * @return mixed
     */
    protected static function resolveFacadeInstance($name)
    {
        if (! isset(static::$resolvedInstance[$name]) && ! isset(static::$app, static::$app[$name])) {
            $class = static::DEFAULT_FACADE;

            static::swap(new $class);
        }

        return parent::resolveFacadeInstance($name);
    }
}
