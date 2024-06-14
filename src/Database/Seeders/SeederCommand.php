<?php

namespace LaraGram\Database\Seeders;

use LaraGram\Console\Command;

class SeederCommand extends Command
{
    protected $signature = 'db:seed';
    protected $description = 'Start seeding database';

    public function handle(): void
    {
        $seeder = $this->getOption('seeder');
        if ($seeder == null){
            $this->output->failed('Set the seeder name [ --seeder=seederName ]');
            return;
        }

        if (!file_exists(app('path.seeder') . DIRECTORY_SEPARATOR . $seeder . '.php')){
            $this->output->failed("seeder [ $seeder ] not exist!");
            return;
        }

        $time = microtime(true);
        (new ("\Database\Seeders\\" . $seeder))->run();
        $time = round((microtime(true) - $time) * 1000);

        $this->output->success("Seeding [ $seeder ] completed -> {$time}ms");
    }
}