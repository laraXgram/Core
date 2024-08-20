<?php

namespace LaraGram\Foundation\PackageManager\Uninstaller;

use LaraGram\Console\Command;
use LaraGram\Support\Facades\Console;

class OpenSwoole extends Command
{
    protected $signature = 'remove:openswoole';
    protected $description = 'Remove Openswoole Core';

    public function handle()
    {
        if ($this->getOption('h') == 'h') Console::output()->message($this->description, true);

        if (!extension_loaded('openswoole')) {
            Console::output()->failed("openswoole extension not loaded", true);
        }

        exec("composer remove openswoole/core 2>&1", $output, $return_var);

        if ($return_var !== 0) {
            Console::output()->failed("Error removing package: ");
            echo implode("\n", $output);
        } else {
            Console::output()->success("[ OpenSwoole Core ] Removed Successfully!", true);
        }
    }
}