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
     * Create a new database connection instance.
     *
     * @param  \PDO|\Closure  $pdo
     * @param  string  $database
     * @param  string  $tablePrefix
     * @param  array  $config
     * @return void
     */
    public function __construct($pdo, $database = '', $tablePrefix = '', array $config = [])
    {
        parent::__construct($pdo, $database, $tablePrefix, $config);

        $this->configureForeignKeyConstraints();
        $this->configureBusyTimeout();
        $this->configureJournalMode();
        $this->configureSynchronous();
    }

    /**
     * {@inheritdoc}
     */
    public function getDriverTitle()
    {
        return 'SQLite';
    }

    /**
     * Enable or disable foreign key constraints if configured.
     *
     * @return void
     */
    protected function configureForeignKeyConstraints(): void
    {
        $enableForeignKeyConstraints = $this->getConfig('foreign_key_constraints');

        if ($enableForeignKeyConstraints === null) {
            return;
        }

        $schemaBuilder = $this->getSchemaBuilder();

        try {
            $enableForeignKeyConstraints
                ? $schemaBuilder->enableForeignKeyConstraints()
                : $schemaBuilder->disableForeignKeyConstraints();
        } catch (QueryException $e) {
            if (! $e->getPrevious() instanceof SQLiteDatabaseDoesNotExistException) {
                throw $e;
            }
        }
    }

    /**
     * Set the busy timeout if configured.
     *
     * @return void
     */
    protected function configureBusyTimeout(): void
    {
        $milliseconds = $this->getConfig('busy_timeout');

        if ($milliseconds === null) {
            return;
        }

        try {
            $this->getSchemaBuilder()->setBusyTimeout($milliseconds);
        } catch (QueryException $e) {
            if (! $e->getPrevious() instanceof SQLiteDatabaseDoesNotExistException) {
                throw $e;
            }
        }
    }

    /**
     * Set the journal mode if configured.
     *
     * @return void
     */
    protected function configureJournalMode(): void
    {
        $mode = $this->getConfig('journal_mode');

        if ($mode === null) {
            return;
        }

        try {
            $this->getSchemaBuilder()->setJournalMode($mode);
        } catch (QueryException $e) {
            if (! $e->getPrevious() instanceof SQLiteDatabaseDoesNotExistException) {
                throw $e;
            }
        }
    }

    /**
     * Set the synchronous mode if configured.
     *
     * @return void
     */
    protected function configureSynchronous(): void
    {
        $mode = $this->getConfig('synchronous');

        if ($mode === null) {
            return;
        }

        try {
            $this->getSchemaBuilder()->setSynchronous($mode);
        } catch (QueryException $e) {
            if (! $e->getPrevious() instanceof SQLiteDatabaseDoesNotExistException) {
                throw $e;
            }
        }
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
        return boolval(preg_match('#(column(s)? .* (is|are) not unique|UNIQUE constraint failed: .*)#i', $exception->getMessage()));
    }

    /**
     * Get the default query grammar instance.
     *
     * @return \LaraGram\Database\Query\Grammars\SQLiteGrammar
     */
    protected function getDefaultQueryGrammar()
    {
        ($grammar = new QueryGrammar)->setConnection($this);

        return $this->withTablePrefix($grammar);
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
        ($grammar = new SchemaGrammar)->setConnection($this);

        return $this->withTablePrefix($grammar);
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
