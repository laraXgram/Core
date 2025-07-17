<?php

namespace LaraGram\Foundation\Support\Providers;

use App\Models\User;
use LaraGram\Request\Request;
use LaraGram\Support\Facades\Bot;
use LaraGram\Support\Facades\Gate;
use LaraGram\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [];

    /**
     * Register the application's policies.
     *
     * @return void
     */
    public function register()
    {
        $this->booting(function () {
            $this->registerPolicies();
        });
    }

    /**
     * Register the application's policies.
     *
     * @return void
     */
    public function registerPolicies()
    {
        foreach ($this->policies() as $model => $policy) {
            Gate::policy($model, $policy);
        }
    }

    /**
     * Get the policies defined on the provider.
     *
     * @return array<class-string, class-string>
     */
    public function policies()
    {
        return $this->policies;
    }
}