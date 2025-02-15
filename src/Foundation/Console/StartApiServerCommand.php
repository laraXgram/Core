<?php

namespace LaraGram\Foundation\Console;

use LaraGram\Console\Command;
use LaraGram\Console\Process\Process;
use LaraGram\Console\Attribute\AsCommand;
use LaraGram\Console\Input\InputOption;

#[AsCommand(name: 'start:apiserver')]
class StartApiServerCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'start:apiserver';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start Local Bot API Server';

    /**
     * Execute the console command.
     *
     * @return int|null
     */
    public function handle()
    {
        if (empty(config('bot.api_server.api_id')) || empty('bot.api_server.api_hash'))
            $this->components->error("api_id or api_hash not set in [ config.bot ].");

        if (!file_exists($this->option('dir')))
            mkdir($this->option('dir'), recursive: true);

        $process = $this->startProcess();

        $this->components->info('Local Bot API Server started on [http://' . $this->option('host') . ':' . $this->option('port') . ']');
        $this->comment('  <fg=yellow;options=bold>Press Ctrl+C to stop the server</>');
        $this->newLine(2);

        while ($process->isRunning()) {
            usleep(500 * 1000);
        }

        return $process->getExitCode();
    }

    /**
     * Start a new server process.
     *
     * @return Process
     */
    protected function startProcess()
    {
        $process = new Process($this->apiServerCommand());

        $this->trap(fn () => [SIGTERM, SIGINT, SIGHUP, SIGUSR1, SIGUSR2, SIGQUIT], function ($signal) use ($process) {
            if ($process->isRunning()) {
                $process->stop(10, $signal);
            }

            exit;
        });

        $process->start();

        return $process;
    }

    public function apiServerCommand()
    {
        $command = [
            'telegram-bot-api',
            '--local',
            '--http-ip-address=' . $this->option('host'),
            '--http-port=' . $this->option('port'),
            '--api-id=' . config('bot.api_server.api_id'),
            '--api-hash=' . config('bot.api_server.api_hash'),
            '--dir=' . $this->option('dir'),
        ];

        $BOT_API_SERVER_LOG_DIR = config('bot.api_server.log_dir');
        $BOT_API_SERVER_STAT_IP = config('bot.api_server.stat.ip');
        $BOT_API_SERVER_STAT_PORT = config('bot.api_server.stat.port');

        if (!empty($BOT_API_SERVER_LOG_DIR)) $command[] = $BOT_API_SERVER_LOG_DIR;
        if (!empty($BOT_API_SERVER_STAT_IP))  $command[] = $BOT_API_SERVER_STAT_IP;
        if (!empty($BOT_API_SERVER_STAT_PORT)) $command[] = $BOT_API_SERVER_STAT_PORT;

        return $command;
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['host', null, InputOption::VALUE_OPTIONAL, "Specify the host IP address for the bot API server", config('bot.api_server.ip') ?? '127.0.0.1'],
            ['port', null, InputOption::VALUE_OPTIONAL, 'Specify the port for the bot API server.', config('bot.api_server.port') ?? 8081],
            ['dir', null, InputOption::VALUE_OPTIONAL, 'Specify the directory path for the API server.', config('bot.api_server.dir') ?? app('path.storage') . 'app/apiserver'],
        ];
    }
}