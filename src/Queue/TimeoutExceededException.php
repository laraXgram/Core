<?php

namespace LaraGram\Queue;

class TimeoutExceededException extends MaxAttemptsExceededException
{
    /**
     * Create a new instance for the job.
     *
     * @param  \LaraGram\Contracts\Queue\Job  $job
     * @return \LaraGram\Support\HigherOrderTapProxy
     */
    public static function forJob($job)
    {
        return tap(new static($job->resolveName().' has timed out.'), function ($e) use ($job) {
            $e->job = $job;
        });
    }
}
