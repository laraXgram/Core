<?php

namespace LaraGram\Database\Migrations;

use Closure;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Builder;

class Schema
{
    public function hasTable(string $table): bool
    {
        return Capsule::schema()->hasTable($table);
    }

    public function table(string $table, Closure $callback)
    {
        Capsule::schema()->table($table, $callback);
    }

    public function create(string $table, Closure $callback)
    {
        Capsule::schema()->create($table, $callback);
    }

    public function blueprintResolver(Closure $resolver)
    {
        Capsule::schema()->blueprintResolver($resolver);
    }

    public function createDatabase(string $name): bool
    {
        return Capsule::schema()->createDatabase($name);
    }

    public function disableForeignKeyConstraints(): bool
    {
        return Capsule::schema()->disableForeignKeyConstraints();
    }

    public function drop(string $table)
    {
        Capsule::schema()->drop($table);
    }

    public function dropAllTables()
    {
        Capsule::schema()->dropAllTables();
    }

    public function dropAllTypes()
    {
        Capsule::schema()->dropAllTypes();
    }

    public function dropAllViews()
    {
        Capsule::schema()->dropAllViews();
    }

    public function dropColumns(string $name, array|string $columns)
    {
        Capsule::schema()->dropColumns($name, $columns);
    }

    public function dropDatabaseIfExists(string $name): bool
    {
        return Capsule::schema()->dropDatabaseIfExists($name);
    }

    public function dropIfExists(string $table)
    {
        Capsule::schema()->dropIfExists($table);
    }

    public function enableForeignKeyConstraints(): bool
    {
        return Capsule::schema()->enableForeignKeyConstraints();
    }

    public function getAllTables(): array
    {
        return Capsule::schema()->getAllTables();
    }

    public function getColumnListing(string $table): array
    {
        return Capsule::schema()->getColumnListing($table);
    }

    public function getColumnType(string $table, string $column): string
    {
        return Capsule::schema()->getColumnType($table, $column);
    }

    public function getConnection(): Connection
    {
        return Capsule::schema()->getConnection();
    }

    public function hasColumn(string $table, string $column): bool
    {
        return Capsule::schema()->hasColumn($table, $column);
    }

    public function hasColumns(string $table, array $columns): bool
    {
        return Capsule::schema()->hasColumns($table, $columns);
    }

    public function rename(string $from, string $to)
    {
        Capsule::schema()->rename($from, $to);
    }

    public function setConnection(Connection $connection): Builder
    {
        return Capsule::schema()->setConnection($connection);
    }

    public function whenTableDoesntHaveColumn(string $table, string $column, Closure $callback)
    {
        Capsule::schema()->whenTableDoesntHaveColumn($table, $column, $callback);
    }

    public function withoutForeignKeyConstraints(Closure $callback): mixed
    {
        return Capsule::schema()->withoutForeignKeyConstraints($callback);
    }

    public function whenTableHasColumn(string $table, string $column, Closure $callback)
    {
        Capsule::schema()->whenTableHasColumn($table, $column, $callback);
    }
}