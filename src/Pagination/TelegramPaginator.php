<?php

namespace LaraGram\Pagination;

use LaraGram\Pagination\Concerns\BuildsTelegramNavigation;

/**
 * @template TKey of array-key
 *
 * @template-covariant TValue
 *
 * @extends Paginator<TKey, TValue>
 */
class TelegramPaginator extends Paginator
{
    use BuildsTelegramNavigation;

    /**
     * Get the current page for the request.
     *
     * @param  int  $currentPage
     * @return int
     */
    protected function setCurrentPage($currentPage)
    {
        $currentPage = $currentPage ?: static::resolveCurrentPage($this->pageName);

        return $this->isValidPageNumber($currentPage) ? (int) $currentPage : 1;
    }
}
