<?php

namespace LaraGram\Foundation\Console;

use FilesystemIterator;
use LaraGram\Console\Attribute\AsCommand;
use LaraGram\Console\Command;
use LaraGram\Console\Output\OutputInterface;
use LaraGram\Support\Collection;
use LaraGram\Support\Finder\Finder;
use LaraGram\Support\Finder\SplFileInfo;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

#[AsCommand(name: 'template:cache')]
class TemplateCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'template:cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Compile all of the application's Temple8 templates";

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->callSilent('template:clear');

        $this->paths()->each(function ($path) {
            $prefix = $this->output->isVeryVerbose() ? '<fg=yellow;options=bold>DIR</> ' : '';

            $this->components->task($prefix.$path, null, OutputInterface::VERBOSITY_VERBOSE);

            $this->compileTemplates($this->temple8FilesIn([$path]));
        });

        $this->newLine();

        $this->components->info('Temple8 templates cached successfully.');
    }

    /**
     * Compile the given template files.
     *
     * @param  \LaraGram\Support\Collection  $templates
     * @return void
     */
    protected function compileTemplates(Collection $templates)
    {
        $compiler = $this->laragram['template']->getEngineResolver()->resolve('temple8')->getCompiler();

        $templates->map(function (SplFileInfo $file) use ($compiler) {
            $this->components->task('    '.$file->getRelativePathname(), null, OutputInterface::VERBOSITY_VERY_VERBOSE);

            $compiler->compile($file->getRealPath());
        });

        if ($this->output->isVeryVerbose()) {
            $this->newLine();
        }
    }

    /**
     * Get the Temple8 files in the given path.
     *
     * @param  array  $paths
     * @return \LaraGram\Support\Collection
     */
    protected function temple8FilesIn(array $paths)
    {
        $extensions = (new Collection($this->laragram['template']->getExtensions()))
            ->filter(fn ($value) => $value === 't8')
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
     * Get all of the possible template paths.
     *
     * @return \LaraGram\Support\Collection
     */
    protected function paths()
    {
        $finder = $this->laragram['template']->getFinder();

        return (new Collection($finder->getPaths()))->merge(
            (new Collection($finder->getHints()))->flatten()
        );
    }
}
