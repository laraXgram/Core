<?php

namespace LaraGram\Foundation\Console;

use LaraGram\Console\Command;
use LaraGram\Support\Facades\Console;

class APIServeCommand extends Command
{
    protected $signature = 'start:apiserver';
    protected $description = 'Start Local Bot API Server';

    public function handle()
    {
        if ($this->getOption('h') == 'h') Console::output()->message($this->description, true);

        $API_ID = config('bot.api_server.api_id');
        $API_HASH = config('bot.api_server.api_hash');

        $BOT_API_SERVER_IP = config('bot.api_server.ip');
        $BOT_API_SERVER_PORT = config('bot.api_server.port');

        if ($API_ID == null || $API_HASH == null) Console::output()->failed("API_ID or API_HASH not set!", exit: true);
        if (($BOT_API_SERVER_IP == null && $this->options['host'] == null) || ($BOT_API_SERVER_PORT == null && $this->options['port'] == null)) Console::output()->failed("BOT_API_SERVER_IP or BOT_API_SERVER_PORT not set!", exit: true);

        $host = $this->options['host'] ?? $BOT_API_SERVER_IP;
        $port = $this->options['port'] ?? $BOT_API_SERVER_PORT;
        $dir = config('bot.api_server.dir') ?? app('path.storage') . 'app/apiserver';

        if (!file_exists($dir))
        {
            mkdir($dir, recursive: true);
            sleep(1);
        }

        Console::output()->success("Starting API Server on {$host}:{$port}");

        $command = "telegram-bot-api --local --http-ip-address={$host} --http-port={$port} --api-id={$API_ID} --api-hash={$API_HASH} --dir={$dir}";

        $BOT_API_SERVER_LOG_DIR = config('bot.api_server.log_dir');
        $BOT_API_SERVER_STAT_IP = config('bot.api_server.stat.ip');
        $BOT_API_SERVER_STAT_PORT = config('bot.api_server.stat.port');
        if ($BOT_API_SERVER_LOG_DIR != '') $command .= " --log={$BOT_API_SERVER_LOG_DIR}";
        if ($BOT_API_SERVER_STAT_IP != '') $command .= " --http-stat-ip-address={$BOT_API_SERVER_STAT_IP}";
        if ($BOT_API_SERVER_STAT_PORT != '') $command .= " --http-stat-port={$BOT_API_SERVER_STAT_PORT}";

        exec($command);
    }
}