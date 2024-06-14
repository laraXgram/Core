<?php

namespace LaraGram\Database\Seeders;


use LaraGram\Console\Command;

class GenerateSeeder extends Command
{
    protected $signature = 'make:seeder';
    protected $description = 'Create new database seeder';

    public function handle(): void
    {
        $stub = file_get_contents($this->getStub('/stubs/seeder.stub'));
        $name = ucfirst($this->getArgument(0));

        $file_structure = str_replace('%name%', $name, $stub);

        if (!file_exists(app('path.seeder'))){
            mkdir(app('path.seeder'), recursive: true);
        }

        if (file_exists(app('path.seeder') . DIRECTORY_SEPARATOR . $name . 'Seeder.php')){
            $this->output->warning("Seed [ $name ] already exist!", exit: true);
        }

        file_put_contents(app('path.seeder') . DIRECTORY_SEPARATOR . $name . 'Seeder.php', $file_structure);

        $this->output->success("Seed [ $name ] created successfully!");
    }

    protected function getStub($stub)
    {
        return file_exists($customPath = app()->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__.$stub;
    }
}