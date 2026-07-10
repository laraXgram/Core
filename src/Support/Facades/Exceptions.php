<?php

namespace LaraGram\Support\Facades;

use LaraGram\Contracts\Debug\ExceptionHandler;
use LaraGram\Support\Arr;
use LaraGram\Support\Testing\Fakes\ExceptionHandlerFake;

/**
 * @method static void register()
 * @method static \LaraGram\Foundation\Exceptions\ReportableHandler reportable(callable $reportUsing)
 * @method static \LaraGram\Foundation\Exceptions\Handler renderable(callable $renderUsing)
 * @method static \LaraGram\Foundation\Exceptions\Handler map(\Closure|string $from, \Closure|string|null $to = null)
 * @method static \LaraGram\Foundation\Exceptions\Handler dontReport(array|string $exceptions)
 * @method static \LaraGram\Foundation\Exceptions\Handler dontReportWhen(callable $dontReportWhen)
 * @method static \LaraGram\Foundation\Exceptions\Handler ignore(array|string $exceptions)
 * @method static \LaraGram\Foundation\Exceptions\Handler dontRetry(array|string $exceptions)
 * @method static \LaraGram\Foundation\Exceptions\Handler dontRetryWhen(callable $dontRetryWhen)
 * @method static bool shouldStopRetries(\Throwable $e)
 * @method static \LaraGram\Foundation\Exceptions\Handler dontFlash(array|string $attributes)
 * @method static \LaraGram\Foundation\Exceptions\Handler level(string $type, string $level)
 * @method static void report(\Throwable $e)
 * @method static bool isReporting(\Throwable $e)
 * @method static bool shouldReport(\Throwable $e)
 * @method static \LaraGram\Foundation\Exceptions\Handler throttleUsing(callable $throttleUsing)
 * @method static \LaraGram\Foundation\Exceptions\Handler stopIgnoring(array|string $exceptions)
 * @method static array buildContextForException(\Throwable $e)
 * @method static \LaraGram\Foundation\Exceptions\Handler buildContextUsing(\Closure $contextCallback)
 * @method static \LaraGram\Http\BaseResponse render(\LaraGram\Http\Request $request, \Throwable $e)
 * @method static \LaraGram\Foundation\Exceptions\Handler respondUsing(callable $callback)
 * @method static \LaraGram\Foundation\Exceptions\Handler shouldRenderJsonWhen(callable $callback)
 * @method static \LaraGram\Foundation\Exceptions\Handler dontReportDuplicates()
 * @method static \LaraGram\Contracts\Debug\ExceptionHandler handler()
 * @method static void renderForConsole(\LaraGram\Console\Output\OutputInterface $output, \Throwable $e)
 * @method static \LaraGram\Support\Testing\Fakes\ExceptionHandlerFake throwOnReport()
 * @method static \LaraGram\Support\Testing\Fakes\ExceptionHandlerFake throwFirstReported()
 * @method static array reported()
 * @method static \LaraGram\Support\Testing\Fakes\ExceptionHandlerFake setHandler(\LaraGram\Contracts\Debug\ExceptionHandler $handler)
 *
 * @see \LaraGram\Foundation\Exceptions\Handler
 * @see \LaraGram\Support\Testing\Fakes\ExceptionHandlerFake
 */
class Exceptions extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return ExceptionHandler::class;
    }
}
