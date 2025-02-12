<?php

namespace LaraGram\Foundation\Bootstrap;

use Exception;
use LaraGram\Contracts\Debug\ExceptionHandler;
use LaraGram\Contracts\Foundation\Application;
use LaraGram\Console\Output\ConsoleOutput;
use Throwable;

class HandleExceptions
{
    /**
     * Reserved memory so that errors can be displayed properly on memory exhaustion.
     *
     * @var string|null
     */
    public static $reservedMemory;

    /**
     * The application instance.
     *
     * @var \LaraGram\Contracts\Foundation\Application
     */
    protected static $app;

    /**
     * Bootstrap the given application.
     *
     * @param  \LaraGram\Contracts\Foundation\Application  $app
     * @return void
     */
    public function bootstrap(Application $app)
    {
        static::$reservedMemory = str_repeat('x', 32768);

        static::$app = $app;

        error_reporting(-1);

        set_exception_handler($this->forwardsTo('handleException'));
    }

    /**
     * Handle an uncaught exception from the application.
     *
     * Note: Most exceptions can be handled via the try / catch block in
     * the HTTP and Console kernels. But, fatal error exceptions must
     * be handled differently since they are not normal exceptions.
     *
     * @param  \Throwable  $e
     * @return void
     */
    public function handleException(Throwable $e)
    {
        static::$reservedMemory = null;

        if (static::$app->runningInConsole()) {
            $this->renderForConsole($e);
        }
    }

    /**
     * Render an exception to the console.
     *
     * @param  \Throwable  $e
     * @return void
     */
    protected function renderForConsole(Throwable $e)
    {
        $this->getExceptionHandler()->renderForConsole(new ConsoleOutput, $e);
    }

    /**
     * Forward a method call to the given method if an application instance exists.
     *
     * @return callable
     */
    protected function forwardsTo($method)
    {
        return fn (...$arguments) => static::$app
            ? $this->{$method}(...$arguments)
            : false;
    }


    /**
     * Get an instance of the exception handler.
     *
     * @return \LaraGram\Contracts\Debug\ExceptionHandler
     */
    protected function getExceptionHandler()
    {
        return static::$app->make(ExceptionHandler::class);
    }
}
