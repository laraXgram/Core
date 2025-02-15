<?php

namespace LaraGram\Console\Concerns;

use LaraGram\Console\Signals;
use LaraGram\Support\Collection;

trait InteractsWithSignals
{
    /**
     * The signal registrar instance.
     *
     * @var \LaraGram\Console\Signals|null
     */
    protected $signals;

    /**
     * Define a callback to be run when the given signal(s) occurs.
     *
     * @template TSignals of iterable<array-key, int>|int
     *
     * @param  (\Closure():(TSignals))|TSignals  $signals
     * @param  callable(int $signal): void  $callback
     * @return void
     */
    public function trap($signals, $callback)
    {
        Signals::whenAvailable(function () use ($signals, $callback) {
            $this->signals ??= new Signals(
                $this->getApplication()->getSignalRegistry(),
            );

            Collection::wrap(value($signals))
                ->each(fn ($signal) => $this->signals->register($signal, $callback));
        });
    }

    /**
     * Untrap signal handlers set within the command's handler.
     *
     * @return void
     *
     * @internal
     */
    public function untrap()
    {
        if (! is_null($this->signals)) {
            $this->signals->unregister();

            $this->signals = null;
        }
    }
}
