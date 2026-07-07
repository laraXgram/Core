<?php

namespace LaraGram\Foundation\Providers;

use LaraGram\Contracts\Validation\ValidatesWhenResolved;
use LaraGram\Foundation\Http\FormRequest;
use LaraGram\Routing\Redirector;
use LaraGram\Support\ServiceProvider;

class FormRequestServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app->afterResolving(ValidatesWhenResolved::class, function ($resolved) {
            $resolved->validateResolved();
        });

        $this->app->resolving(FormRequest::class, function ($request, $app) {
            $request = FormRequest::createFrom($app['http.request'], $request);

            $request->setContainer($app)->setRedirector($app->make(Redirector::class));
        });
    }
}
