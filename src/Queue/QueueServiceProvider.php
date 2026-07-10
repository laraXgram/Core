<?php

namespace LaraGram\Queue;

use Aws\DynamoDb\DynamoDbClient;
use LaraGram\Contracts\Debug\ExceptionHandler;
use LaraGram\Contracts\Events\Dispatcher as EventDispatcher;
use LaraGram\Contracts\Support\DeferrableProvider;
use LaraGram\Queue\Connectors\BackgroundConnector;
use LaraGram\Queue\Connectors\BeanstalkdConnector;
use LaraGram\Queue\Connectors\DatabaseConnector;
use LaraGram\Queue\Connectors\DeferredConnector;
use LaraGram\Queue\Connectors\FailoverConnector;
use LaraGram\Queue\Connectors\NullConnector;
use LaraGram\Queue\Connectors\RedisConnector;
use LaraGram\Queue\Connectors\SqsConnector;
use LaraGram\Queue\Connectors\SyncConnector;
use LaraGram\Queue\Failed\DatabaseFailedJobProvider;
use LaraGram\Queue\Failed\DatabaseUuidFailedJobProvider;
use LaraGram\Queue\Failed\DynamoDbFailedJobProvider;
use LaraGram\Queue\Failed\FileFailedJobProvider;
use LaraGram\Queue\Failed\NullFailedJobProvider;
use LaraGram\Support\Arr;
use LaraGram\Support\Facades\Facade;
use LaraGram\Support\ServiceProvider;
use LaraGram\Support\SerializableClosure\SerializableClosure;

class QueueServiceProvider extends ServiceProvider implements DeferrableProvider
{
    use SerializesAndRestoresModelIdentifiers;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->configureSerializableClosureUses();

        $this->registerManager();
        $this->registerConnection();
        $this->registerWorker();
        $this->registerListener();
        $this->registerRoutes();
        $this->registerFailedJobServices();
    }

    /**
     * Configure serializable closures uses.
     *
     * @return void
     */
    protected function configureSerializableClosureUses()
    {
        SerializableClosure::transformUseVariablesUsing(function ($data) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->getSerializedPropertyValue($value);
            }

            return $data;
        });

        SerializableClosure::resolveUseVariablesUsing(function ($data) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->getRestoredPropertyValue($value);
            }

            return $data;
        });
    }

    /**
     * Register the queue manager.
     *
     * @return void
     */
    protected function registerManager()
    {
        $this->app->singleton('queue', function ($app) {
            // Once we have an instance of the queue manager, we will register the various
            // resolvers for the queue connectors. These connectors are responsible for
            // creating the classes that accept queue configs and instantiate queues.
            return tap(new QueueManager($app), function ($manager) {
                $this->registerConnectors($manager);
            });
        });
    }

    /**
     * Register the default queue connection binding.
     *
     * @return void
     */
    protected function registerConnection()
    {
        $this->app->singleton('queue.connection', function ($app) {
            return $app['queue']->connection();
        });
    }

    /**
     * Register the connectors on the queue manager.
     *
     * @param  \LaraGram\Queue\QueueManager  $manager
     * @return void
     */
    public function registerConnectors($manager)
    {
        foreach (['Null', 'Sync', 'Deferred', 'Background', 'Failover', 'Database', 'Redis', 'Beanstalkd', 'Sqs'] as $connector) {
            $this->{"register{$connector}Connector"}($manager);
        }
    }

    /**
     * Register the Null queue connector.
     *
     * @param  \LaraGram\Queue\QueueManager  $manager
     * @return void
     */
    protected function registerNullConnector($manager)
    {
        $manager->addConnector('null', function () {
            return new NullConnector;
        });
    }

    /**
     * Register the Sync queue connector.
     *
     * @param  \LaraGram\Queue\QueueManager  $manager
     * @return void
     */
    protected function registerSyncConnector($manager)
    {
        $manager->addConnector('sync', function () {
            return new SyncConnector;
        });
    }

    /**
     * Register the Deferred queue connector.
     *
     * @param  \LaraGram\Queue\QueueManager  $manager
     * @return void
     */
    protected function registerDeferredConnector($manager)
    {
        $manager->addConnector('deferred', function () {
            return new DeferredConnector;
        });
    }

    /**
     * Register the Background queue connector.
     *
     * @param  \LaraGram\Queue\QueueManager  $manager
     * @return void
     */
    protected function registerBackgroundConnector($manager)
    {
        $manager->addConnector('background', function () {
            return new BackgroundConnector;
        });
    }

    /**
     * Register the Failover queue connector.
     *
     * @param  \LaraGram\Queue\QueueManager  $manager
     * @return void
     */
    protected function registerFailoverConnector($manager)
    {
        $manager->addConnector('failover', function () use ($manager) {
            return new FailoverConnector(
                $manager,
                $this->app->make(EventDispatcher::class)
            );
        });
    }

    /**
     * Register the database queue connector.
     *
     * @param  \LaraGram\Queue\QueueManager  $manager
     * @return void
     */
    protected function registerDatabaseConnector($manager)
    {
        $manager->addConnector('database', function () {
            return new DatabaseConnector($this->app['db']);
        });
    }

    /**
     * Register the Redis queue connector.
     *
     * @param  \LaraGram\Queue\QueueManager  $manager
     * @return void
     */
    protected function registerRedisConnector($manager)
    {
        $manager->addConnector('redis', function () {
            return new RedisConnector($this->app['redis']);
        });
    }

    /**
     * Register the Beanstalkd queue connector.
     *
     * @param  \LaraGram\Queue\QueueManager  $manager
     * @return void
     */
    protected function registerBeanstalkdConnector($manager)
    {
        $manager->addConnector('beanstalkd', function () {
            return new BeanstalkdConnector;
        });
    }

    /**
     * Register the Amazon SQS queue connector.
     *
     * @param  \LaraGram\Queue\QueueManager  $manager
     * @return void
     */
    protected function registerSqsConnector($manager)
    {
        $manager->addConnector('sqs', function () {
            return new SqsConnector;
        });
    }

    /**
     * Register the queue worker.
     *
     * @return void
     */
    protected function registerWorker()
    {
        $this->app->singleton('queue.worker', function ($app) {
            $isDownForMaintenance = function () {
                return $this->app->isDownForMaintenance();
            };

            $resetScope = function () use ($app) {
                if (method_exists($app['log'], 'flushSharedContext')) {
                    $app['log']->flushSharedContext();
                }

                if (method_exists($app['log'], 'withoutContext')) {
                    $app['log']->withoutContext();
                }

                if (method_exists($app['db'], 'getConnections')) {
                    foreach ($app['db']->getConnections() as $connection) {
                        $connection->resetTotalQueryDuration();
                        $connection->allowQueryDurationHandlersToRunAgain();
                    }
                }

                $app->forgetScopedInstances();

                Facade::clearResolvedInstances();

                memory_reset_peak_usage();
            };

            return new Worker(
                $app['queue'],
                $app['events'],
                $app[ExceptionHandler::class],
                $isDownForMaintenance,
                $resetScope
            );
        });
    }

    /**
     * Register the queue listener.
     *
     * @return void
     */
    protected function registerListener()
    {
        $this->app->singleton('queue.listener', function ($app) {
            return new Listener($app->basePath());
        });
    }

    /**
     * Register the default queue routes binding.
     *
     * @return void
     */
    protected function registerRoutes()
    {
        $this->app->singleton('queue.routes', function () {
            return new QueueRoutes;
        });
    }

    /**
     * Register the failed job services.
     *
     * @return void
     */
    protected function registerFailedJobServices()
    {
        $this->app->singleton('queue.failer', function ($app) {
            $config = $app['config']['queue.failed'];

            if (array_key_exists('driver', $config) &&
                (is_null($config['driver']) || $config['driver'] === 'null')) {
                return new NullFailedJobProvider;
            }

            if (isset($config['driver']) && $config['driver'] === 'file') {
                return new FileFailedJobProvider(
                    $config['path'] ?? $this->app->storagePath('framework/cache/failed-jobs.json'),
                    $config['limit'] ?? 100,
                    fn () => $app['cache']->store('file'),
                );
            } elseif (isset($config['driver']) && $config['driver'] === 'dynamodb') {
                return $this->dynamoFailedJobProvider($config);
            } elseif (isset($config['driver']) && $config['driver'] === 'database-uuids') {
                return $this->databaseUuidFailedJobProvider($config);
            } elseif (isset($config['table'])) {
                return $this->databaseFailedJobProvider($config);
            } else {
                return new NullFailedJobProvider;
            }
        });
    }

    /**
     * Create a new database failed job provider.
     *
     * @param  array  $config
     * @return \LaraGram\Queue\Failed\DatabaseFailedJobProvider
     */
    protected function databaseFailedJobProvider($config)
    {
        return new DatabaseFailedJobProvider(
            $this->app['db'], $config['database'], $config['table']
        );
    }

    /**
     * Create a new database failed job provider that uses UUIDs as IDs.
     *
     * @param  array  $config
     * @return \LaraGram\Queue\Failed\DatabaseUuidFailedJobProvider
     */
    protected function databaseUuidFailedJobProvider($config)
    {
        return new DatabaseUuidFailedJobProvider(
            $this->app['db'], $config['database'], $config['table']
        );
    }

    /**
     * Create a new DynamoDb failed job provider.
     *
     * @param  array  $config
     * @return \LaraGram\Queue\Failed\DynamoDbFailedJobProvider
     */
    protected function dynamoFailedJobProvider($config)
    {
        $dynamoConfig = [
            'region' => $config['region'],
            'version' => 'latest',
            'endpoint' => $config['endpoint'] ?? null,
        ];

        if (! empty($config['key']) && ! empty($config['secret'])) {
            $dynamoConfig['credentials'] = Arr::only($config, ['key', 'secret']);

            if (! empty($config['token'])) {
                $dynamoConfig['credentials']['token'] = $config['token'];
            }
        }

        return new DynamoDbFailedJobProvider(
            new DynamoDbClient($dynamoConfig),
            $this->app['config']['app.name'],
            $config['table']
        );
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'queue',
            'queue.connection',
            'queue.failer',
            'queue.listener',
            'queue.routes',
            'queue.worker',
        ];
    }
}
