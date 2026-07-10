<?php

namespace LaraGram\Database;

use Exception;
use LaraGram\Database\Query\Grammars\SQLiteGrammar as QueryGrammar;
use LaraGram\Database\Query\Processors\SQLiteProcessor;
use LaraGram\Database\Schema\Grammars\SQLiteGrammar as SchemaGrammar;
use LaraGram\Database\Schema\SQLiteBuilder;
use LaraGram\Database\Schema\SqliteSchemaState;
use LaraGram\Filesystem\Filesystem;

class SQLiteConnection extends Connection
{
    /**
     * {@inheritdoc}
     */
    public function getDriverTitle()
    {
        return 'SQLite';
    }

    /**
     * Run the statement to start a new transaction.
     *
     * @return void
     */
    protected function executeBeginTransactionStatement()
    {
        if (version_compare(PHP_VERSION, '8.4.0', '>=')) {
            $mode = $this->getConfig('transaction_mode') ?? 'DEFERRED';

            $this->getPdo()->exec("BEGIN {$mode} TRANSACTION");

            return;
        }

        $this->getPdo()->beginTransaction();
    }

    /**
     * Escape a binary value for safe SQL embedding.
     *
     * @param  string  $value
     * @return string
     */
    protected function escapeBinary($value)
    {
        $hex = bin2hex($value);

        return "x'{$hex}'";
    }

    /**
     * Determine if the given database exception was caused by a unique constraint violation.
     *
     * @param  \Exception  $exception
     * @return bool
     */
    protected function isUniqueConstraintError(Exception $exception)
    {
        return (bool) preg_match('#(column(s)? .* (is|are) not unique|UNIQUE constraint failed: .*)#i', $exception->getMessage());
    }

    /**
     * Extract the columns that caused a unique constraint violation.
     *
     * @param  Exception  $exception
     * @return array{index: null, columns: list<string>}
     */
    protected function parseUniqueConstraintViolation(Exception $exception): array
    {
        preg_match('#UNIQUE constraint failed: (.+)#i', $exception->getMessage(), $matches);

        $columns = [];

        if (isset($matches[1])) {
            $columns = array_map(
                static fn ($col) => last(explode('.', trim($col))),
                explode(',', $matches[1])
            );
        }

        return ['columns' => $columns, 'index' => null];
    }

    /**
     * Get the default query grammar instance.
     *
     * @return \LaraGram\Database\Query\Grammars\SQLiteGrammar
     */
    protected function getDefaultQueryGrammar()
    {
        return new QueryGrammar($this);
    }

    /**
     * Get a schema builder instance for the connection.
     *
     * @return \LaraGram\Database\Schema\SQLiteBuilder
     */
    public function getSchemaBuilder()
    {
        if (is_null($this->schemaGrammar)) {
            $this->useDefaultSchemaGrammar();
        }

        return new SQLiteBuilder($this);
    }

    /**
     * Get the default schema grammar instance.
     *
     * @return \LaraGram\Database\Schema\Grammars\SQLiteGrammar
     */
    protected function getDefaultSchemaGrammar()
    {
        return new SchemaGrammar($this);
    }

    /**
     * Get the schema state for the connection.
     *
     * @param  \LaraGram\Filesystem\Filesystem|null  $files
     * @param  callable|null  $processFactory
     *
     * @throws \RuntimeException
     */
    public function getSchemaState(?Filesystem $files = null, ?callable $processFactory = null)
    {
        return new SqliteSchemaState($this, $files, $processFactory);
    }

    /**
     * Get the default post processor instance.
     *
     * @return \LaraGram\Database\Query\Processors\SQLiteProcessor
     */
    protected function getDefaultPostProcessor()
    {
        return new SQLiteProcessor;
    }
}
