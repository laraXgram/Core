<?php

namespace LaraGram\Foundation\PackageManager\Uninstaller;

use LaraGram\Console\Command;
use LaraGram\Support\Facades\Console;

class OpenSwoole extends Command
{
    protected $signature = 'remove:openswoole';
    protected $description = 'Remove Openswoole/Swoole Core';

    public function handle()
    {
        if ($this->getOption('h') == 'h') Console::output()->message($this->description, true);

        if (!extension_loaded('openswoole') && !extension_loaded('swoole')) {
            Console::output()->failed('Extension Openswoole/Swoole not loaded!');
        }

        Console::output()->message("This operation may take time...");

        if (extension_loaded('openswoole')) {
            exec("composer remove openswoole/core 2>&1", $output, $return_var);
        }elseif (extension_loaded('swoole')){
            exec("composer remove swoole/ide-helper 2>&1", $output, $return_var);
        }

        if ($return_var !== 0) {
            Console::output()->failed("Error removing package: ");
            echo implode("\n", $output);
        } else {
            Console::output()->success("[ OpenSwoole/Swoole Core ] Removed Successfully!", true);
        }
    }
}