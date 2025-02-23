<?php declare(strict_types=1);

namespace LaraGram\Log\Logger;

interface ResettableInterface
{
    public function reset(): void;
}
