<?php

namespace LaraGram\Foundation\Exceptions;

use LaraGram\Console\View\Components\BulletList;
use LaraGram\Console\View\Components\Error;
use LaraGram\Container\Container;
use LaraGram\Contracts\Debug\ExceptionHandler as ExceptionHandlerContract;
use LaraGram\Support\Traits\ReflectsClosures;
use LaraGram\Console\ExtendedApplication as ConsoleApplication;
use LaraGram\Console\Exception\CommandNotFoundException;
use Throwable;
use WeakMap;

class Handler implements ExceptionHandlerContract
{
    use ReflectsClosures;

    /**
     * The container implementation.
     *
     * @var \LaraGram\Contracts\Container\Container
     */
    protected $container;

    /**
     * The already reported exception map.
     *
     * @var \WeakMap
     */
    protected $reportedExceptionMap;

    /**
     * Create a new exception handler instance.
     *
     * @param  \LaraGram\Contracts\Container\Container  $container
     * @return void
     */
    public function __construct(Container $container)
    {
        $this->container = $container;

        $this->reportedExceptionMap = new WeakMap;

        $this->register();
    }

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Render an exception to the console.
     *
     * @param  \LaraGram\Console\Output\OutputInterface  $output
     * @param  \Throwable  $e
     * @return void
     *
     * @internal This method is not meant to be used or overwritten outside the framework.
     */
    public function renderForConsole($output, Throwable $e)
    {
        if ($e instanceof CommandNotFoundException) {
            $messageParts = explode('.', $e->getMessage());
            $message = $messageParts[0] ?? '';

            if (!empty($alternatives = $e->getAlternatives())) {
                $message .= '. Did you mean one of these?';

                (new Error($output))->render($message);
                (new BulletList($output))->render($alternatives);

                $output->writeln('');
            } else {
                (new Error($output))->render($message);
            }

            return;
        }

        (new ConsoleApplication)->renderThrowable($e, $output);
    }
}
