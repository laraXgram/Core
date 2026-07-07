<?php

namespace LaraGram\Support;

if (\PHP_VERSION_ID < 80600 && \PHP_VERSION_ID >= 80100) {
    enum SortDirection
    {
        case Ascending;
        case Descending;
    }
}
