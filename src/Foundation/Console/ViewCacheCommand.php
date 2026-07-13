<?php

namespace LaraGram\Foundation\Console;

use LaraGram\Console\Command;
use LaraGram\Support\Collection;
use LaraGram\Console\Attribute\AsCommand;
use LaraGram\Console\Output\OutputInterface;
use LaraGram\Support\Finder\Finder;
use LaraGram\Support\Finder\SplFileInfo;

#[AsCommand(name: 'view:cache')]
class ViewCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'view:cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Compile all of the application's Blade templates";

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->callSilent('view:clear');

        $this->paths()->each(function ($path) {
            $prefix = $this->output->isVeryVerbose() ? '<fg=yellow;options=bold>DIR</> ' : '';

            $this->components->task($prefix.$path, null, OutputInterface::VERBOSITY_VERBOSE);

            $this->compileViews($this->bladeFilesIn([$path]));
        });

        $this->newLine();

        $this->components->info('Blade templates cached successfully.');
    }

    /**
     * Compile the given view files.
     *
     * @param  \LaraGram\Support\Collection  $views
     * @return void
     */
    protected function compileViews(Collection $views)
    {
        $compiler = $this->laragram['view']->getEngineResolver()->resolve('blade')->getCompiler();

        $views->map(function (SplFileInfo $file) use ($compiler) {
            $this->components->task('    '.$file->getRelativePathname(), null, OutputInterface::VERBOSITY_VERY_VERBOSE);

            $compiler->compile($file->getRealPath());
        });

        if ($this->output->isVeryVerbose()) {
            $this->newLine();
        }
    }

    /**
     * Get the Blade files in the given path.
     *
     * @param  array  $paths
     * @return \LaraGram\Support\Collection
     */
    protected function bladeFilesIn(array $paths)
    {
        $extensions = (new Collection($this->laragram['view']->getExtensions()))
            ->filter(fn ($value) => $value === 'blade')
            ->keys()
            ->map(fn ($extension) => "*.{$extension}")
            ->all();

        return new Collection(
            Finder::create()
                ->in($paths)
                ->exclude('vendor')
                ->name($extensions)
                ->files()
        );
    }

    /**
     * Get all of the possible view paths.
     *
     * @return \LaraGram\Support\Collection
     */
    protected function paths()
    {
        $finder = $this->laragram['view']->getFinder();

        $paths = (new Collection($finder->getPaths()))->merge(
            (new Collection($finder->getHints()))->flatten()
        )->unique();

        return $paths->reject(fn ($path) => $paths->contains(function ($existing) use ($path) {
            return $existing !== $path && str_starts_with(realpath($path) ?: $path, realpath($existing) ?: $existing);
        }))->values();
    }
}
