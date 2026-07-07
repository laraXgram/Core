<?php

namespace LaraGram\Http\VarDumper\Dumper;

use LaraGram\Http\VarDumper\Cloner\Data;
use LaraGram\Http\VarDumper\Dumper\ContextProvider\ContextProviderInterface;

class ContextualizedDumper implements DataDumperInterface
{
    /**
     * @param ContextProviderInterface[] $contextProviders
     */
    public function __construct(
        private DataDumperInterface $wrappedDumper,
        private array $contextProviders,
    ) {
    }

    public function dump(Data $data): ?string
    {
        $context = $data->getContext();
        foreach ($this->contextProviders as $contextProvider) {
            $context[$contextProvider::class] = $contextProvider->getContext();
        }

        return $this->wrappedDumper->dump($data->withContext($context));
    }
}
