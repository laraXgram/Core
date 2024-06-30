<?php

namespace LaraGram\Console;


class GenerateCommand extends Command
{
    protected $signature = 'make:command';
    protected $description = 'Create new terminal command';

    public function handle(): void
    {
        if ($this->getOption('h') == 'h') $this->output->message($this->description, true);

        $stub = file_get_contents($this->getStub('/stubs/command.stub'));
        $name = str_replace('Command', '', ucfirst($this->getArgument(0)));


        $file_structure = str_replace('%name%', $name . 'Command', $stub);
        $file_structure = str_replace('%signature%', strtolower($name), $file_structure);

        if (!file_exists(app('path.command'))){
            mkdir(app('path.command'));
        }

        if (file_exists(app('path.command') . DIRECTORY_SEPARATOR . $name . 'Command.php')){
            $this->output->warning("Command [ $name ] already exist!", exit: true);
        }

        file_put_contents(app('path.command') . DIRECTORY_SEPARATOR . $name . 'Command.php', $file_structure);

        $this->output->success("Command [ $name ] created successfully!");
    }

    protected function getStub($stub)
    {
        return file_exists($customPath = app()->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__.$stub;
    }
}