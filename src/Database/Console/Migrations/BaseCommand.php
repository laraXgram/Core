<?php

namespace LaraGram\Database\Console\Migrations;

use LaraGram\Console\Command;
use LaraGram\Support\Collection;

class BaseCommand extends Command
{
    /**
     * Get all of the migration paths.
     *
     * @return string[]
     */
    protected function getMigrationPaths()
    {
        // Here, we will check to see if a path option has been defined. If it has we will
        // use the path relative to the root of the installation folder so our database
        // migrations may be run for any customized path from within the application.
        if ($this->input->hasOption('path') && $this->option('path')) {
            return (new Collection($this->option('path')))->map(function ($path) {
                return ! $this->usingRealPath()
                                ? $this->laragram->basePath().'/'.$path
                                : $path;
            })->all();
        }

        return array_merge(
            $this->migrator->paths(), [$this->getMigrationPath()]
        );
    }

    /**
     * Determine if the given path(s) are pre-resolved "real" paths.
     *
     * @return bool
     */
    protected function usingRealPath()
    {
        return $this->input->hasOption('realpath') && $this->option('realpath');
    }

    /**
     * Get the path to the migration directory.
     *
     * @return string
     */
    protected function getMigrationPath()
    {
        return $this->laragram->databasePath().DIRECTORY_SEPARATOR.'migrations';
    }
}
