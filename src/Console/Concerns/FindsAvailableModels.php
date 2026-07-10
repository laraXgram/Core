<?php

namespace LaraGram\Console\Concerns;

use LaraGram\Support\Collection;
use LaraGram\Support\Finder\Finder;

trait FindsAvailableModels
{
    /**
     * Get a list of possible model names.
     *
     * @return array<int, string>
     */
    protected function findAvailableModels()
    {
        $modelPath = is_dir(app_path('Models')) ? app_path('Models') : app_path();

        return (new Collection(Finder::create()->files()->depth(0)->in($modelPath)))
            ->map(fn ($file) => $file->getBasename('.php'))
            ->sort()
            ->values()
            ->all();
    }
}
