<?php

namespace LaraGram\Queue\Connectors;

use LaraGram\Contracts\Events\Dispatcher;
use LaraGram\Queue\FailoverQueue;
use LaraGram\Queue\QueueManager;

class FailoverConnector implements ConnectorInterface
{
    /**
     * Create a new connector instance.
     */
    public function __construct(
        protected QueueManager $manager,
        protected Dispatcher $events
    ) {
    }

    /**
     * Establish a queue connection.
     *
     * @return \LaraGram\Contracts\Queue\Queue
     */
    public function connect(array $config)
    {
        return new FailoverQueue(
            $this->manager,
            $this->events,
            $config['connections'],
        );
    }
}
