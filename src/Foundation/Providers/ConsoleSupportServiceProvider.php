<?php

namespace LaraGram\Foundation\Providers;

use LaraGram\Contracts\Support\DeferrableProvider;
use LaraGram\Database\MigrationServiceProvider;
use LaraGram\Support\AggregateServiceProvider;

class ConsoleSupportServiceProvider extends AggregateServiceProvider implements DeferrableProvider
{
    /**
     * The provider class names.
     *
     * @var string[]
     */
    protected $providers = [
        CommanderServiceProvider::class,
        MigrationServiceProvider::class,
        ComposerServiceProvider::class,
    ];
}
