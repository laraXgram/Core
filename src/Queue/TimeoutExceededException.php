<?php

namespace LaraGram\Queue;

class TimeoutExceededException extends MaxAttemptsExceededException
{
    public static function forJob($job)
    {
        $instance = new static($job->resolveName() . ' has timed out.');
        $instance->job = $job;

        return $instance;
    }
}
