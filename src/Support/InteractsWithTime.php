<?php

namespace LaraGram\Support;

use DateInterval;
use DateTime;
use DateTimeInterface;

trait InteractsWithTime
{
    /**
     * Get the number of seconds until the given DateTime.
     *
     * @param  DateTimeInterface|DateInterval|int  $delay
     * @return int
     */
    protected function secondsUntil($delay)
    {
        $delay = $this->parseDateInterval($delay);

        return $delay instanceof DateTimeInterface
            ? max(0, $delay->getTimestamp() - $this->getCurrentTime())
            : (int) $delay;
    }

    /**
     * Get the "available at" UNIX timestamp.
     *
     * @param  DateTimeInterface|DateInterval|int  $delay
     * @return int
     */
    protected function availableAt($delay = 0)
    {
        $delay = $this->parseDateInterval($delay);

        return $delay instanceof DateTimeInterface
            ? $delay->getTimestamp()
            : $this->getCurrentTime() + (int)$delay;
    }

    /**
     * If the given value is an interval, convert it to a DateTime instance.
     *
     * @param  DateTimeInterface|DateInterval|int  $delay
     * @return DateTimeInterface|int
     */
    protected function parseDateInterval($delay)
    {
        if ($delay instanceof DateInterval) {
            $dateTime = new DateTime();
            $dateTime->add($delay);
            return $dateTime;
        }

        return $delay;
    }

    /**
     * Get the current system time as a UNIX timestamp.
     *
     * @return int
     */
    protected function getCurrentTime()
    {
        return (new DateTime())->getTimestamp();
    }
}
