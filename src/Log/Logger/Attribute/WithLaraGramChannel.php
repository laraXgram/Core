<?php declare(strict_types=1);

namespace LaraGram\Log\Logger\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class WithLaraGramChannel
{
    public function __construct(
        public readonly string $channel
    ) {
    }
}
