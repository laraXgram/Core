<?php

namespace LaraGram\Foundation\Console;

use LaraGram\Console\Command;
use LaraGram\Support\Facades\Console;

class UninstallEloquent extends Command
{
    protected $signature = 'remove:eloquent';
    protected $description = 'Remove Laravel Eloquent ORM';

    public function handle()
    {
        if ($this->getOption('h') == 'h') Console::output()->message($this->description, true);

        exec("composer remove illuminate/database illuminate/events illuminate/support fakerphp/faker 2>&1", $output, $return_var);

        if ($return_var !== 0) {
            Console::output()->failed("Error removing package: ");
            echo implode("\n", $output);
        } else {
            Console::output()->success("[ Eloquent ORM ] Removed Successfully!", true);
        }
    }
}