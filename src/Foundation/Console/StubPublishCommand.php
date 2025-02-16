<?php

namespace LaraGram\Foundation\Console;

use LaraGram\Console\Command;
use LaraGram\Filesystem\Filesystem;
use LaraGram\Foundation\Events\PublishingStubs;
use LaraGram\Console\Attribute\AsCommand;

#[AsCommand(name: 'stub:publish')]
class StubPublishCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stub:publish
                    {--existing : Publish and overwrite only the files that have already been published}
                    {--force : Overwrite any existing files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish all stubs that are available for customization';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        if (! is_dir($stubsPath = $this->laragram->basePath('stubs'))) {
            (new Filesystem)->makeDirectory($stubsPath);
        }

        $stubs = [
            __DIR__ . '/stubs/cast.inbound.stub' => 'cast.inbound.stub',
            __DIR__ . '/stubs/cast.stub' => 'cast.stub',
            __DIR__ . '/stubs/class.stub' => 'class.stub',
            __DIR__ . '/stubs/class.invokable.stub' => 'class.invokable.stub',
            __DIR__ . '/stubs/console.stub' => 'console.stub',
            __DIR__ . '/stubs/enum.stub' => 'enum.stub',
            __DIR__ . '/stubs/enum.backed.stub' => 'enum.backed.stub',
            __DIR__ . '/stubs/model.pivot.stub' => 'model.pivot.stub',
            __DIR__ . '/stubs/model.stub' => 'model.stub',
            __DIR__ . '/stubs/observer.plain.stub' => 'observer.plain.stub',
            __DIR__ . '/stubs/observer.stub' => 'observer.stub',
            __DIR__ . '/stubs/provider.stub' => 'provider.stub',
            __DIR__ . '/stubs/scope.stub' => 'scope.stub',
            __DIR__ . '/stubs/trait.stub' => 'trait.stub',
            __DIR__ . '/stubs/controller.invokable.stub' => 'controller.invokable.stub',
            __DIR__ . '/stubs/controller.plain.stub' => 'controller.plain.stub',
            realpath(__DIR__.'/../../Database/Console/Factories/stubs/factory.stub') => 'factory.stub',
            realpath(__DIR__.'/../../Database/Console/Seeds/stubs/seeder.stub') => 'seeder.stub',
            realpath(__DIR__.'/../../Database/Migrations/stubs/migration.create.stub') => 'migration.create.stub',
            realpath(__DIR__.'/../../Database/Migrations/stubs/migration.stub') => 'migration.stub',
            realpath(__DIR__.'/../../Database/Migrations/stubs/migration.update.stub') => 'migration.update.stub',
        ];

        $this->laragram['events']->dispatch($event = new PublishingStubs($stubs));

        foreach ($event->stubs as $from => $to) {
            $to = $stubsPath.DIRECTORY_SEPARATOR.ltrim($to, DIRECTORY_SEPARATOR);

            if ((! $this->option('existing') && (! file_exists($to) || $this->option('force')))
                || ($this->option('existing') && file_exists($to))) {
                file_put_contents($to, file_get_contents($from));
            }
        }

        $this->components->info('Stubs published successfully.');
    }
}
