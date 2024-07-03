<?php

namespace LaraGram\JsonDatabase;

use LaraGram\Console\Command;
use function Symfony\Component\Translation\t;

class MigrateCommand extends Command
{
    protected $signature = 'json-migrate';
    protected $description = 'Start migrating';

    public function handle(): void
    {
        if ($this->getOption('h') == 'h') $this->output->message($this->description, true);

        if (!file_exists(app('path.storage') . '/App/JDB')){
            mkdir(app('path.storage') . '/App/JDB/', recursive: true);
        }

        $migration_path = app('path.storage') . '/App/JDB/migration.json';

        if (!is_file($migration_path)) file_put_contents($migration_path, '{"migrated":{},"batch":0}');

        $migrationFile = json_decode(file_get_contents($migration_path), true);
        $migrationFile['batch']++;

        $migrationFolder = app('path.database') . '/Json/Migrations';
        $migrations = scandir($migrationFolder);
        $needed = [];
        $status = false;
        foreach ($migrations as $migration) {
            if ($migration[0] != '.' && !in_array($migration, $migrationFile['migrated'])) {
                $start = microtime(true);
                $class = require_once app('path.database') . "/Json/Migrations/{$migration}";
                $class->up();
                $migrationFile['migrated'][] = $migration;
                $end = floor((microtime(true) - $start) * 1000);
                $this->output->success("{$migration} Migrated! -> {$end}ms");
                $status = true;
            }
        }
        if (!$status) {
            $this->output->warning("Nothing to migrate!");
        } else {
            file_put_contents($migration_path, json_encode($migrationFile, 128 | 16));
        }
    }
}