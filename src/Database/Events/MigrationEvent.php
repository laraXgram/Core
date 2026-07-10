<?php

namespace LaraGram\Database\Events;

use LaraGram\Contracts\Database\Events\MigrationEvent as MigrationEventContract;
use LaraGram\Database\Migrations\Migration;

abstract class MigrationEvent implements MigrationEventContract
{
    /**
     * A migration instance.
     *
     * @var \LaraGram\Database\Migrations\Migration
     */
    public $migration;

    /**
     * The migration method that was called.
     *
     * @var string
     */
    public $method;

    /**
     * The migration name.
     *
     * @var string|null
     */
    public $name;

    /**
     * Create a new event instance.
     *
     * @param  \LaraGram\Database\Migrations\Migration  $migration
     * @param  string  $method
     * @param  string|null  $name
     */
    public function __construct(Migration $migration, $method, $name = null)
    {
        $this->method = $method;
        $this->migration = $migration;
        $this->name = $name;
    }
}
