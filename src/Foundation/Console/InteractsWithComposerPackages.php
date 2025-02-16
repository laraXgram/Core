<?php

namespace LaraGram\Foundation\Console;

use LaraGram\Console\Process\Process;

trait InteractsWithComposerPackages
{
    /**
     * Installs the given Composer Packages into the application.
     *
     * @param  string  $composer
     * @param  array  $packages
     * @return bool
     */
    protected function requireComposerPackages(string $composer, array $packages, bool $write = false)
    {
        if ($composer !== 'global') {
            $command = ['php', $composer, 'require'];
        }

        $command = array_merge(
            $command ?? ['composer', 'require'],
            $packages,
        );

        return ! (new Process($command, $this->laragram->basePath(), ['COMPOSER_MEMORY_LIMIT' => '-1']))
            ->setTimeout(null)
            ->run(function ($type, $output) use ($write){
                if ($write === true) $this->output->write($output);
            });
    }

    /**
     * Get the path to the appropriate PHP binary.
     *
     * @return string
     */
    protected function phpBinary()
    {
        return 'php';
    }
}
