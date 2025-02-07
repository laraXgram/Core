<?php

namespace LaraGram\Foundation\Console;

use LaraGram\Console\Command;
use LaraGram\Support\Facades\Console;

class JsonMigrateCommand extends Command
{
    protected $signature = 'json-migrate';
    protected $description = 'Start migrating';

    public function handle(): void
    {
        if ($this->getOption('h') == 'h') Console::output()->message($this->description, true);

        $JSON_DB_DATA_DIR = config('database.json.storage');
        if (!file_exists($JSON_DB_DATA_DIR)){
            mkdir($JSON_DB_DATA_DIR, recursive: true);
        }

        $migration_path = $JSON_DB_DATA_DIR . '/migration.json';

        if (!is_file($migration_path)) file_put_contents($migration_path, '{"migrated":{},"batch":0}');

        $migrationFile = json_decode(file_get_contents($migration_path), true);
        $migrationFile['batch']++;

        $migrationFolder = app('path.database') . 'json/migrations';
        $migrations = scandir($migrationFolder);
        $needed = [];
        $status = false;
        foreach ($migrations as $migration) {
            if ($migration[0] != '.' && !in_array($migration, $migrationFile['migrated'])) {
                $start = microtime(true);
                $class = require_once app('path.database') . "/json/migrations/{$migration}";
                $class->up();
                $migrationFile['migrated'][] = $migration;
                $end = floor((microtime(true) - $start) * 1000);
                Console::output()->success("{$migration} Migrated! -> {$end}ms");
                $status = true;
            }
        }
        if (!$status) {
            Console::output()->warning("Nothing to migrate!");
        } else {
            file_put_contents($migration_path, json_encode($migrationFile, 128 | 16));
        }
    }
}