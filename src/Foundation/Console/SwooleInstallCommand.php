<?php

namespace LaraGram\Foundation\Console;

use LaraGram\Console\Command;
use LaraGram\Console\Attribute\AsCommand;
use function LaraGram\Console\Prompts\spin;

#[AsCommand(name: 'install:swoole')]
class SwooleInstallCommand extends Command
{
    use InteractsWithComposerPackages;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'install:swoole
                    {--composer=global : Absolute path to the Composer binary which should be used to install packages}
                    {--force : Overwrite any existing API routes file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install Swoole IDE helper package';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if (extension_loaded('openswoole')) {
            spin(function (){
                $this->requireComposerPackages($this->option('composer'), [
                    'openswoole/core',
                ]);
            }, "<fg=yellow>Installing Openswoole core...</>");

            $this->components->success("Openswoole core installed successfully!");
        } elseif (extension_loaded('swoole')) {
            spin(function (){
                $this->requireComposerPackages($this->option('composer'), [
                    'swoole/ide-helper',
                ]);
            }, "<fg=yellow>Installing Swoole IDE helper...</>");

            $this->components->success("Swoole IDE helper installed successfully!");
        }else{
            $this->components->error("Extension Swoole/Openswoole not found!");
        }

        return 0;
    }
}
