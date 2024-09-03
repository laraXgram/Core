<?php

namespace LaraGram\Foundation\Server;

use LaraGram\Console\Command;
use LaraGram\Support\Facades\Config;
use LaraGram\Support\Facades\Console;

class APIServeCommand extends Command
{
    protected $signature = 'start:apiserver';
    protected $description = 'Start Local Bot API Server';

    public function handle()
    {
        if ($this->getOption('h') == 'h') Console::output()->message($this->description, true);

        $API_ID = Config::get('bot.API_ID');
        $API_HASH = Config::get('bot.API_HASH');

        $BOT_API_SERVER_IP = Config::get('bot.BOT_API_SERVER_IP');
        $BOT_API_SERVER_PORT = Config::get('bot.BOT_API_SERVER_PORT');

        if ($API_ID == null || $API_HASH == null) Console::output()->failed("API_ID or API_HASH not set!", exit: true);
        if (($BOT_API_SERVER_IP == null && $this->options['host'] == null) || ($BOT_API_SERVER_PORT == null && $this->options['port'] == null)) Console::output()->failed("BOT_API_SERVER_IP or BOT_API_SERVER_PORT not set!", exit: true);

        $host = $this->options['host'] ?? $BOT_API_SERVER_IP;
        $port = $this->options['port'] ?? $BOT_API_SERVER_PORT;
        $dir = Config::get('bot.BOT_API_SERVER_DIR') ?? app('path.storage') . 'app/apiserver';

        if (!file_exists($dir))
        {
            mkdir($dir, recursive: true);
            sleep(1);
        }

        Console::output()->success("Starting API Server on {$host}:{$port}");


        $API_ID = Config::get('bot.API_ID');
        $API_HASH = Config::get('bot.API_HASH');

        $command = "telegram-bot-api --local --http-ip-address={$host} --http-port={$port} --api-id={$API_ID} --api-hash={$API_HASH} --dir={$dir}";

        $BOT_API_SERVER_LOG_DIR = Config::get('bot.BOT_API_SERVER_LOG_DIR');
        $BOT_API_SERVER_STAT_IP = Config::get('bot.BOT_API_SERVER_STAT_IP');
        $BOT_API_SERVER_STAT_PORT = Config::get('bot.BOT_API_SERVER_STAT_PORT');
        if ($BOT_API_SERVER_LOG_DIR != '') $command .= " --log={$BOT_API_SERVER_LOG_DIR}";
        if ($BOT_API_SERVER_STAT_IP != '') $command .= " --http-stat-ip-address={$BOT_API_SERVER_STAT_IP}";
        if ($BOT_API_SERVER_STAT_PORT != '') $command .= " --http-stat-port={$BOT_API_SERVER_STAT_PORT}";

        exec($command);
    }
}