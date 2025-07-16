<?php

namespace LaraGram\Auth;

use LaraGram\Contracts\Auth\UserProvider;
use LaraGram\Database\ConnectionInterface;

class DatabaseUserProvider implements UserProvider
{
    /**
     * The active database connection.
     *
     * @var \LaraGram\Database\ConnectionInterface
     */
    protected $connection;

    /**
     * The table containing the users.
     *
     * @var string
     */
    protected $table;

    /**
     * The column containing the user_id.
     *
     * @var string
     */
    protected $column;

    /**
     * Create a new database user provider.
     *
     * @param  \LaraGram\Database\ConnectionInterface  $connection
     * @param  string  $table
     * @return void
     */
    public function __construct(ConnectionInterface $connection, $table, $column)
    {
        $this->connection = $connection;
        $this->table = $table;
        $this->column = $column;
    }

    /**
     * Retrieve a user by their unique identifier.
     *
     * @param  mixed  $identifier
     * @return \LaraGram\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByUserId($identifier)
    {
        $user = $this->connection->table($this->table)->find($identifier, $this->column);

        return $this->getGenericUser($user);
    }

    /**
     * Get the generic user.
     *
     * @param  mixed  $user
     * @return \LaraGram\Auth\GenericUser|null
     */
    protected function getGenericUser($user)
    {
        if (! is_null($user)) {
            return new GenericUser((array) $user);
        }
    }
}
