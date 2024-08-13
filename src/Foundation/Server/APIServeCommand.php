<?php

namespace LaraGram\Foundation\Server;

use LaraGram\Console\Command;
use LaraGram\Support\Facades\Console;
use LaraGram\Support\Facades\Request;

class APIServeCommand extends Command
{
    protected $signature = 'start:apiserver';
    protected $description = 'Start Local Bot API Server';

    public function handle()
    {
        if ($this->getOption('h') == 'h') Console::output()->message($this->description, true);

        if ($_ENV['API_ID'] == null || $_ENV['API_HASH'] == null) Console::output()->failed("API_ID or API_HASH not set!", exit: true);
        if (($_ENV['BOT_API_SERVER_IP'] == null && $this->options['host'] == null) || ($_ENV['BOT_API_SERVER_PORT'] == null && $this->options['port'] == null)) Console::output()->failed("BOT_API_SERVER_IP or BOT_API_SERVER_PORT not set!", exit: true);

        $port = $this->options['port'] ?? $_ENV['BOT_API_SERVER_PORT'];
        $host = $this->options['host'] ?? $_ENV['BOT_API_SERVER_IP'];
        $dir = $_ENV['BOT_API_SERVER_DIR'] ?? app('path.storage') . '/API-Server';

        if (!file_exists($dir)) mkdir($dir, recursive: true);

        Console::output()->success("Starting API Server on {$host}:{$port}");

        $command = "telegram-bot-api --local --http-ip-address={$host} --http-port={$port} --api-id={$_ENV['API_ID']} --api-hash={$_ENV['API_HASH']} --dir={$dir}";

        if ($_ENV['BOT_API_SERVER_LOG_DIR'] != '') $command .= " --log={$_ENV['BOT_API_SERVER_LOG_DIR']}";
        if ($_ENV['BOT_API_SERVER_STAT_IP'] != '') $command .= " --http-stat-ip-address={$_ENV['BOT_API_SERVER_STAT_IP']}";
        if ($_ENV['BOT_API_SERVER_STAT_PORT'] != '') $command .= " --http-stat-port={$_ENV['BOT_API_SERVER_STAT_PORT']}";

        exec($command);
    }
}