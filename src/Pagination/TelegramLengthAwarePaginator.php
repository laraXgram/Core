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
}
