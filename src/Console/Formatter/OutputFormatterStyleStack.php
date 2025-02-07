<?php

namespace LaraGram\Console\Formatter;

use LaraGram\Console\Exception\InvalidArgumentException;

class OutputFormatterStyleStack
{
    /**
     * @var OutputFormatterStyleInterface[]
     */
    private array $styles = [];

    private OutputFormatterStyleInterface $emptyStyle;

    public function __construct(?OutputFormatterStyleInterface $emptyStyle = null)
    {
        $this->emptyStyle = $emptyStyle ?? new OutputFormatterStyle();
        $this->reset();
    }

    /**
     * Resets stack (ie. empty internal arrays).
     */
    public function reset(): void
    {
        $this->styles = [];
    }

    /**
     * Pushes a style in the stack.
     */
    public function push(OutputFormatterStyleInterface $style): void
    {
        $this->styles[] = $style;
    }

    /**
     * Pops a style from the stack.
     *
     * @throws InvalidArgumentException When style tags incorrectly nested
     */
    public function pop(?OutputFormatterStyleInterface $style = null): OutputFormatterStyleInterface
    {
        if (!$this->styles) {
            return $this->emptyStyle;
        }

        if (null === $style) {
            return array_pop($this->styles);
        }

        foreach (array_reverse($this->styles, true) as $index => $stackedStyle) {
            if ($style->apply('') === $stackedStyle->apply('')) {
                $this->styles = \array_slice($this->styles, 0, $index);

                return $stackedStyle;
            }
        }

        throw new InvalidArgumentException('Incorrectly nested style tag found.');
    }

    /**
     * Computes current style with stacks top codes.
     */
    public function getCurrent(): OutputFormatterStyleInterface
    {
        if (!$this->styles) {
            return $this->emptyStyle;
        }

        return $this->styles[\count($this->styles) - 1];
    }

    /**
     * @return $this
     */
    public function setEmptyStyle(OutputFormatterStyleInterface $emptyStyle): static
    {
        $this->emptyStyle = $emptyStyle;

        return $this;
    }

    public function getEmptyStyle(): OutputFormatterStyleInterface
    {
        return $this->emptyStyle;
    }
}
