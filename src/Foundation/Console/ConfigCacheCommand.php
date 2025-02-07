<?php

namespace LaraGram\Foundation\Console;

use LaraGram\Console\Command;
use LaraGram\Filesystem\Filesystem;
use LaraGram\Support\Facades\Console;
use LogicException;
use Throwable;

class ConfigCacheCommand extends Command
{
    protected $signature = 'config:cache';
    protected $description = 'Create a cache file for faster configuration loading';

    protected Filesystem $files;

    public function handle()
    {
        if ($this->getOption('h') == 'h') Console::output()->message($this->description, true);

        $this->files = new Filesystem();

        $configPath = $this->app->getCachedConfigPath();

        Console::callSilent('config:clear');

        $config = $this->getFreshConfiguration();

        $this->files->put(
            $configPath, '<?php return '.var_export($config, true).';'.PHP_EOL
        );

        try {
            require $configPath;
        } catch (Throwable $e) {
            $this->files->delete($configPath);

            throw new LogicException('Your configuration files are not serializable.', 0, $e);
        }

        Console::output()->success('Configs cached!', true);
    }

    protected function getFreshConfiguration()
    {
        $this->app->bootstrap();

        return $this->app['config']->all();
    }
}