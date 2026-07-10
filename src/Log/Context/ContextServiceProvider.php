<?php

namespace LaraGram\Log\Context;

use LaraGram\Contracts\Log\ContextLogProcessor as ContextLogProcessorContract;
use LaraGram\Queue\Events\JobProcessing;
use LaraGram\Queue\Queue;
use LaraGram\Support\Env;
use LaraGram\Support\Facades\Context;
use LaraGram\Support\ServiceProvider;

class ContextServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->scoped(Repository::class);

        if ($this->app->runningInConsole()) {
            $this->app->resolving(Repository::class, function (Repository $repository) {
                $context = Env::get('__LARAGRAM_CONTEXT');

                if ($context && $context = json_decode($context, associative: true)) {
                    $repository->hydrate($context);
                }
            });
        }

        $this->app->bind(ContextLogProcessorContract::class, fn () => new ContextLogProcessor());
    }

    /**
     * Boot the application services.
     *
     * @return void
     */
    public function boot()
    {
        Queue::createPayloadUsing(function ($connection, $queue, $payload) {
            /** @phpstan-ignore staticMethod.notFound */
            $context = Context::dehydrate();

            return $context === null ? $payload : [
                ...$payload,
                'laragram:log:context' => $context,
            ];
        });

        $this->app['events']->listen(function (JobProcessing $event) {
            /** @phpstan-ignore staticMethod.notFound */
            Context::hydrate($event->job->payload()['laragram:log:context'] ?? null);
        });
    }
}
