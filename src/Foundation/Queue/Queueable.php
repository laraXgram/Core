<?php

namespace LaraGram\Foundation\Queue;

use LaraGram\Bus\Queueable as QueueableByBus;
use LaraGram\Foundation\Bus\Dispatchable;
use LaraGram\Queue\InteractsWithQueue;
use LaraGram\Queue\SerializesModels;

trait Queueable
{
    use Dispatchable, InteractsWithQueue, QueueableByBus, SerializesModels;
}
