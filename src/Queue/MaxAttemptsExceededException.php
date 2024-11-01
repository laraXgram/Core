<?php

namespace LaraGram\Queue;

use RuntimeException;

class MaxAttemptsExceededException extends RuntimeException
{
    public $job;

    public static function forJob($job)
    {
        $instance = new static($job->resolveName() . ' has been attempted too many times.');
        $instance->job = $job;

        return $instance;
    }
}
