<?php

namespace LaraGram\Support\Queue\Concerns;

use LaraGram\Container\Container;
use LaraGram\Queue\QueueRoutes;

trait ResolvesQueueRoutes
{
    /**
     * Resolve the default connection name for a given queueable instance.
     *
     * @param  object  $queueable
     * @return string|null
     */
    public function resolveConnectionFromQueueRoute($queueable)
    {
        return $this->queueRoutes()->getConnection($queueable);
    }

    /**
     * Resolve the default queue name for a given queueable instance.
     *
     * @param  object  $queueable
     * @return string|null
     */
    public function resolveQueueFromQueueRoute($queueable)
    {
        return $this->queueRoutes()->getQueue($queueable);
    }

    /**
     * Get the queue routes manager instance.
     *
     * @return \LaraGram\Queue\QueueRoutes
     */
    protected function queueRoutes()
    {
        $container = Container::getInstance();

        return $container->bound('queue.routes')
            ? $container->make('queue.routes')
            : new QueueRoutes;
    }
}
