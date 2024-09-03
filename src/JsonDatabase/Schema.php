<?php

namespace LaraGram\JsonDatabase;

use Closure;

class Schema
{
    private function mergeSchemas($array1, $array2)
    {
        foreach ($array2 as $key => $value) {
            if (!isset($array1[$key])) {
                $array1[$key] = $value;
            } else {
                if (is_array($value) && is_array($array1[$key])) {
                    $array1[$key] = $this->mergeSchemas($array1[$key], $value);
                }
            }
        }
        return $array1;
    }

    public static function hasTable(string $table): bool
    {
        return key_exists(ucfirst($table), json_decode(file_get_contents(Config::get('database.JSON_DB_DATA_DIR') . 'schema.json'), true));
    }

    public static function table(string $table, Closure $callback): void
    {
        $schema_path = Config::get('database.JSON_DB_DATA_DIR') . 'schema.json';
        if (!is_file($schema_path)) file_put_contents($schema_path, '');
        $schema = json_decode(file_get_contents($schema_path), true);
        $blueprint = new Blueprint();
        $callback($blueprint);
        $oldSchema = $schema[ucfirst($table)];
        $newSchema = $blueprint->getSchema();
        $schema[ucfirst($table)] = (new self())->mergeSchemas($oldSchema, $newSchema);
        file_put_contents($schema_path, json_encode($schema, 128 | 16));
    }

    public static function create(string $table, Closure $callback): void
    {
        $schema_path = Config::get('database.JSON_DB_DATA_DIR') . 'schema.json';
        if (!is_file($schema_path)) file_put_contents($schema_path, '');
        $schema = json_decode(file_get_contents($schema_path), true);
        $blueprint = new Blueprint();
        $callback($blueprint);
        $schema[ucfirst($table)] = $blueprint->getSchema();
        file_put_contents($schema_path, json_encode($schema, 128 | 16));
    }

    public static function drop(string $table): void
    {
        $schema_path = Config::get('database.JSON_DB_DATA_DIR') . 'schema.json';
        $schema = json_decode(file_get_contents($schema_path), true);
        unset($schema[ucfirst($table)]);
        file_put_contents($schema_path, json_encode($schema, 128 | 16));
    }

    public static function dropAllTables(): void
    {
        file_put_contents(Config::get('database.JSON_DB_DATA_DIR') . 'schema.json', "{\n\n}");
    }
}