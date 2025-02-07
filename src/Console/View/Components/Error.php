<?php

namespace LaraGram\Console\View\Components;

use LaraGram\Console\Output\OutputInterface;

class Error extends Component
{
    /**
     * Renders the component using the given arguments.
     *
     * @param  string  $string
     * @param  int  $verbosity
     * @return void
     */
    public function render($string, $verbosity = OutputInterface::VERBOSITY_NORMAL)
    {
        $line = new Line($this->output);
        $line->render('error', $string, $verbosity);
    }
}
