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
        return key_exists(ucfirst($table), json_decode(file_get_contents(app('path.storage') . '/App/JDB/schema.json'), true));
    }

    public static function table(string $table, Closure $callback): void
    {
        if (!is_file(app('path.storage') . '/App/JDB/schema.json')) file_put_contents(app('path.storage') . '/App/JDB/schema.json', '');
        $schema = json_decode(file_get_contents(app('path.storage') . '/App/JDB/schema.json'), true);
        $blueprint = new Blueprint();
        $callback($blueprint);
        $oldSchema = $schema[ucfirst($table)];
        $newSchema = $blueprint->getSchema();
        $schema[ucfirst($table)] = (new self())->mergeSchemas($oldSchema, $newSchema);
        file_put_contents(app('path.storage') . '/App/JDB/schema.json', json_encode($schema, 128 | 16));
    }

    public static function create(string $table, Closure $callback): void
    {
        if (!is_file(app('path.storage') . '/App/JDB/schema.json')) file_put_contents(app('path.storage') . '/App/JDB/schema.json', '');
        $schema = json_decode(file_get_contents(app('path.storage') . '/App/JDB/schema.json'), true);
        $blueprint = new Blueprint();
        $callback($blueprint);
        $schema[ucfirst($table)] = $blueprint->getSchema();
        file_put_contents(app('path.storage') . '/App/JDB/schema.json', json_encode($schema, 128 | 16));
    }

    public static function drop(string $table): void
    {
        $schema = json_decode(file_get_contents(app('path.storage') . '/App/JDB/schema.json'), true);
        unset($schema[ucfirst($table)]);
        file_put_contents(app('path.storage') . '/App/JDB/schema.json', json_encode($schema, 128 | 16));
    }

    public static function dropAllTables(): void
    {
        file_put_contents(app('path.storage') . '/App/JDB/schema.json', "{\n\n}");
    }
}