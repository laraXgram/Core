<?php

namespace LaraGram\Foundation\Server;

use LaraGram\Console\Command;
use LaraGram\Support\Facades\Config;
use LaraGram\Support\Facades\Console;

class ServeCommand extends Command
{
    protected $signature = 'serve';
    protected $description = 'Start Development Server';

    public function handle()
    {
        if ($this->getOption('h') == 'h') Console::output()->message($this->description, true);

        $DEVELOPMENT_SERVER_IP = Config::get('server.DEVELOPMENT_SERVER_IP');
        $DEVELOPMENT_SERVER_PORT = Config::get('server.DEVELOPMENT_SERVER_PORT');

        if (($DEVELOPMENT_SERVER_IP == null && $this->options['host'] == null) || ($DEVELOPMENT_SERVER_PORT == null && $this->options['port'] == null)) Console::output()->failed("DEVELOPMENT_SERVER_IP or DEVELOPMENT_SERVER_PORT not set!", exit: true);

        $DEVELOPMENT_SERVER_PORT = $this->options['port'] ?? $DEVELOPMENT_SERVER_PORT;
        $DEVELOPMENT_SERVER_IP = $this->options['host'] ?? $DEVELOPMENT_SERVER_IP;

        $API_ID = Config::get('bot.API_ID');
        $API_HASH = Config::get('bot.API_HASH');
        $BOT_API_SERVER_IP = Config::get('bot.BOT_API_SERVER_IP');
        $BOT_API_SERVER_PORT = Config::get('bot.BOT_API_SERVER_PORT');
        $BOT_API_SERVER_DIR = Config::get('bot.BOT_API_SERVER_DIR');

        if ($this->getOption('apiserver') == 'apiserver') {
            if ($API_ID == null || $API_HASH == null) Console::output()->failed("API_ID or API_HASH not set!", exit: true);
            if ($BOT_API_SERVER_IP == null || $BOT_API_SERVER_PORT == null) Console::output()->failed("BOT_API_SERVER_IP or BOT_API_SERVER_PORT not set!", exit: true);

            $dir = $BOT_API_SERVER_DIR ?? app('path.storage') . '/API-Server';

            if (!file_exists($dir)) mkdir($dir, recursive: true);

            Console::output()->success("Starting API Server on {$BOT_API_SERVER_IP}:{$BOT_API_SERVER_PORT}");

            $command = "telegram-bot-api --local --http-ip-address={$BOT_API_SERVER_IP} --http-port={$BOT_API_SERVER_PORT} --api-id={$API_ID} --api-hash={$API_HASH} --dir={$dir}";

            $BOT_API_SERVER_LOG_DIR = Config::get('bot.BOT_API_SERVER_LOG_DIR');
            $BOT_API_SERVER_STAT_IP = Config::get('bot.BOT_API_SERVER_STAT_IP');
            $BOT_API_SERVER_STAT_PORT = Config::get('bot.BOT_API_SERVER_STAT_PORT');
            if ($BOT_API_SERVER_LOG_DIR != '') $command .= " --log={$BOT_API_SERVER_LOG_DIR}";
            if ($BOT_API_SERVER_STAT_IP != '') $command .= " --http-stat-ip-address={$BOT_API_SERVER_STAT_IP}";
            if ($BOT_API_SERVER_STAT_PORT != '') $command .= " --http-stat-port={$BOT_API_SERVER_STAT_PORT}";

            $command .= " & php -S {$DEVELOPMENT_SERVER_IP}:{$DEVELOPMENT_SERVER_PORT}";

            exec($command);
        }elseif ($this->getOption('openswoole') == 'openswoole' || $this->getOption('swoole') == 'swoole') {
            if (Config::get('bot.UPDATE_TYPE') !== 'openswoole' || Config::get('bot.UPDATE_TYPE') !== 'swoole') {
                Console::output()->failed("UPDATE_TYPE is not openswoole/swoole!", exit: true);
            }
            require_once 'Bootstrap/app.php';
        } else{
            exec("php -S {$DEVELOPMENT_SERVER_IP}:{$DEVELOPMENT_SERVER_PORT}");
        }
    }
}