<?php

namespace LaraGram\Database\Events;

abstract class ConnectionEvent
{
    /**
     * The name of the connection.
     *
     * @var string
     */
    public $connectionName;

    /**
     * The database connection instance.
     *
     * @var \LaraGram\Database\Connection
     */
    public $connection;

    /**
     * Create a new event instance.
     *
     * @param  \LaraGram\Database\Connection  $connection
     * @return void
     */
    public function __construct($connection)
    {
        $this->connection = $connection;
        $this->connectionName = $connection->getName();
    }
}
