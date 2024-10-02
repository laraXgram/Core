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

        $DEVELOPMENT_SERVER_IP = config('server.development_server.ip');
        $DEVELOPMENT_SERVER_PORT = config('server.development_server.port');

        if (($DEVELOPMENT_SERVER_IP == null && $this->options['host'] == null) || ($DEVELOPMENT_SERVER_PORT == null && $this->options['port'] == null)) Console::output()->failed("DEVELOPMENT_SERVER_IP or DEVELOPMENT_SERVER_PORT not set!", exit: true);

        $DEVELOPMENT_SERVER_PORT = $this->options['port'] ?? $DEVELOPMENT_SERVER_PORT;
        $DEVELOPMENT_SERVER_IP = $this->options['host'] ?? $DEVELOPMENT_SERVER_IP;

        if ($this->getOption('openswoole') == 'openswoole') {
            if (config('laraquest.update_type') !== 'openswoole') {
                Console::output()->failed("UPDATE_TYPE is not openswoole!", exit: true);
            }

            require_once 'Bootstrap/app.php';
        }elseif ($this->getOption('polling') == 'polling') {
            if (config('laraquest.update_type') !== 'polling') {
                Console::output()->failed("UPDATE_TYPE is not polling!", exit: true);
            }

            require_once 'Bootstrap/app.php';
        } else{
            Console::output()->success("Development server started on [ {$DEVELOPMENT_SERVER_IP}:{$DEVELOPMENT_SERVER_PORT} ]");
            exec("php -S {$DEVELOPMENT_SERVER_IP}:{$DEVELOPMENT_SERVER_PORT}");
        }
    }
}