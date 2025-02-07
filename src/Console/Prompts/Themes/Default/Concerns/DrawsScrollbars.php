<?php

namespace LaraGram\Console\Prompts\Themes\Default\Concerns;

trait DrawsScrollbars
{
    /**
     * Render a scrollbar beside the visible items.
     *
     * @template T of array<int, string>
     *
     * @param  T  $visible
     * @return T
     */
    protected function scrollbar(array $visible, int $firstVisible, int $height, int $total, int $width, string $color = 'cyan'): array
    {
        if ($height >= $total) {
            return $visible;
        }

        $scrollPosition = $this->scrollPosition($firstVisible, $height, $total);

        $lines = $visible;

        $result = array_map(fn ($line, $index) => match ($index) {
            $scrollPosition => preg_replace('/.$/', $this->{$color}('┃'), $this->pad($line, $width)) ?? '',
            default => preg_replace('/.$/', $this->gray('│'), $this->pad($line, $width)) ?? '',
        }, array_values($lines), range(0, count($lines) - 1));

        return $result;
    }

    /**
     * Return the position where the scrollbar "handle" should be rendered.
     */
    protected function scrollPosition(int $firstVisible, int $height, int $total): int
    {
        if ($firstVisible === 0) {
            return 0;
        }

        $maxPosition = $total - $height;

        if ($firstVisible === $maxPosition) {
            return $height - 1;
        }

        if ($height <= 2) {
            return -1;
        }

        $percent = $firstVisible / $maxPosition;

        return (int) round($percent * ($height - 3)) + 1;
    }
}
