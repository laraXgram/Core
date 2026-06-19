<?php

namespace LaraGram\Auth\Status;

use LaraGram\Contracts\Auth\StatusProvider;
use LaraGram\Database\ConnectionInterface;

class DatabaseStatusProvider implements StatusProvider
{
    /**
     * The active database connection.
     *
     * @var \LaraGram\Database\ConnectionInterface
     */
    protected $connection;

    /**
     * The table holding the statuses.
     *
     * @var string
     */
    protected $table;

    /**
     * The column holding the status value.
     *
     * @var string
     */
    protected $statusColumn;

    /**
     * The column holding the user id.
     *
     * @var string
     */
    protected $userColumn;

    /**
     * The column holding the chat id.
     *
     * @var string
     */
    protected $chatColumn;

    /**
     * Create a new database status provider.
     *
     * @param  \LaraGram\Database\ConnectionInterface  $connection
     * @param  string  $table
     * @param  string  $statusColumn
     * @param  string  $userColumn
     * @param  string  $chatColumn
     * @return void
     */
    public function __construct(ConnectionInterface $connection, $table, $statusColumn = 'status', $userColumn = 'user_id', $chatColumn = 'chat_id')
    {
        $this->connection = $connection;
        $this->table = $table;
        $this->statusColumn = $statusColumn;
        $this->userColumn = $userColumn;
        $this->chatColumn = $chatColumn;
    }

    /**
     * {@inheritdoc}
     */
    public function get($userId, $chatId)
    {
        return $this->connection->table($this->table)
            ->where($this->userColumn, $userId)
            ->where($this->chatColumn, $chatId)
            ->value($this->statusColumn);
    }

    /**
     * {@inheritdoc}
     */
    public function put($userId, $chatId, array $attributes)
    {
        if (array_key_exists('status', $attributes) && $this->statusColumn !== 'status') {
            $attributes[$this->statusColumn] = $attributes['status'];
            unset($attributes['status']);
        }

        $this->connection->table($this->table)->updateOrInsert(
            [$this->userColumn => $userId, $this->chatColumn => $chatId],
            $attributes
        );
    }
}
