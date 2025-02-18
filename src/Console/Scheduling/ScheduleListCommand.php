<?php

namespace LaraGram\Console\Scheduling;

use Closure;
use DateTime;
use DateTimeZone;
use LaraGram\Console\Command;
use LaraGram\Support\Collection;
use ReflectionClass;
use ReflectionFunction;
use LaraGram\Console\Attribute\AsCommand;
use LaraGram\Console\Terminal;

#[AsCommand(name: 'schedule:list')]
class ScheduleListCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'schedule:list
        {--timezone= : The timezone that times should be displayed in}
        {--next : Sort the listed tasks by their next due date}
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all scheduled tasks';

    /**
     * The terminal width resolver callback.
     *
     * @var \Closure|null
     */
    protected static $terminalWidthResolver;

    /**
     * Execute the console command.
     *
     * @param \LaraGram\Console\Scheduling\Schedule $schedule
     * @return void
     *
     * @throws \Exception
     */
    public function handle(Schedule $schedule)
    {
        $events = new Collection($schedule->events());

        if ($events->isEmpty()) {
            $this->components->info('No scheduled tasks have been defined.');

            return;
        }

        $terminalWidth = self::getTerminalWidth();

        $expressionSpacing = $this->getCronExpressionSpacing($events);

        $repeatExpressionSpacing = $this->getRepeatExpressionSpacing($events);

        $timezone = new DateTimeZone($this->option('timezone') ?? config('app.timezone'));

        $events = $this->sortEvents($events, $timezone);

        $events = $events->map(function ($event) use ($terminalWidth, $expressionSpacing, $repeatExpressionSpacing, $timezone) {
            return $this->listEvent($event, $terminalWidth, $expressionSpacing, $repeatExpressionSpacing, $timezone);
        });

        $this->line(
            $events->flatten()->filter()->prepend('')->push('')->toArray()
        );
    }

    /**
     * Get the spacing to be used on each event row.
     *
     * @param \LaraGram\Support\Collection $events
     * @return array<int, int>
     */
    private function getCronExpressionSpacing($events)
    {
        $rows = $events->map(fn($event) => array_map('mb_strlen', preg_split("/\s+/", $event->expression)));

        return (new Collection($rows[0] ?? []))->keys()->map(fn($key) => $rows->max($key))->all();
    }

    /**
     * Get the spacing to be used on each event row.
     *
     * @param \LaraGram\Support\Collection $events
     * @return int
     */
    private function getRepeatExpressionSpacing($events)
    {
        return $events->map(fn($event) => mb_strlen($this->getRepeatExpression($event)))->max();
    }

    private function diffForHumansPure(DateTime $dateTime1, DateTime $dateTime2): string {
        $diff = $dateTime1->diff($dateTime2);

        if ($diff->y > 0) {
            return $diff->y . ' Year ago ';
        } elseif ($diff->m > 0) {
            return $diff->m . ' Month ago';
        } elseif ($diff->d > 0) {
            return $diff->d . ' Day ago';
        } elseif ($diff->h > 0) {
            return $diff->h . ' Hour ago';
        } elseif ($diff->i > 0) {
            return $diff->i . ' Minute ago';
        } else {
            return $diff->s . ' Second ago';
        }
    }

    /**
     * List the given even in the console.
     *
     * @param \LaraGram\Console\Scheduling\Event $event
     * @param int $terminalWidth
     * @param array $expressionSpacing
     * @param int $repeatExpressionSpacing
     * @param \DateTimeZone $timezone
     * @return array
     */
    private function listEvent($event, $terminalWidth, $expressionSpacing, $repeatExpressionSpacing, $timezone)
    {
        $expression = $this->formatCronExpression($event->expression, $expressionSpacing);

        $repeatExpression = str_pad($this->getRepeatExpression($event), $repeatExpressionSpacing);

        $command = $event->command ?? '';

        $description = $event->description ?? '';

        if (!$this->output->isVerbose()) {
            $command = $event->normalizeCommand($command);
        }

        if ($event instanceof CallbackEvent) {
            $command = $event->getSummaryForDisplay();

            if (in_array($command, ['Closure', 'Callback'])) {
                $command = 'Closure at: ' . $this->getClosureLocation($event);
            }
        }

        $command = mb_strlen($command) > 1 ? "{$command} " : '';

        $nextDueDateLabel = 'Next Due:';

        $nextDueDate = $this->getNextDueDateForEvent($event, $timezone);

        $nextDueDate = $this->output->isVerbose()
            ? $nextDueDate->format('Y-m-d H:i:s P')
            : $this->diffForHumansPure($nextDueDate, new DateTime("now", $nextDueDate->getTimezone()));

        $hasMutex = $event->mutex->exists($event) ? 'Has Mutex › ' : '';

        $dots = str_repeat('.', max(
            $terminalWidth - mb_strlen($expression . $repeatExpression . $command . $nextDueDateLabel . $nextDueDate . $hasMutex) - 8, 0
        ));

        $command = preg_replace("#(php laragram [\w\-:]+) (.+)#", '$1 <fg=yellow;options=bold>$2</>', $command);

        return [sprintf(
            '  <fg=yellow>%s</> <fg=#6C7280>%s</> %s<fg=#6C7280>%s %s%s %s</>',
            $expression,
            $repeatExpression,
            $command,
            $dots,
            $hasMutex,
            $nextDueDateLabel,
            $nextDueDate
        ), $this->output->isVerbose() && mb_strlen($description) > 1 ? sprintf(
            '  <fg=#6C7280>%s%s %s</>',
            str_repeat(' ', mb_strlen($expression) + 2),
            '⇁',
            $description
        ) : ''];
    }

    /**
     * Get the repeat expression for an event.
     *
     * @param \LaraGram\Console\Scheduling\Event $event
     * @return string
     */
    private function getRepeatExpression($event)
    {
        return $event->isRepeatable() ? "{$event->repeatSeconds}s " : '';
    }

    /**
     * Sort the events by due date if option set.
     *
     * @param \LaraGram\Support\Collection $events
     * @param \DateTimeZone $timezone
     * @return \LaraGram\Support\Collection
     */
    private function sortEvents(\LaraGram\Support\Collection $events, DateTimeZone $timezone)
    {
        return $this->option('next')
            ? $events->sortBy(fn($event) => $this->getNextDueDateForEvent($event, $timezone))
            : $events;
    }

    private function parseCronField(string $field, int $min, int $max): array
    {
        $results = [];
        if ($field === '*') {
            return range($min, $max);
        }
        foreach (explode(',', $field) as $part) {
            if (strpos($part, '/') !== false) {
                list($range, $step) = explode('/', $part);
                $step = (int)$step;
                if ($range === '*' || $range === '') {
                    $rangeStart = $min;
                    $rangeEnd = $max;
                } elseif (strpos($range, '-') !== false) {
                    list($start, $end) = explode('-', $range);
                    $rangeStart = (int)$start;
                    $rangeEnd = (int)$end;
                } else {
                    $rangeStart = (int)$range;
                    $rangeEnd = $max;
                }
                for ($i = $rangeStart; $i <= $rangeEnd; $i++) {
                    if ((($i - $rangeStart) % $step) === 0) {
                        $results[] = $i;
                    }
                }
            } elseif (strpos($part, '-') !== false) {
                list($start, $end) = explode('-', $part);
                for ($i = (int)$start; $i <= (int)$end; $i++) {
                    $results[] = $i;
                }
            } else {
                $results[] = (int)$part;
            }
        }
        $results = array_unique($results);
        sort($results);
        return $results;
    }

    private function cronMatches(DateTime $dt, array $cronParts): bool
    {
        $minute = (int)$dt->format('i');
        $hour = (int)$dt->format('G');
        $day = (int)$dt->format('j');
        $month = (int)$dt->format('n');
        $weekday = (int)$dt->format('w');

        $validMinutes = $this->parseCronField($cronParts[0], 0, 59);
        $validHours = $this->parseCronField($cronParts[1], 0, 23);
        $validDays = $this->parseCronField($cronParts[2], 1, 31);
        $validMonths = $this->parseCronField($cronParts[3], 1, 12);
        $validWeekdays = $this->parseCronField($cronParts[4], 0, 6);

        if (!in_array($minute, $validMinutes, true)) return false;
        if (!in_array($hour, $validHours, true)) return false;
        if (!in_array($month, $validMonths, true)) return false;

        $dayWildcard = ($cronParts[2] === '*');
        $weekdayWildcard = ($cronParts[4] === '*');

        $domMatch = in_array($day, $validDays, true);
        $dowMatch = in_array($weekday, $validWeekdays, true);

        if (!$dayWildcard && !$weekdayWildcard) {
            if (!($domMatch || $dowMatch)) return false;
        } else {
            if (!$dayWildcard && !$domMatch) return false;
            if (!$weekdayWildcard && !$dowMatch) return false;
        }

        return true;
    }

    private function getNextRunDatePure(string $cronExpression, DateTime $start): DateTime
    {
        $cronParts = preg_split('/\s+/', trim($cronExpression));
        if (count($cronParts) < 5) {
            throw new \Exception("Invalid Cron Expression");
        }
        $dt = clone $start;
        $dt->modify('+1 minute');
        $dt->setTime((int)$dt->format('H'), (int)$dt->format('i'), 0);
        while (true) {
            if ($this->cronMatches($dt, $cronParts)) {
                return $dt;
            }
            $dt->modify('+1 minute');
        }
    }

    function getPreviousRunDatePure(string $cronExpression, DateTime $start, bool $allowCurrent = false): DateTime
    {
        $cronParts = preg_split('/\s+/', trim($cronExpression));
        if (count($cronParts) < 5) {
            throw new \Exception("Invalid Cron Expression");
        }
        $dt = clone $start;
        if (!$allowCurrent) {
            $dt->modify('-1 minute');
        }
        $dt->setTime((int)$dt->format('H'), (int)$dt->format('i'), 0);
        while (true) {
            if ($this->cronMatches($dt, $cronParts)) {
                return $dt;
            }
            $dt->modify('-1 minute');
        }
    }

    private function ceilSecondsPure(DateTime $dt, int $repeatSeconds): DateTime
    {
        $timestamp = $dt->getTimestamp();
        $remainder = $timestamp % $repeatSeconds;
        if ($remainder === 0) {
            return $dt;
        }
        $ceilTimestamp = $timestamp + ($repeatSeconds - $remainder);
        $newDt = clone $dt;
        $newDt->setTimestamp($ceilTimestamp);
        return $newDt;
    }

    /**
     * Get the next due date for an event.
     *
     * @param \LaraGram\Console\Scheduling\Event $event
     * @param \DateTimeZone $timezone
     * @return DateTime
     */
    private function getNextDueDateForEvent($event, DateTimeZone $targetTimezone): DateTime
    {
        $eventTimezone = new DateTimeZone($event->timezone);
        $now = new DateTime("now", $eventTimezone);

        $nextDueDate = $this->getNextRunDatePure($event->expression, $now);
        $nextDueDate->setTimezone($targetTimezone);

        if (!$event->isRepeatable()) {
            return $nextDueDate;
        }

        $previousDueDate = $this->getPreviousRunDatePure($event->expression, $now, true);
        $previousDueDate->setTimezone($targetTimezone);

        $nowStartOfMinute = clone $now;
        $nowStartOfMinute->setTime((int)$now->format('H'), (int)$now->format('i'), 0);

        if ($nowStartOfMinute->getTimestamp() !== $previousDueDate->getTimestamp()) {
            return $nextDueDate;
        }

        $temp = clone $now;
        $temp->modify('+1 second');
        $repeatDue = $this->ceilSecondsPure($temp, $event->repeatSeconds);
        $repeatDue->setTimezone($targetTimezone);

        return $repeatDue;
    }

    /**
     * Format the cron expression based on the spacing provided.
     *
     * @param string $expression
     * @param array<int, int> $spacing
     * @return string
     */
    private function formatCronExpression($expression, $spacing)
    {
        $expressions = preg_split("/\s+/", $expression);

        return (new Collection($spacing))
            ->map(fn($length, $index) => str_pad($expressions[$index], $length))
            ->implode(' ');
    }

    /**
     * Get the file and line number for the event closure.
     *
     * @param \LaraGram\Console\Scheduling\CallbackEvent $event
     * @return string
     */
    private function getClosureLocation(CallbackEvent $event)
    {
        $callback = (new ReflectionClass($event))->getProperty('callback')->getValue($event);

        if ($callback instanceof Closure) {
            $function = new ReflectionFunction($callback);

            return sprintf(
                '%s:%s',
                str_replace($this->laragram->basePath() . DIRECTORY_SEPARATOR, '', $function->getFileName() ?: ''),
                $function->getStartLine()
            );
        }

        if (is_string($callback)) {
            return $callback;
        }

        if (is_array($callback)) {
            $className = is_string($callback[0]) ? $callback[0] : $callback[0]::class;

            return sprintf('%s::%s', $className, $callback[1]);
        }

        return sprintf('%s::__invoke', $callback::class);
    }

    /**
     * Get the terminal width.
     *
     * @return int
     */
    public static function getTerminalWidth()
    {
        return is_null(static::$terminalWidthResolver)
            ? (new Terminal)->getWidth()
            : call_user_func(static::$terminalWidthResolver);
    }

    /**
     * Set a callback that should be used when resolving the terminal width.
     *
     * @param \Closure|null $resolver
     * @return void
     */
    public static function resolveTerminalWidthUsing($resolver)
    {
        static::$terminalWidthResolver = $resolver;
    }
}
