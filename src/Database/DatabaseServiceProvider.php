<?php

namespace LaraGram\Database;

use LaraGram\Contracts\Support\DeferrableProvider;
use LaraGram\Database\Migrations\Schema;
use LaraGram\Support\ServiceProvider;

class DatabaseServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function boot()
    {
        if (config('database.database.power') == 'on') {
            $this->app->registerEloquent();
        }
    }

    public function register(): void
    {
        $this->app->singleton('db.schema', function (){
           return new Schema();
        });
    }

    public function provides(): array
    {
        return ['db.schema'];
    }
}