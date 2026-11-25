<?php

namespace LaraGram\Support;

if (\PHP_VERSION_ID >= 80600) {
    // PHP 8.6 ships SortDirection as a native enum; this name stays an alias of it.
    \class_alias(\SortDirection::class, SortDirection::class);
} elseif (\PHP_VERSION_ID >= 80100) {
    enum SortDirection
    {
        case Ascending;
        case Descending;
    }
}
