<?php

namespace LaraGram\Console\Helper;

class TableSeparator extends TableCell
{
    public function __construct(array $options = [])
    {
        parent::__construct('', $options);
    }
}
