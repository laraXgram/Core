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

        if (($_ENV['DEVELOPMENT_SERVER_IP'] == null && $this->options['host'] == null) || ($_ENV['DEVELOPMENT_SERVER_PORT'] == null && $this->options['port'] == null)) $this->output->failed("DEVELOPMENT_SERVER_IP or DEVELOPMENT_SERVER_PORT not set!", exit: true);

        $DEVELOPMENT_SERVER_PORT = $this->options['port'] ?? $_ENV['DEVELOPMENT_SERVER_PORT'];
        $DEVELOPMENT_SERVER_IP = $this->options['host'] ?? $_ENV['DEVELOPMENT_SERVER_IP'];

        if ($this->getOption('apiserver') == 'apiserver') {
            if ($_ENV['API_ID'] == null || $_ENV['API_HASH'] == null) $this->output->failed("API_ID or API_HASH not set!", exit: true);
            if ($_ENV['BOT_API_SERVER_IP'] == null || $_ENV['BOT_API_SERVER_PORT'] == null) $this->output->failed("BOT_API_SERVER_IP or BOT_API_SERVER_PORT not set!", exit: true);

            $dir = $_ENV['BOT_API_SERVER_DIR'] ?? app('path.storage') . '/API-Server';

            if (!file_exists($dir)) mkdir($dir, recursive: true);

            $this->output->success("Starting API Server on {$_ENV['BOT_API_SERVER_IP']}:{$_ENV['BOT_API_SERVER_PORT']}");

            $command = "telegram-bot-api --local --http-ip-address={$_ENV['BOT_API_SERVER_IP']} --http-port={$_ENV['BOT_API_SERVER_PORT']} --api-id={$_ENV['API_ID']} --api-hash={$_ENV['API_HASH']} --dir={$dir}";

            if ($_ENV['BOT_API_SERVER_LOG_DIR'] != '') $command .= " --log={$_ENV['BOT_API_SERVER_LOG_DIR']}";
            if ($_ENV['BOT_API_SERVER_STAT_IP'] != '') $command .= " --http-stat-ip-address={$_ENV['BOT_API_SERVER_STAT_IP']}";
            if ($_ENV['BOT_API_SERVER_STAT_PORT'] != '') $command .= " --http-stat-port={$_ENV['BOT_API_SERVER_STAT_PORT']}";

            $command .= " & php -S {$DEVELOPMENT_SERVER_IP}:{$DEVELOPMENT_SERVER_PORT}";

            exec($command);
        }elseif ($this->getOption('openswoole') == 'openswoole') {
            if ($_ENV['UPDATE_TYPE'] !== 'openswoole') {
                $this->output->failed("UPDATE_TYPE is not openswoole!", exit: true);
            }
            exec("php Bootstrap/app.php");
        } else{
            exec("php -S {$DEVELOPMENT_SERVER_IP}:{$DEVELOPMENT_SERVER_PORT}");
        }
    }
}