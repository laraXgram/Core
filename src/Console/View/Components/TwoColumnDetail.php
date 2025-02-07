<?php

namespace LaraGram\Console\View\Components;

use LaraGram\Console\Output\OutputInterface;

class TwoColumnDetail extends Component
{
    /**
     * Renders the component using the given arguments.
     *
     * @param  string  $first
     * @param  string|null  $second
     * @param  int  $verbosity
     * @return void
     */
    public function render($first, $second = null, $verbosity = OutputInterface::VERBOSITY_NORMAL)
    {
        $first = $this->mutate($first, [
            Mutators\EnsureDynamicContentIsHighlighted::class,
            Mutators\EnsureNoPunctuation::class,
            Mutators\EnsureRelativePaths::class,
        ]);

        $second = $this->mutate($second, [
            Mutators\EnsureDynamicContentIsHighlighted::class,
            Mutators\EnsureNoPunctuation::class,
            Mutators\EnsureRelativePaths::class,
        ]);

        $this->renderView('two-column-detail', [
            'first' => $first,
            'second' => $second,
        ], $verbosity);
    }
}
