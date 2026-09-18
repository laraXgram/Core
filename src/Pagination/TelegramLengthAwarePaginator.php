<?php

namespace LaraGram\Pagination;

use LaraGram\Pagination\Concerns\BuildsTelegramNavigation;

/**
 * @template TKey of array-key
 *
 * @template-covariant TValue
 *
 * @extends LengthAwarePaginator<TKey, TValue>
 */
class TelegramLengthAwarePaginator extends LengthAwarePaginator
{
    use BuildsTelegramNavigation;

    /**
     * The number of page links shown on each side of the current page.
     *
     * A keyboard row has far less room than a web page, so the window is
     * narrower than the one the web paginators use.
     *
     * @var int
     */
    public $onEachSide = 1;
}
