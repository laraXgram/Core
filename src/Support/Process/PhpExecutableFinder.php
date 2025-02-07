<?php

namespace LaraGram\Support\Process;

use LaraGram\Console\Process\ExecutableFinder;
use LaraGram\Console\Process\PhpExecutableFinder as LaraGramPhpExecutableFinder;

class PhpExecutableFinder extends LaraGramPhpExecutableFinder
{
    /**
     * Finds The PHP executable.
     */
    #[\Override]
    public function find(bool $includeArgs = true): string|false
    {
        if ($herdPath = getenv('HERD_HOME')) {
            return (new ExecutableFinder)->find('php', false, [implode(DIRECTORY_SEPARATOR, [$herdPath, 'bin'])]);
        }

        return parent::find($includeArgs);
    }
}
