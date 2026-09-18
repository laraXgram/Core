<?php

namespace LaraGram\Broadcasting\Console;

use LaraGram\Console\Attribute\AsCommand;
use LaraGram\Console\Command;
use LaraGram\Filesystem\Filesystem;

#[AsCommand(name: 'install:broadcasting')]
class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'install:broadcasting
                    {--force : Overwrite the existing channels file}
                    {--without-migration : Do not create the broadcast tables migration}
                    {--without-tracking : Do not register the TrackChats bot middleware}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set up broadcasting: channels file, tables and chat tracking';

    /**
     * Execute the console command.
     *
     * @param  \LaraGram\Filesystem\Filesystem  $files
     * @return int
     */
    public function handle(Filesystem $files)
    {
        if (! file_exists($this->laragram->configPath('broadcasting.php'))) {
            $this->call('config:publish', ['name' => 'broadcasting']);
        }

        $this->createChannelsFile($files);

        $this->registerBroadcasting($files);

        if (! $this->option('without-tracking')) {
            $this->registerChatTracking($files);
        }

        if (! $this->option('without-migration')) {
            $this->call('make:broadcast-tables');

            $this->components->info('Run [php laragram migrate] to create the broadcast tables.');
        }

        return 0;
    }

    /**
     * Create the channels file.
     *
     * @param  \LaraGram\Filesystem\Filesystem  $files
     * @return void
     */
    protected function createChannelsFile(Filesystem $files): void
    {
        $path = $this->laragram->basePath('listens/channels.php');

        if (file_exists($path) && ! $this->option('force')) {
            $this->components->info('The [listens/channels.php] file already exists.');

            return;
        }

        $files->ensureDirectoryExists(dirname($path));

        $files->copy(__DIR__.'/stubs/channels.stub', $path);

        $this->components->info('Created [listens/channels.php].');
    }

    /**
     * Load the channels file from the application bootstrap file.
     *
     * @param  \LaraGram\Filesystem\Filesystem  $files
     * @return void
     */
    protected function registerBroadcasting(Filesystem $files): void
    {
        $path = $this->laragram->bootstrapPath('app.php');

        $content = file_exists($path) ? $files->get($path) : '';

        if (str_contains($content, 'withBroadcasting(')) {
            return;
        }

        if (str_contains($content, '->withMiddleware(')) {
            $files->put($path, preg_replace(
                '/^(\s*)->withMiddleware\(/m',
                "$1->withBroadcasting(__DIR__.'/../listens/channels.php')\n$1->withMiddleware(",
                $content,
                1
            ));

            $this->components->info('Registered the channels file in [bootstrap/app.php].');

            return;
        }

        $this->components->warn(
            "Add ->withBroadcasting(__DIR__.'/../listens/channels.php') to [bootstrap/app.php]."
        );
    }

    /**
     * Register the TrackChats middleware in the bot middleware group.
     *
     * @param  \LaraGram\Filesystem\Filesystem  $files
     * @return void
     */
    protected function registerChatTracking(Filesystem $files): void
    {
        $path = $this->laragram->bootstrapPath('app.php');

        $content = file_exists($path) ? $files->get($path) : '';

        if (str_contains($content, 'TrackChats')) {
            return;
        }

        $pattern = '/(->withMiddleware\(function \(Middleware \$middleware\)(?:: void)? \{\s*)\/\/(\s*\}\))/';

        if (preg_match($pattern, $content)) {
            $files->put($path, preg_replace(
                $pattern,
                "$1\$middleware->bot(append: [\n            \\LaraGram\\Broadcasting\\Telegram\\Middleware\\TrackChats::class,\n        ]);$2",
                $content,
                1
            ));

            $this->components->info('Registered the TrackChats bot middleware in [bootstrap/app.php].');

            return;
        }

        $this->components->warn(
            'Add $middleware->bot(append: [\LaraGram\Broadcasting\Telegram\Middleware\TrackChats::class]) to withMiddleware() in [bootstrap/app.php].'
        );
    }
}
