<?php

namespace LaraGram\Console\Prompts\Concerns;

use LaraGram\Console\Prompts\Output\BufferedConsoleOutput;

use function LaraGram\Console\Prompts\Convertor\render;
use function LaraGram\Console\Prompts\Convertor\renderUsing;

trait Convertor
{
    protected function convertor(string $html)
    {
        renderUsing($output = new BufferedConsoleOutput);

        render($html);

        return $this->restoreEscapeSequences($output->fetch());
    }

    protected function restoreEscapeSequences(string $string)
    {
        return preg_replace('/\[(\d+)m/', "\e[".'\1m', $string);
    }
}
