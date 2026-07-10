<?php

namespace LaraGram\Http\VarDumper\Dumper;

use LaraGram\Http\VarDumper\Cloner\Data;

interface DataDumperInterface
{
    public function dump(Data $data): ?string;
}
