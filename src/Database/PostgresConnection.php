<?php

namespace LaraGram\Database;

use DateTimeInterface;
use Exception;
use LaraGram\Database\Query\Grammars\PostgresGrammar as QueryGrammar;
use LaraGram\Database\Query\Processors\PostgresProcessor;
use LaraGram\Database\Schema\Grammars\PostgresGrammar as SchemaGrammar;
use LaraGram\Database\Schema\PostgresBuilder;
use LaraGram\Database\Schema\PostgresSchemaState;
use LaraGram\Filesystem\Filesystem;
use PDO;

class PostgresConnection extends Connection
{
    /**
     * {@inheritdoc}
     */
    public function getDriverTitle()
    {
        return 'PostgreSQL';
    }

    /**
     * Prepare the query bindings for execution.
     *
     * @param  array  $bindings
     * @return array
     */
    public function prepareBindings(array $bindings)
    {
        $grammar = $this->getQueryGrammar();

        foreach ($bindings as $key => $value) {
            if ($value instanceof DateTimeInterface) {
                $bindings[$key] = $value->format($grammar->getDateFormat());
            } elseif (is_bool($value)) {
                $bindings[$key] = $this->usesEmulatedPrepares()
                    ? ($value ? 'true' : 'false')
                    : (int) $value;
            }
        }

        return $bindings;
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

        return "'\x{$hex}'::bytea";
    }

    /**
     * Escape a bool value for safe SQL embedding.
     *
     * @param  bool  $value
     * @return string
     */
    protected function escapeBool($value)
    {
        return $value ? 'true' : 'false';
    }

    /**
     * Determine if the given database exception was caused by a unique constraint violation.
     *
     * @param  \Exception  $exception
     * @return bool
     */
    protected function isUniqueConstraintError(Exception $exception)
    {
        return '23505' === $exception->getCode();
    }

    /**
     * Extract the index and columns that caused a unique constraint violation.
     *
     * @param  Exception  $exception
     * @return array{index: string|null, columns: list<string>}
     */
    protected function parseUniqueConstraintViolation(Exception $exception): array
    {
        [$index, $columns] = [null, []];

        if (preg_match('#unique constraint "([^"]+)"#i', $message = $exception->getMessage(), $matches)) {
            $index = $matches[1];
        }

        if (preg_match('#Key \(([^)]+)\)=#i', $message, $matches)) {
            $columns = array_map(trim(...), explode(',', $matches[1]));
        }

        return ['columns' => $columns, 'index' => $index];
    }

    /**
     * Get the default query grammar instance.
     *
     * @return \LaraGram\Database\Query\Grammars\PostgresGrammar
     */
    protected function getDefaultQueryGrammar()
    {
        return new QueryGrammar($this);
    }

    /**
     * Get a schema builder instance for the connection.
     *
     * @return \LaraGram\Database\Schema\PostgresBuilder
     */
    public function getSchemaBuilder()
    {
        if (is_null($this->schemaGrammar)) {
            $this->useDefaultSchemaGrammar();
        }

        return new PostgresBuilder($this);
    }

    /**
     * Get the default schema grammar instance.
     *
     * @return \LaraGram\Database\Schema\Grammars\PostgresGrammar
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
     * @return \LaraGram\Database\Schema\PostgresSchemaState
     */
    public function getSchemaState(?Filesystem $files = null, ?callable $processFactory = null)
    {
        return new PostgresSchemaState($this, $files, $processFactory);
    }

    /**
     * Get the default post processor instance.
     *
     * @return \LaraGram\Database\Query\Processors\PostgresProcessor
     */
    protected function getDefaultPostProcessor()
    {
        return new PostgresProcessor;
    }

    /**
     * Determine if the active PDO configuration uses emulated prepares.
     *
     * @return bool
     */
    protected function usesEmulatedPrepares()
    {
        // Binding preparation runs after query routing has selected the PDO variant...
        $config = match ($this->latestReadWriteTypeUsed()) {
            'read' => $this->readPdoConfig,
            'direct' => $this->directPdoConfig,
            default => $this->config,
        };

        return (bool) ($config['options'][PDO::ATTR_EMULATE_PREPARES] ?? false);
    }
}
