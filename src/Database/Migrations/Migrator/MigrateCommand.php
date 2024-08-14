<?php

namespace LaraGram\Database\Migrations\Migrator;

use Illuminate\Database\Schema\Blueprint;
use LaraGram\Console\Command;
use LaraGram\Support\Facades\Console;
use LaraGram\Support\Facades\Schema;

class MigrateCommand extends Command
{
    private int $batch;

    protected $signature = 'migrate';
    protected $description = 'Start migrating';

    public function handle()
    {
        if ($this->getOption('h') == 'h') Console::output()->message($this->description, true);

        $this->migrate();
    }

    private function needMigrate(): array|null
    {
        $lastMigrationsInDb = Migration::all()->toArray();
        $batch = [];
        foreach ($lastMigrationsInDb as $lastMigrationInDb) {
            $batch[] = $lastMigrationInDb['batch'];
            $lastMigrations[] = $lastMigrationInDb['migration'];
        }

        $existMigrationsInFolder = scandir(app('path.database') . DIRECTORY_SEPARATOR . 'migrations');
        foreach ($existMigrationsInFolder as $existMigrationInFolder) {
            if ($existMigrationInFolder[0] !== '.') {
                $existMigrations[] = $existMigrationInFolder;
            }
        }

        $existMigrations = array_map(function ($value) {
            return str_replace('.php', '', $value);
        }, $existMigrations);

        $needToMigrate = [];

        if (isset($lastMigrations) && isset($existMigrations) && count($lastMigrations) === count($existMigrations)) {
            return null;
        } else if (isset($lastMigrations) && count($lastMigrations) !== count($existMigrations)) {
            foreach ($existMigrations as $existMigration) {
                if (!in_array($existMigration, $lastMigrations)) {
                    $needToMigrate[] = $existMigration;
                }
            }

            $this->batch = max($batch);
        } else {
            foreach ($existMigrations as $existMigration) {
                $needToMigrate[] = $existMigration;
            }

            $this->batch = 0;
        }

        return $needToMigrate;
    }

    private function addToMigrations(string $name, int $batch): void
    {
        $migration = new Migration();
        $migration->migration = $name;
        $migration->batch = $batch;
        $migration->save();
    }

    public function migrate(): void
    {
        $this->create_migrations_table();

        $needMigrate = $this->needMigrate();
        if (is_null($needMigrate)) {
            Console::output()->message('Nothing to migrate!');
            return;
        }

        foreach ($needMigrate as $migrate) {
            $start = microtime(true);
            $class = require_once app('path.database') . DIRECTORY_SEPARATOR . 'migrations' . DIRECTORY_SEPARATOR .  "{$migrate}.php";
            $class->up();
            $this->addToMigrations($migrate, $this->batch + 1);
            $end = floor((microtime(true) - $start) * 1000);
            Console::output()->success("Migrated: [ $migrate ] -> {$end}ms");
        }
    }

    public function create_migrations_table(): void
    {

        if (!Schema::hasTable('migrations')) {
            Schema::create('migrations', function (Blueprint $table) {
                $table->id();
                $table->string('migration');
                $table->integer('batch');
            });
        }
    }
}
