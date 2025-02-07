<?php

namespace LaraGram\Console\Concerns;

use LaraGram\Console\Input\InputOption;

trait CreatesMatchingTest
{
    /**
     * Add the standard command options for generating matching tests.
     *
     * @return void
     */
    protected function addTestOptions()
    {
        foreach (['test' => 'Test', 'pest' => 'Pest', 'phpunit' => 'PHPUnit'] as $option => $name) {
            $this->getDefinition()->addOption(new InputOption(
                $option,
                null,
                InputOption::VALUE_NONE,
                "Generate an accompanying {$name} test for the {$this->type}"
            ));
        }
    }

    /**
     * Create the matching test case if requested.
     *
     * @param  string  $path
     * @return bool
     */
    protected function handleTestCreation($path)
    {
        if (! $this->option('test') && ! $this->option('pest') && ! $this->option('phpunit')) {
            return false;
        }

        $path = $this->laragram['path'];
        $name = str_replace('\\', '/', substr($path, strlen($path), -4)) . 'Test';

        return $this->call('make:test', [
                'name' => $name,
                '--pest' => $this->option('pest'),
                '--phpunit' => $this->option('phpunit'),
            ]) == 0;

    }
}
