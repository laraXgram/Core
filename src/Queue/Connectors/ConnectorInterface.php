<?php

namespace LaraGram\Queue\Connectors;

interface ConnectorInterface
{
    /**
     * Establish a queue connection.
     *
     * @param  array  $config
     * @return \LaraGram\Contracts\Queue\Queue
     */
    public function connect(array $config);
}
