<?php

namespace LaraGram\Contracts\Debug;

use Throwable;

interface ExceptionHandler
{
    /**
     * Render an exception to the console.
     *
     * @param  \LaraGram\Console\Output\OutputInterface  $output
     * @param  \Throwable  $e
     * @return void
     *
     * @internal This method is not meant to be used or overwritten outside the framework.
     */
    public function renderForConsole($output, Throwable $e);
}
