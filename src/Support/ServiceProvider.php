<?php

namespace LaraGram\Support;

use Closure;
use LaraGram\Container\Container;
use LaraGram\Contracts\Application;

abstract class ServiceProvider
{
    protected Application|Container $app;

    protected array $bootingCallbacks = [];

    protected array $bootedCallbacks = [];

    public function __construct($app)
    {
        $this->app = $app;
    }

    public function register()
    {
        //
    }

    public function booting(Closure $callback): void
    {
        $this->bootingCallbacks[] = $callback;
    }

    public function booted(Closure $callback): void
    {
        $this->bootedCallbacks[] = $callback;
    }

    public function callBootingCallbacks(): void
    {
        $index = 0;

        while ($index < count($this->bootingCallbacks)) {
            $this->app->call($this->bootingCallbacks[$index]);

            $index++;
        }
    }

    public function callBootedCallbacks(): void
    {
        $index = 0;

        while ($index < count($this->bootedCallbacks)) {
            $this->app->call($this->bootedCallbacks[$index]);

            $index++;
        }
    }

    protected function callAfterResolving($name, $callback): void
    {
        $this->app->afterResolving($name, $callback);

        if ($this->app->resolved($name)) {
            $callback($this->app->make($name), $this->app);
        }
    }

    public function provides(): array
    {
        return [];
    }

    public function when(): array
    {
        return [];
    }

}
