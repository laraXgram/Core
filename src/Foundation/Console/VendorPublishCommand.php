<?php

namespace LaraGram\Foundation\Console;

use LaraGram\Console\Command;
use LaraGram\Filesystem\Filesystem;
use LaraGram\Foundation\Events\VendorTagPublished;
use LaraGram\Support\Arr;
use LaraGram\Support\ServiceProvider;
use LaraGram\Console\Attribute\AsCommand;

use function LaraGram\Console\Prompts\search;
use function LaraGram\Console\Prompts\select;

#[AsCommand(name: 'vendor:publish')]
class VendorPublishCommand extends Command
{
    /**
     * The filesystem instance.
     *
     * @var \LaraGram\Filesystem\Filesystem
     */
    protected $files;

    /**
     * The provider to publish.
     *
     * @var string|null
     */
    protected $provider = null;

    /**
     * The tags to publish.
     *
     * @var array
     */
    protected $tags = [];

    /**
     * The time the command started.
     *
     * @var \DateTimeInterface|null
     */
    protected $publishedAt;

    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'vendor:publish
                    {--existing : Publish and overwrite only the files that have already been published}
                    {--force : Overwrite any existing files}
                    {--all : Publish assets for all service providers without prompt}
                    {--provider= : The service provider that has assets you want to publish}
                    {--tag=* : One or many tags that have assets you want to publish}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish any publishable assets from vendor packages';

    /**
     * Indicates if migration dates should be updated while publishing.
     *
     * @var bool
     */
    protected static $updateMigrationDates = true;

    /**
     * Create a new command instance.
     *
     * @param  \LaraGram\Filesystem\Filesystem  $files
     * @return void
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->publishedAt = \date('Y-m-d H:i:s');

        $this->determineWhatShouldBePublished();

        foreach ($this->tags ?: [null] as $tag) {
            $this->publishTag($tag);
        }
    }

    /**
     * Determine the provider or tag(s) to publish.
     *
     * @return void
     */
    protected function determineWhatShouldBePublished()
    {
        if ($this->option('all')) {
            return;
        }

        [$this->provider, $this->tags] = [
            $this->option('provider'), (array) $this->option('tag'),
        ];

        if (! $this->provider && ! $this->tags) {
            $this->promptForProviderOrTag();
        }
    }

    /**
     * Prompt for which provider or tag to publish.
     *
     * @return void
     */
    protected function promptForProviderOrTag()
    {
        $choices = $this->publishableChoices();

        $choice = windows_os()
            ? select(
                "Which provider or tag's files would you like to publish?",
                $choices,
                scroll: 15,
            )
            : search(
                label: "Which provider or tag's files would you like to publish?",
                options: fn ($search) => array_values(array_filter(
                    $choices,
                    fn ($choice) => str_contains(strtolower($choice), strtolower($search))
                )),
                placeholder: 'Search...',
                scroll: 15,
            );

        if ($choice == $choices[0] || is_null($choice)) {
            return;
        }

        $this->parseChoice($choice);
    }

    /**
     * The choices available via the prompt.
     *
     * @return array
     */
    protected function publishableChoices()
    {
        return array_merge(
            ['All providers and tags'],
            preg_filter('/^/', '<fg=gray>Provider:</> ', Arr::sort(ServiceProvider::publishableProviders())),
            preg_filter('/^/', '<fg=gray>Tag:</> ', Arr::sort(ServiceProvider::publishableGroups()))
        );
    }

    /**
     * Parse the answer that was given via the prompt.
     *
     * @param  string  $choice
     * @return void
     */
    protected function parseChoice($choice)
    {
        [$type, $value] = explode(': ', strip_tags($choice));

        if ($type === 'Provider') {
            $this->provider = $value;
        } elseif ($type === 'Tag') {
            $this->tags = [$value];
        }
    }

    /**
     * Publishes the assets for a tag.
     *
     * @param  string  $tag
     * @return mixed
     */
    protected function publishTag($tag)
    {
        $pathsToPublish = $this->pathsToPublish($tag);

        if ($publishing = count($pathsToPublish) > 0) {
            $this->components->info(sprintf(
                'Publishing %sassets',
                $tag ? "[$tag] " : '',
            ));
        }

        foreach ($pathsToPublish as $from => $to) {
            $this->publishItem($from, $to);
        }

        if ($publishing === false) {
            $this->components->info('No publishable resources for tag ['.$tag.'].');
        } else {
            $this->laragram['events']->dispatch(new VendorTagPublished($tag, $pathsToPublish));

            $this->newLine();
        }
    }

    /**
     * Get all of the paths to publish.
     *
     * @param  string  $tag
     * @return array
     */
    protected function pathsToPublish($tag)
    {
        return ServiceProvider::pathsToPublish(
            $this->provider, $tag
        );
    }

    /**
     * Publish the given item from and to the given location.
     *
     * @param  string  $from
     * @param  string  $to
     * @return void
     */
    protected function publishItem($from, $to)
    {
        if ($this->files->isFile($from)) {
            return $this->publishFile($from, $to);
        } elseif ($this->files->isDirectory($from)) {
            return $this->publishDirectory($from, $to);
        }

        $this->components->error("Can't locate path: <{$from}>");
    }

    /**
     * Publish the file to the given path.
     *
     * @param  string  $from
     * @param  string  $to
     * @return void
     */
    protected function publishFile($from, $to)
    {
        if ((! $this->option('existing') && (! $this->files->exists($to) || $this->option('force')))
            || ($this->option('existing') && $this->files->exists($to))) {
            $to = $this->ensureMigrationNameIsUpToDate($from, $to);

            $this->createParentDirectory(dirname($to));

            $this->files->copy($from, $to);

            $this->status($from, $to, 'file');
        } else {
            if ($this->option('existing')) {
                $this->components->twoColumnDetail(sprintf(
                    'File [%s] does not exist',
                    str_replace($this->laragram->basePath() . 'VendorPublishCommand.php/', '', $to),
                ), '<fg=yellow;options=bold>SKIPPED</>');
            } else {
                $this->components->twoColumnDetail(sprintf(
                    'File [%s] already exists',
                    str_replace($this->laragram->basePath() . 'VendorPublishCommand.php/', '', realpath($to)),
                ), '<fg=yellow;options=bold>SKIPPED</>');
            }
        }
    }

    /**
     * Create the directory to house the published files if needed.
     *
     * @param  string  $directory
     * @return void
     */
    protected function createParentDirectory($directory)
    {
        if (! $this->files->isDirectory($directory)) {
            $this->files->makeDirectory($directory, 0755, true);
        }
    }

    /**
     * Ensure the given migration name is up-to-date.
     *
     * @param  string  $from
     * @param  string  $to
     * @return string
     */
    protected function ensureMigrationNameIsUpToDate($from, $to)
    {
        if (static::$updateMigrationDates === false) {
            return $to;
        }

        $from = realpath($from);

        foreach (ServiceProvider::publishableMigrationPaths() as $path) {
            $path = realpath($path);

            if ($from === $path && preg_match('/\d{4}_(\d{2})_(\d{2})_(\d{6})_/', $to)) {
                $this->publishedAt->modify('+1 second');

                return preg_replace(
                    '/\d{4}_(\d{2})_(\d{2})_(\d{6})_/',
                    $this->publishedAt->format('Y_m_d_His').'_',
                    $to,
                );
            }
        }

        return $to;
    }

    /**
     * Write a status message to the console.
     *
     * @param  string  $from
     * @param  string  $to
     * @param  string  $type
     * @return void
     */
    protected function status($from, $to, $type)
    {
        $from = str_replace($this->laragram->basePath() . 'VendorPublishCommand.php/', '', realpath($from));

        $to = str_replace($this->laragram->basePath() . 'VendorPublishCommand.php/', '', realpath($to));

        $this->components->task(sprintf(
            'Copying %s [%s] to [%s]',
            $type,
            $from,
            $to,
        ));
    }

    /**
     * Instruct the command to not update the dates on migrations when publishing.
     *
     * @return void
     */
    public static function dontUpdateMigrationDates()
    {
        static::$updateMigrationDates = false;
    }
}
