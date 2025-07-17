<?php

namespace LaraGram\Foundation\Exceptions\Renderer;

use LaraGram\Contracts\Events\Dispatcher;
use LaraGram\Database\Events\QueryExecuted;
use LaraGram\Queue\Events\JobProcessed;
use LaraGram\Queue\Events\JobProcessing;
use LaraGram\Surge\Events\RequestReceived;
use LaraGram\Surge\Events\RequestTerminated;
use LaraGram\Surge\Events\TaskReceived;
use LaraGram\Surge\Events\TickReceived;

class Listener
{
    /**
     * The queries that have been executed.
     *
     * @var array<int, array{connectionName: string, time: float, sql: string, bindings: array}>
     */
    protected $queries = [];

    /**
     * Register the appropriate listeners on the given event dispatcher.
     *
     * @param  \LaraGram\Contracts\Events\Dispatcher  $events
     * @return void
     */
    public function registerListeners(Dispatcher $events)
    {
        $events->listen(QueryExecuted::class, [$this, 'onQueryExecuted']);

        $events->listen([JobProcessing::class, JobProcessed::class], function () {
            $this->queries = [];
        });

        if (isset($_SERVER['LARAGRAM_SURGE'])) {
            $events->listen([RequestReceived::class, TaskReceived::class, TickReceived::class, RequestTerminated::class], function () {
                $this->queries = [];
            });
        }
    }

    /**
     * Returns the queries that have been executed.
     *
     * @return array<int, array{sql: string, time: float}>
     */
    public function queries()
    {
        return $this->queries;
    }

    /**
     * Listens for the query executed event.
     *
     * @param  \LaraGram\Database\Events\QueryExecuted  $event
     * @return void
     */
    public function onQueryExecuted(QueryExecuted $event)
    {
        if (count($this->queries) === 100) {
            return;
        }

        $this->queries[] = [
            'connectionName' => $event->connectionName,
            'time' => $event->time,
            'sql' => $event->sql,
            'bindings' => $event->bindings,
        ];
    }
}
