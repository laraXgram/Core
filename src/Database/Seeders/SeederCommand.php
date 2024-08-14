<?php

namespace LaraGram\Database\Seeders;

use LaraGram\Console\Command;
use LaraGram\Support\Facades\Console;

class SeederCommand extends Command
{
    protected $signature = 'db:seed';
    protected $description = 'Start seeding database';

    public function handle(): void
    {
        if ($this->getOption('h') == 'h') Console::output()->message($this->description, true);

        $seeder = $this->getOption('seeder');
        if ($seeder == null){
            Console::output()->failed('Set the seeder name [ --seeder=seederName ]');
            return;
        }

        if (!file_exists(app('path.database') . DIRECTORY_SEPARATOR . 'seeders' . DIRECTORY_SEPARATOR . $seeder . '.php')){
            Console::output()->failed("seeder [ $seeder ] not exist!");
            return;
        }

        $time = microtime(true);
        (new ("\Database\Seeders\\" . $seeder))->run();
        $time = round((microtime(true) - $time) * 1000);

        Console::output()->success("Seeding [ $seeder ] completed -> {$time}ms");
    }
}