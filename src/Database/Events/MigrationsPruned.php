<?php

namespace LaraGram\Database\Events;

use LaraGram\Database\Connection;

class MigrationsPruned
{
    /**
     * The database connection instance.
     *
     * @var \LaraGram\Database\Connection
     */
    public $connection;

    /**
     * The database connection name.
     *
     * @var string|null
     */
    public $connectionName;

    /**
     * The path to the directory where migrations were pruned.
     *
     * @var string
     */
    public $path;

    /**
     * Create a new event instance.
     *
     * @param  \LaraGram\Database\Connection  $connection
     * @param  string  $path
     * @return void
     */
    public function __construct(Connection $connection, string $path)
    {
        $this->connection = $connection;
        $this->connectionName = $connection->getName();
        $this->path = $path;
    }
}
