<?php

namespace LaraGram\Database;

use LaraGram\Support\Str;
use PDOException;
use Throwable;

class QueryException extends PDOException
{
    /**
     * The database connection name.
     *
     * @var string
     */
    public $connectionName;

    /**
     * The SQL for the query.
     *
     * @var string
     */
    protected $sql;

    /**
     * The bindings for the query.
     *
     * @var array
     */
    protected $bindings;

    /**
     * Create a new query exception instance.
     *
     * @param  string  $connectionName
     * @param  string  $sql
     * @param  array  $bindings
     * @param  \Throwable  $previous
     * @return void
     */
    public function __construct($connectionName, $sql, array $bindings, Throwable $previous)
    {
        parent::__construct('', 0, $previous);

        $this->connectionName = $connectionName;
        $this->sql = $sql;
        $this->bindings = $bindings;
        $this->code = $previous->getCode();
        $this->message = $this->formatMessage($connectionName, $sql, $bindings, $previous);

        if ($previous instanceof PDOException) {
            $this->errorInfo = $previous->errorInfo;
        }
    }

    /**
     * Format the SQL error message.
     *
     * @param  string  $connectionName
     * @param  string  $sql
     * @param  array  $bindings
     * @param  \Throwable  $previous
     * @return string
     */
    protected function formatMessage($connectionName, $sql, $bindings, Throwable $previous)
    {
        return $previous->getMessage().' (Connection: '.$connectionName.', SQL: '.Str::replaceArray('?', $bindings, $sql).')';
    }

    /**
     * Get the connection name for the query.
     *
     * @return string
     */
    public function getConnectionName()
    {
        return $this->connectionName;
    }

    /**
     * Get the SQL for the query.
     *
     * @return string
     */
    public function getSql()
    {
        return $this->sql;
    }

    /**
     * Get the bindings for the query.
     *
     * @return array
     */
    public function getBindings()
    {
        return $this->bindings;
    }
}
