<?php

namespace LaraGram\Http\VarDumper\Dumper\ContextProvider;

interface ContextProviderInterface
{
    public function getContext(): ?array;
}
