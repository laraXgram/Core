<?php

namespace LaraGram\Filesystem;

use LaraGram\Support\ServiceProvider;

class FilesystemServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('files', function () {
            return new Filesystem;
        });
    }
}
