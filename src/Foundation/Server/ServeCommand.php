<?php

namespace LaraGram\Foundation\Server;

use LaraGram\Console\Command;
use LaraGram\Support\Facades\Request;

class ServeCommand extends Command
{
    protected $signature = 'serve';
    protected $description = 'Start Development Server';

    public function handle()
    {
        if ($this->getOption('h') == 'h') $this->output->message($this->description, true);

        $DEVELOPMENT_SERVER_IP = $_ENV['DEVELOPMENT_SERVER_IP'] == '' ? '127.0.0.1' : $_ENV['DEVELOPMENT_SERVER_IP'];
        $DEVELOPMENT_SERVER_PORT = $_ENV['DEVELOPMENT_SERVER_PORT'] == '' ? '9000' : $_ENV['DEVELOPMENT_SERVER_PORT'];

        if ($this->options == null) exec("php -S {$DEVELOPMENT_SERVER_IP}:{$DEVELOPMENT_SERVER_PORT}");
        if ($this->getOption('apiserver') == 'apiserver') {
            $BOT_API_SERVER_DIR = $_ENV['BOT_API_SERVER_DIR'] == '' ? app('path.storage') . '/API-Server' : $_ENV['BOT_API_SERVER_DIR'];
            if (!file_exists($BOT_API_SERVER_DIR)) mkdir($BOT_API_SERVER_DIR, recursive: true);
            if ($_ENV['API_ID'] == null || $_ENV['API_HASH'] == null) $this->output->failed("API_ID or API_HASH not set!", exit: true);
            exec("telegram-bot-api --local --api-id={$_ENV['API_ID']} --api-hash={$_ENV['API_HASH']} --dir={$BOT_API_SERVER_DIR} & php -S {$DEVELOPMENT_SERVER_IP}:{$DEVELOPMENT_SERVER_PORT}");
        }
    }
}