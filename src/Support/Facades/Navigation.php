<?php

namespace LaraGram\Support\Facades;

/**
 * @method static \LaraGram\Listening\Navigation\Breadcrumb|null push(string|null $name = null, string|null $title = null, \LaraGram\Listening\Listen|null $listen = null)
 * @method static \LaraGram\Listening\Navigation\BackResponse back(int|string $to = 1, \Closure|string|null $fallback = null)
 * @method static \LaraGram\Request\Response navigate(int|string $to = 1, \Closure|string|null $fallback = null, \LaraGram\Request\Request|null $request = null)
 * @method static array trail()
 * @method static string breadcrumbs(string $separator = ' › ')
 * @method static \LaraGram\Listening\Navigation\Breadcrumb|null current()
 * @method static \LaraGram\Listening\Navigation\Breadcrumb|null previous()
 * @method static bool canGoBack(int|string $to = 1)
 * @method static bool isGoingBack()
 * @method static void clear()
 *
 * @see \LaraGram\Listening\Navigation\Navigator
 */
class Navigation extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'navigation';
    }
}
