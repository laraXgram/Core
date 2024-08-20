<?php

namespace LaraGram\Foundation\PackageManager\Installer;

use LaraGram\Console\Command;
use LaraGram\Support\Facades\Console;

class OpenSwoole extends Command
{
    protected $signature = 'install:openswoole';
    protected $description = 'Install Openswoole Core';

    public function handle()
    {
        if ($this->getOption('h') == 'h') Console::output()->message($this->description, true);

        if (!extension_loaded('openswoole')) {
            Console::output()->failed("openswoole extension not loaded", true);
        }

        Console::output()->message("This operation may take time...");

        exec("composer require openswoole/core 2>&1", $output, $return_var);

        if ($return_var !== 0) {
            Console::output()->failed("Error installing package: ");
            echo implode("\n", $output);
        } else {
            Console::output()->success("[ OpenSwoole Core ] Installed Successfully!", true);
        }
    }
}