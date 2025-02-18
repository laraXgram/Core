<?php

namespace LaraGram\Foundation\Console;

use LaraGram\Console\GeneratorCommand;
use LaraGram\Console\Attribute\AsCommand;

#[AsCommand(name: 'generate-app', hidden: true)]
class GenerateAppCommand extends GeneratorCommand
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'generate-app';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate LaraGram Application';

    protected $hidden = true;

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $token = $this->ask('Enter your bot Token: ');
        $api_server = $this->ask('Enter bot API server: ', "https://api.telegram.org/");

        $webhook = $this->confirm('Do you need to set webhook? ', true);
        if ($webhook){
            $bot_domain = $this->ask('Enter bot Domain: ');
            $this->call('webhook:set', [
                'token' => $token,
                'url' => $bot_domain,
            ]);
        }

        $db = $this->confirm('Do you need Database? ');
        if ($db){
            $db_name = $this->ask('Enter database name: ');
            $db_host = $this->ask('Enter database host: ', 'localhost');
            $db_user = $this->ask('Enter database user: ', 'root');
            $db_pass = $this->secret('Enter database password: ');
        }

        var_dump($token, $api_server, $db_name, $db_user, $db_host, $db_pass);
        return 0;
    }

    protected function getStub()
    {
        // TODO: Implement getStub() method.
    }
}
