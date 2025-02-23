<?php

namespace LaraGram\Database\Schema;

use Exception;
use LaraGram\Database\Connection;
use LaraGram\Support\Str;
use LaraGram\Console\Process\Process;

class MySqlSchemaState extends SchemaState
{
    /**
     * Dump the database's schema into a file.
     *
     * @param  \LaraGram\Database\Connection  $connection
     * @param  string  $path
     * @return void
     */
    public function dump(Connection $connection, $path)
    {
        $this->executeDumpProcess($this->makeProcess(
            $this->baseDumpCommand().' --routines --result-file="${:LARAGRAM_LOAD_PATH}" --no-data'
        ), $this->output, array_merge($this->baseVariables($this->connection->getConfig()), [
            'LARAGRAM_LOAD_PATH' => $path,
        ]));

        $this->removeAutoIncrementingState($path);

        if ($this->hasMigrationTable()) {
            $this->appendMigrationData($path);
        }
    }

    /**
     * Remove the auto-incrementing state from the given schema dump.
     *
     * @param  string  $path
     * @return void
     */
    protected function removeAutoIncrementingState(string $path)
    {
        $this->files->put($path, preg_replace(
            '/\s+AUTO_INCREMENT=[0-9]+/iu',
            '',
            $this->files->get($path)
        ));
    }

    /**
     * Append the migration data to the schema dump.
     *
     * @param  string  $path
     * @return void
     */
    protected function appendMigrationData(string $path)
    {
        $process = $this->executeDumpProcess($this->makeProcess(
            $this->baseDumpCommand().' '.$this->getMigrationTable().' --no-create-info --skip-extended-insert --skip-routines --compact --complete-insert'
        ), null, array_merge($this->baseVariables($this->connection->getConfig()), [
            //
        ]));

        $this->files->append($path, $process->getOutput());
    }

    /**
     * Load the given schema file into the database.
     *
     * @param  string  $path
     * @return void
     */
    public function load($path)
    {
        $command = 'mysql '.$this->connectionString().' --database="${:LARAGRAM_LOAD_DATABASE}" < "${:LARAGRAM_LOAD_PATH}"';

        $process = $this->makeProcess($command)->setTimeout(null);

        $process->mustRun(null, array_merge($this->baseVariables($this->connection->getConfig()), [
            'LARAGRAM_LOAD_PATH' => $path,
        ]));
    }

    /**
     * Get the base dump command arguments for MySQL as a string.
     *
     * @return string
     */
    protected function baseDumpCommand()
    {
        $command = 'mysqldump '.$this->connectionString().' --no-tablespaces --skip-add-locks --skip-comments --skip-set-charset --tz-utc --column-statistics=0';

        if (! $this->connection->isMaria()) {
            $command .= ' --set-gtid-purged=OFF';
        }

        return $command.' "${:LARAGRAM_LOAD_DATABASE}"';
    }

    /**
     * Generate a basic connection string (--socket, --host, --port, --user, --password) for the database.
     *
     * @return string
     */
    protected function connectionString()
    {
        $value = ' --user="${:LARAGRAM_LOAD_USER}" --password="${:LARAGRAM_LOAD_PASSWORD}"';

        $config = $this->connection->getConfig();

        $value .= $config['unix_socket'] ?? false
                        ? ' --socket="${:LARAGRAM_LOAD_SOCKET}"'
                        : ' --host="${:LARAGRAM_LOAD_HOST}" --port="${:LARAGRAM_LOAD_PORT}"';

        if (isset($config['options'][\PDO::MYSQL_ATTR_SSL_CA])) {
            $value .= ' --ssl-ca="${:LARAGRAM_LOAD_SSL_CA}"';
        }

        return $value;
    }

    /**
     * Get the base variables for a dump / load command.
     *
     * @param  array  $config
     * @return array
     */
    protected function baseVariables(array $config)
    {
        $config['host'] ??= '';

        return [
            'LARAGRAM_LOAD_SOCKET' => $config['unix_socket'] ?? '',
            'LARAGRAM_LOAD_HOST' => is_array($config['host']) ? $config['host'][0] : $config['host'],
            'LARAGRAM_LOAD_PORT' => $config['port'] ?? '',
            'LARAGRAM_LOAD_USER' => $config['username'],
            'LARAGRAM_LOAD_PASSWORD' => $config['password'] ?? '',
            'LARAGRAM_LOAD_DATABASE' => $config['database'],
            'LARAGRAM_LOAD_SSL_CA' => $config['options'][\PDO::MYSQL_ATTR_SSL_CA] ?? '',
        ];
    }

    /**
     * Execute the given dump process.
     *
     * @param  \LaraGram\Console\Process\Process  $process
     * @param  callable  $output
     * @param  array  $variables
     * @param  int  $depth
     * @return \LaraGram\Console\Process\Process
     */
    protected function executeDumpProcess(Process $process, $output, array $variables, int $depth = 0)
    {
        if ($depth > 30) {
            throw new Exception('Dump execution exceeded maximum depth of 30.');
        }

        try {
            $process->setTimeout(null)->mustRun($output, $variables);
        } catch (Exception $e) {
            if (Str::contains($e->getMessage(), ['column-statistics', 'column_statistics'])) {
                return $this->executeDumpProcess(Process::fromShellCommandLine(
                    str_replace(' --column-statistics=0', '', $process->getCommandLine())
                ), $output, $variables, $depth + 1);
            }

            if (str_contains($e->getMessage(), 'set-gtid-purged')) {
                return $this->executeDumpProcess(Process::fromShellCommandLine(
                    str_replace(' --set-gtid-purged=OFF', '', $process->getCommandLine())
                ), $output, $variables, $depth + 1);
            }

            throw $e;
        }

        return $process;
    }
}
