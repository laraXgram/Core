<?php

namespace LaraGram\Foundation\Server;

use LaraGram\Console\Command;
use LaraGram\Support\Facades\Request;

class APIServeCommand extends Command
{
    protected $signature = 'start:apiserver';
    protected $description = 'Start Local Bot API Server';

    public function handle()
    {
        if ($this->getOption('h') == 'h') $this->output->message($this->description, true);

        $BOT_API_SERVER_DIR = $_ENV['BOT_API_SERVER_DIR'] == '' ? app('path.storage') . '/API-Server' : $_ENV['BOT_API_SERVER_DIR'];
        if (!file_exists($BOT_API_SERVER_DIR)) mkdir($BOT_API_SERVER_DIR, recursive: true);
        if ($_ENV['API_ID'] == null || $_ENV['API_HASH'] == null) $this->output->failed("API_ID or API_HASH not set!", exit: true);
        $this->output->success("Starting API Server on 127.0.0.1:8081");
        exec("telegram-bot-api --local --api-id={$_ENV['API_ID']} --api-hash={$_ENV['API_HASH']} --dir={$BOT_API_SERVER_DIR}");
    }
}