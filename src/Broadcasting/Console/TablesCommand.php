<?php

namespace LaraGram\Broadcasting\Console;

use LaraGram\Console\Attribute\AsCommand;
use LaraGram\Console\MigrationGeneratorCommand;

#[AsCommand(name: 'make:broadcast-tables', aliases: ['broadcasting:table'])]
class TablesCommand extends MigrationGeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:broadcast-tables';

    /**
     * The console command name aliases.
     *
     * @var array
     */
    protected $aliases = ['broadcasting:table'];

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a migration for the broadcast chats, members and targets tables';

    /**
     * Get the migration table name.
     *
     * @return string
     */
    protected function migrationTableName()
    {
        return $this->laragram['config']['broadcasting.stores.database.chats_table'] ?? 'broadcast_chats';
    }

    /**
     * Replace the table placeholders of the migration stub.
     *
     * @param  string  $path
     * @param  string  $table
     * @return void
     */
    protected function replaceMigrationPlaceholders($path, $table)
    {
        $config = $this->laragram['config'];

        $this->files->put($path, str_replace(
            ['{{table}}', '{{members_table}}', '{{targets_table}}'],
            [
                $table,
                $config->get('broadcasting.stores.database.members_table', 'broadcast_members'),
                $config->get('broadcasting.stores.database.targets_table', 'broadcast_targets'),
            ],
            $this->files->get($this->migrationStubFile())
        ));
    }

    /**
     * Get the path to the migration stub file.
     *
     * @return string
     */
    protected function migrationStubFile()
    {
        return __DIR__.'/stubs/broadcast_tables.stub';
    }
}
