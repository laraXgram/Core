<?php

namespace LaraGram\Console\Prompts\Tui;

class KeyHints
{
    /**
     * Render one row of key bindings, with optional text on its right.
     *
     * @param  array<string, string>  $bindings
     */
    public static function render(array $bindings, int $width, string $right = ''): string
    {
        $hints = [];

        foreach ($bindings as $key => $action) {
            $hints[] = Ansi::bold($key).' '.Ansi::dim($action);
        }

        $left = Ansi::truncate(implode('  ', $hints), max(0, $width - Ansi::width($right) - 1));

        return $left.str_repeat(' ', max(0, $width - Ansi::width($left) - Ansi::width($right))).$right;
    }
}
