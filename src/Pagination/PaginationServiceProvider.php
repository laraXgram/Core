<?php

namespace LaraGram\Pagination;

use LaraGram\Support\ServiceProvider;

class PaginationServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadViewsFrom(__DIR__.'/resources/views', 'pagination');

        $this->loadTemplatesFrom(__DIR__.'/resources/templates', 'pagination');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/resources/views' => $this->app->resourcePath('views/vendor/pagination'),
            ], 'laragram-pagination');

            $this->publishes([
                __DIR__.'/resources/templates' => $this->app->basePath('app/templates/vendor/pagination'),
            ], 'laragram-pagination');
        }
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        PaginationState::resolveUsing($this->app);
    }
}
