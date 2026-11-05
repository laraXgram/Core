<?php

namespace LaraGram\Console\Prompts\Tui;

class TabBar
{
    /**
     * Render one row of tabs, keeping the active one in view.
     *
     * @param  list<array{label: string, color?: string|null, marker?: string}>  $tabs
     */
    public static function render(array $tabs, int $active, int $width): string
    {
        $cells = [];

        foreach ($tabs as $index => $tab) {
            $text = ' '.($tab['marker'] ?? '').($index + 1).':'.$tab['label'].' ';

            $cells[] = $index === $active
                ? "\e[1m".Ansi::inverse(Ansi::color($text, $tab['color'] ?? null))
                : Ansi::color($text, $tab['color'] ?? null);
        }

        $first = 0;

        while ($first < $active && Ansi::width(implode(' ', array_slice($cells, $first, $active - $first + 1))) > $width) {
            $first++;
        }

        return Ansi::truncate(implode(' ', array_slice($cells, $first)), $width);
    }
}
