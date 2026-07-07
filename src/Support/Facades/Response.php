<?php

namespace LaraGram\Support\Facades;

use LaraGram\Contracts\Routing\ResponseFactory as ResponseFactoryContract;

/**
 * @method static \LaraGram\Http\Response make(mixed $content = '', int $status = 200, array $headers = [])
 * @method static \LaraGram\Http\Response noContent(int $status = 204, array $headers = [])
 * @method static \LaraGram\Http\Response view(string|array $view, array $data = [], int $status = 200, array $headers = [])
 * @method static \LaraGram\Http\JsonResponse json(mixed $data = [], int $status = 200, array $headers = [], int $options = 0)
 * @method static \LaraGram\Http\JsonResponse jsonp(string $callback, mixed $data = [], int $status = 200, array $headers = [], int $options = 0)
 * @method static \LaraGram\Http\StreamedResponse eventStream(\Closure $callback, array $headers = [], \LaraGram\Http\StreamedEvent|string|null $endStreamWith = '</stream>')
 * @method static \LaraGram\Http\StreamedResponse stream(callable|null $callback, int $status = 200, array $headers = [])
 * @method static \LaraGram\Http\StreamedJsonResponse streamJson(array $data, int $status = 200, array $headers = [], int $encodingOptions = 15)
 * @method static \LaraGram\Http\StreamedResponse streamDownload(callable $callback, string|null $name = null, array $headers = [], string|null $disposition = 'attachment')
 * @method static \LaraGram\Http\BinaryFileResponse download(\SplFileInfo|string $file, string|null $name = null, array $headers = [], string|null $disposition = 'attachment')
 * @method static \LaraGram\Http\BinaryFileResponse file(\SplFileInfo|string $file, array $headers = [])
 * @method static \LaraGram\Http\RedirectResponse redirectTo(string $path, int $status = 302, array $headers = [], bool|null $secure = null)
 * @method static \LaraGram\Http\RedirectResponse redirectToRoute(\BackedEnum|string $route, mixed $parameters = [], int $status = 302, array $headers = [])
 * @method static \LaraGram\Http\RedirectResponse redirectToAction(array|string $action, mixed $parameters = [], int $status = 302, array $headers = [])
 * @method static \LaraGram\Http\RedirectResponse redirectGuest(string $path, int $status = 302, array $headers = [], bool|null $secure = null)
 * @method static \LaraGram\Http\RedirectResponse redirectToIntended(string $default = '/', int $status = 302, array $headers = [], bool|null $secure = null)
 * @method static void macro(string $name, object|callable $macro)
 * @method static void mixin(object $mixin, bool $replace = true)
 * @method static bool hasMacro(string $name)
 * @method static void flushMacros()
 *
 * @see \LaraGram\Routing\ResponseFactory
 */
class Response extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return ResponseFactoryContract::class;
    }
}
