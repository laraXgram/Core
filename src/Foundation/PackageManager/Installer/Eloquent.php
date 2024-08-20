<?php

namespace LaraGram\Foundation\PackageManager\Installer;

use LaraGram\Console\Command;
use LaraGram\Support\Facades\Console;

class Eloquent extends Command
{
    protected $signature = 'install:eloquent';
    protected $description = 'Install Laravel Eloquent ORM';

    public function handle()
    {
        if ($this->getOption('h') == 'h') Console::output()->message($this->description, true);

        Console::output()->message("This operation may take time...");

        exec("composer require illuminate/database illuminate/events illuminate/support fakerphp/faker 2>&1", $output, $return_var);

        if ($return_var !== 0) {
            Console::output()->failed("Error installing package: ");
            echo implode("\n", $output);
        } else {
            Console::output()->success("[ Eloquent ORM ] Installed Successfully!", true);
        }
    }
}