<?php

namespace LaraGram\Foundation\Console;

use LaraGram\Console\Command;
use LaraGram\Filesystem\Filesystem;
use LaraGram\Console\Attribute\AsCommand;

#[AsCommand(name: 'install:api')]
class ApiInstallCommand extends Command
{
    use InteractsWithComposerPackages;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'install:api
                    {--force : Overwrite any existing API routes file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create an API routes file';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        if (file_exists($apiRoutesPath = $this->laragram->basePath('routes/api.php')) &&
            ! $this->option('force')) {
            $this->components->error('API routes file already exists.');
        } else {
            $this->components->info('Published API routes file.');

            copy(__DIR__.'/stubs/api-routes.stub', $apiRoutesPath);

            $this->uncommentApiRoutesFile();
        }
    }

    /**
     * Uncomment the API routes file in the application bootstrap file.
     *
     * @return void
     */
    protected function uncommentApiRoutesFile()
    {
        $appBootstrapPath = $this->laragram->bootstrapPath('app.php');

        $content = file_get_contents($appBootstrapPath);

        if (str_contains($content, '// api: ')) {
            (new Filesystem)->replaceInFile(
                '// api: ',
                'api: ',
                $appBootstrapPath,
            );
        } elseif (str_contains($content, 'web: __DIR__.\'/../routes/web.php\',')) {
            (new Filesystem)->replaceInFile(
                'web: __DIR__.\'/../routes/web.php\',',
                'web: __DIR__.\'/../routes/web.php\','.PHP_EOL.'        api: __DIR__.\'/../routes/api.php\',',
                $appBootstrapPath,
            );
        } else {
            $this->components->warn("Unable to automatically add API route definition to [{$appBootstrapPath}]. API route file should be registered manually.");

            return;
        }
    }
}
