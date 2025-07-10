<?php

namespace LaraGram\Support\Testing\Fakes;

use LaraGram\Contracts\Debug\ExceptionHandler;
use LaraGram\Foundation\Testing\Concerns\WithoutExceptionHandlingHandler;
use LaraGram\Support\Traits\ForwardsCalls;
use LaraGram\Support\Traits\ReflectsClosures;
use Throwable;

/**
 * @mixin \LaraGram\Foundation\Exceptions\Handler
 */
class ExceptionHandlerFake implements ExceptionHandler, Fake
{
    use ForwardsCalls, ReflectsClosures;

    /**
     * All of the exceptions that have been reported.
     *
     * @var list<\Throwable>
     */
    protected $reported = [];

    /**
     * If the fake should throw exceptions when they are reported.
     *
     * @var bool
     */
    protected $throwOnReport = false;

    /**
     * Create a new exception handler fake.
     *
     * @param  \LaraGram\Contracts\Debug\ExceptionHandler  $handler
     * @param  list<class-string<\Throwable>>  $exceptions
     * @return void
     */
    public function __construct(
        protected ExceptionHandler $handler,
        protected array $exceptions = [],
    ) {
        //
    }

    /**
     * Get the underlying handler implementation.
     *
     * @return \LaraGram\Contracts\Debug\ExceptionHandler
     */
    public function handler()
    {
        return $this->handler;
    }

    /**
     * Report or log an exception.
     *
     * @param  \Throwable  $e
     * @return void
     */
    public function report($e)
    {
        if (! $this->isFakedException($e)) {
            $this->handler->report($e);

            return;
        }

        if (! $this->shouldReport($e)) {
            return;
        }

        $this->reported[] = $e;

        if ($this->throwOnReport) {
            throw $e;
        }
    }

    /**
     * Determine if the given exception is faked.
     *
     * @param  \Throwable  $e
     * @return bool
     */
    protected function isFakedException(Throwable $e)
    {
        return count($this->exceptions) === 0 || in_array(get_class($e), $this->exceptions, true);
    }

    /**
     * Determine if the exception should be reported.
     *
     * @param  \Throwable  $e
     * @return bool
     */
    public function shouldReport($e)
    {
        return $this->runningWithoutExceptionHandling() || $this->handler->shouldReport($e);
    }

    /**
     * Determine if the handler is running without exception handling.
     *
     * @return bool
     */
    protected function runningWithoutExceptionHandling()
    {
        return $this->handler instanceof WithoutExceptionHandlingHandler;
    }

    /**
     * Render an exception to the console.
     *
     * @param  \LaraGram\Console\Output\OutputInterface  $output
     * @param  \Throwable  $e
     * @return void
     */
    public function renderForConsole($output, Throwable $e)
    {
        $this->handler->renderForConsole($output, $e);
    }

    /**
     * Throw exceptions when they are reported.
     *
     * @return $this
     */
    public function throwOnReport()
    {
        $this->throwOnReport = true;

        return $this;
    }

    /**
     * Throw the first reported exception.
     *
     * @return $this
     *
     * @throws \Throwable
     */
    public function throwFirstReported()
    {
        foreach ($this->reported as $e) {
            throw $e;
        }

        return $this;
    }

    /**
     * Set the "original" handler that should be used by the fake.
     *
     * @param  \LaraGram\Contracts\Debug\ExceptionHandler  $handler
     * @return $this
     */
    public function setHandler(ExceptionHandler $handler)
    {
        $this->handler = $handler;

        return $this;
    }

    /**
     * Handle dynamic method calls to the handler.
     *
     * @param  string  $method
     * @param  array<string, mixed>  $parameters
     * @return mixed
     */
    public function __call(string $method, array $parameters)
    {
        return $this->forwardCallTo($this->handler, $method, $parameters);
    }
}
