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
        public readonly string|Closure|null $prompt,
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
        public readonly ?Choices $choices = null,
        public readonly ?array $template = null,
        public readonly ?Closure $condition = null,
        public readonly ?Closure $transform = null,
        public readonly mixed $default = null,
        public readonly ?string $skipLabel = null,
        public readonly string|Closure|null $retry = null,
    ) {
    }

    /**
     * Resolve the prompt text for the given answers.
     *
     * @param  \LaraGram\Conversation\AnswersBag  $answers
     * @return string
     */
    public function prompt(AnswersBag $answers): string
    {
        return (string) ($this->prompt instanceof Closure
            ? ($this->prompt)($answers)
            : $this->prompt);
    }

    /**
     * Determine whether the question should be asked.
     *
     * @param  \LaraGram\Conversation\AnswersBag  $answers
     * @return bool
     */
    public function applies(AnswersBag $answers): bool
    {
        return $this->condition === null || (bool) ($this->condition)($answers);
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
