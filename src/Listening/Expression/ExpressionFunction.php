<?php

namespace LaraGram\Listening\Expression;

class ExpressionFunction
{
    private \Closure $compiler;
    private \Closure $evaluator;

    /**
     * @param string   $name      The function name
     * @param callable $compiler  A callable able to compile the function
     * @param callable $evaluator A callable able to evaluate the function
     */
    public function __construct(
        private string $name,
        callable $compiler,
        callable $evaluator,
    ) {
        $this->compiler = $compiler(...);
        $this->evaluator = $evaluator(...);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCompiler(): \Closure
    {
        return $this->compiler;
    }

    public function getEvaluator(): \Closure
    {
        return $this->evaluator;
    }

    /**
     * Creates an ExpressionFunction from a PHP function name.
     *
     * @param string|null $expressionFunctionName The expression function name (default: same than the PHP function name)
     *
     * @throws \InvalidArgumentException if given PHP function name does not exist
     * @throws \InvalidArgumentException if given PHP function name is in namespace
     *                                   and expression function name is not defined
     */
    public static function fromPhp(string $phpFunctionName, ?string $expressionFunctionName = null): self
    {
        $phpFunctionName = ltrim($phpFunctionName, '\\');
        if (!\function_exists($phpFunctionName)) {
            throw new \InvalidArgumentException(\sprintf('PHP function "%s" does not exist.', $phpFunctionName));
        }

        $parts = explode('\\', $phpFunctionName);
        if (!$expressionFunctionName && \count($parts) > 1) {
            throw new \InvalidArgumentException(\sprintf('An expression function name must be defined when PHP function "%s" is namespaced.', $phpFunctionName));
        }

        $compiler = fn (...$args) => \sprintf('\%s(%s)', $phpFunctionName, implode(', ', $args));

        $evaluator = fn ($p, ...$args) => $phpFunctionName(...$args);

        return new self($expressionFunctionName ?: end($parts), $compiler, $evaluator);
    }
}
