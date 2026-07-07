<?php

namespace LaraGram\Conversation;

use Closure;

/**
 * An immutable snapshot of a question's settings, produced by
 * {@see QuestionAccessor}. The engine works against this rather than the
 * mutable, user-facing {@see Question} builder.
 *
 * @internal
 */
final class QuestionDefinition
{
    public function __construct(
        public readonly string $prompt,
        public readonly ?string $name,
        public readonly string $type,
        public readonly string|array|null $rules,
        public readonly array $messages,
        public readonly ?string $skipCommand,
        public readonly mixed $keyboard,
        public readonly ?string $parseMode,
        public readonly ?Closure $callback,
        public readonly bool $deferred,
        public readonly ?Closure $sender,
        public readonly ?int $maxAttempts,
        public readonly string $promptKind,
        public readonly mixed $promptMedia,
        public readonly ?Back $back = null,
        public readonly ?Priority $priority = null,
    ) {
    }

    /**
     * Resolve the answer key for this question at the given position.
     *
     * @param  int  $index
     * @return int|string
     */
    public function key(int $index): int|string
    {
        return $this->name ?? $index;
    }
}
