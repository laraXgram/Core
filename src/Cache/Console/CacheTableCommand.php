<?php

namespace LaraGram\Cache\Console;

use LaraGram\Console\MigrationGeneratorCommand;
use LaraGram\Console\Attribute\AsCommand;

#[AsCommand(name: 'make:cache-table', aliases: ['cache:table'])]
class CacheTableCommand extends MigrationGeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:cache-table';

    /**
     * The console command name aliases.
     *
     * @var array
     */
    protected $aliases = ['cache:table'];

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a migration for the cache database table';

    /**
     * Get the migration table name.
     *
     * @return string
     */
    protected function migrationTableName()
    {
        return 'cache';
    }

    /**
     * Get the path to the migration stub file.
     *
     * @return string
     */
    protected function migrationStubFile()
    {
        return __DIR__.'/stubs/cache.stub';
    }
}
