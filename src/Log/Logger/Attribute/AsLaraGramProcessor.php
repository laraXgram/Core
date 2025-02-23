<?php declare(strict_types=1);

namespace LaraGram\Log\Logger\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class AsLaraGramProcessor
{
    /**
     * @param string|null $channel  The logging channel the processor should be pushed to.
     * @param string|null $handler  The handler the processor should be pushed to.
     * @param string|null $method   The method that processes the records (if the attribute is used at the class level).
     * @param int|null    $priority The priority of the processor so the order can be determined.
     */
    public function __construct(
        public readonly ?string $channel = null,
        public readonly ?string $handler = null,
        public readonly ?string $method = null,
        public readonly ?int $priority = null
    ) {
    }
}
