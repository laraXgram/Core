<?php

namespace LaraGram\Database;

use LaraGram\Database\Migrations\Schema;
use LaraGram\Support\ServiceProvider;

class DatabaseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('db.schema', function (){
           return new Schema();
        });
    }
}