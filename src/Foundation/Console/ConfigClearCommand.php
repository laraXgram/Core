<?php

namespace LaraGram\Foundation\Console;

use LaraGram\Console\Command;
use LaraGram\Filesystem\Filesystem;
use LaraGram\Support\Facades\Console;
use LogicException;
use Throwable;

class ConfigClearCommand extends Command
{
    protected $signature = 'config:clear';
    protected $description = 'Remove the configuration cache file';

    protected Filesystem $files;

    public function handle()
    {
        if ($this->getOption('h') == 'h') Console::output()->message($this->description, true);

        $this->files = new Filesystem();

        $configPath = $this->app->getCachedConfigPath();

        $this->files->delete($configPath);

        if (!$this->silent){
            Console::output()->success('Configs cleared!', true);
        }
    }
}