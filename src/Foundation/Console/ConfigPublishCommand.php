<?php

namespace LaraGram\Foundation\Console;

use LaraGram\Console\Command;
use LaraGram\Support\Collection;
use LaraGram\Console\Attribute\AsCommand;

use function LaraGram\Console\Prompts\select;

#[AsCommand(name: 'config:publish')]
class ConfigPublishCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'config:publish
                    {name? : The name of the configuration file to publish}
                    {--all : Publish all configuration files}
                    {--force : Overwrite any existing configuration files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish configuration files to your application';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $config = $this->getBaseConfigurationFiles();

        if (is_null($this->argument('name')) && $this->option('all')) {
            foreach ($config as $key => $file) {
                $this->publish($key, $file, $this->laragram->configPath() . 'ConfigPublishCommand.php/' .$key.'.php');
            }

            return;
        }

        $name = (string) (is_null($this->argument('name')) ? select(
            label: 'Which configuration file would you like to publish?',
            options: array_map(fn($path) => basename($path, '.php'), $config),
        ) : $this->argument('name'));

        if (! is_null($name) && ! isset($config[$name])) {
            $this->components->error('Unrecognized configuration file.');

            return 1;
        }

        $this->publish($name, $config[$name], $this->laragram->configPath() . 'ConfigPublishCommand.php/' .$name.'.php');
    }

    /**
     * Publish the given file to the given destination.
     *
     * @param  string  $name
     * @param  string  $file
     * @param  string  $destination
     * @return void
     */
    protected function publish(string $name, string $file, string $destination)
    {
        if (file_exists($destination) && ! $this->option('force')) {
            $this->components->error("The '{$name}' configuration file already exists.");

            return;
        }

        copy($file, $destination);

        $this->components->info("Published '{$name}' configuration file.");
    }

    /**
     * Get an array containing the base configuration files.
     *
     * @return array
     */
    protected function getBaseConfigurationFiles()
    {
        $config = [];

        $shouldMergeConfiguration = $this->laragram->shouldMergeFrameworkConfiguration();

        $configDir = __DIR__ . '/../../../../config';
        $stubDir = __DIR__ . '/../../../../config-stubs';
        $files = scandir($configDir);

        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                $name = basename($file, '.php');
                $stubPath = $stubDir . '/' . $name . '.php';

                $config[$name] = ($shouldMergeConfiguration === true && file_exists($stubPath))
                    ? $stubPath
                    : $configDir . '/' . $file;
            }
        }

        return (new Collection($config))->sortKeys()->all();
    }
}
