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
        if (empty(config('bot.api_server.api_id')) || empty(config('bot.api_server.api_hash'))) {
            $this->components->error("api_id or api_hash not set in [ config.bot ].");

            return self::FAILURE;
        }

        if (! $this->usesLaraGramServer() && ($this->streamsFiles() || ! $this->storesFiles())) {
            $this->components->error('File streaming and disabling file storage are only supported by the LaraGram API Server.');

            return self::FAILURE;
        }

        if (! $this->storesFiles() && ! $this->streamsFiles()) {
            $this->components->error('Disabling file storage requires file streaming, set [bot.api_server.stream_files] to true.');

            return self::FAILURE;
        }

        if (!file_exists($this->option('dir')))
            mkdir($this->option('dir'), recursive: true);

        $process = $this->startProcess();

        $this->components->info('Local Bot API Server started on [http://' . $this->option('host') . ':' . $this->option('port') . ']');
        $this->comment('  <fg=yellow;options=bold>Press Ctrl+C to stop the server</>');
        $this->newLine(2);

        while ($process->isRunning()) {
            usleep(500 * 1000);
        }

        if (!$process->isSuccessful()) {
            $this->components->error('Bot API Server [' . $this->option('binary') . '] exited with code ' . $process->getExitCode() . '.');
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
        $process = new Process($this->apiServerCommand(), timeout: null);

        $this->trap(fn () => [SIGTERM, SIGINT, SIGHUP, SIGUSR1, SIGUSR2, SIGQUIT], function ($signal) use ($process) {
            if ($process->isRunning()) {
                $process->stop(10, $signal);
            }

            exit;
        });

        $process->start(fn ($type, $buffer) => $this->output->write($buffer));

        return $process;
    }

    public function apiServerCommand()
    {
        $command = [
            $this->option('binary'),
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

        if (!empty($BOT_API_SERVER_LOG_DIR)) $command[] = '--log=' . $BOT_API_SERVER_LOG_DIR;
        if (!empty($BOT_API_SERVER_STAT_IP))  $command[] = '--http-stat-ip-address=' . $BOT_API_SERVER_STAT_IP;
        if (!empty($BOT_API_SERVER_STAT_PORT)) $command[] = '--http-stat-port=' . $BOT_API_SERVER_STAT_PORT;

        if ($this->streamsFiles()) {
            $command[] = '--stream-files';
            $command[] = '--stream-read-ahead=' . (int) config('bot.api_server.stream_read_ahead', 4);
        }

        if (! $this->storesFiles()) {
            $command[] = '--no-file-storage';
        }

        return $command;
    }

    /**
     * Determine if the server streams files at their /file/ URLs.
     *
     * @return bool
     */
    protected function streamsFiles(): bool
    {
        return filter_var(config('bot.api_server.stream_files', false), FILTER_VALIDATE_BOOL);
    }

    /**
     * Determine if the server writes files to disk.
     *
     * @return bool
     */
    protected function storesFiles(): bool
    {
        return filter_var(config('bot.api_server.file_storage', true), FILTER_VALIDATE_BOOL);
    }

    /**
     * Determine if the configured binary is the LaraGram API Server.
     *
     * @return bool
     */
    protected function usesLaraGramServer(): bool
    {
        return str_contains(basename((string) $this->option('binary')), 'laragram-server');
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['binary', null, InputOption::VALUE_OPTIONAL, 'Specify the bot API server binary (e.g. telegram-bot-api or laragram-server).', config('bot.api_server.binary') ?? 'telegram-bot-api'],
            ['host', null, InputOption::VALUE_OPTIONAL, "Specify the host IP address for the bot API server", config('bot.api_server.ip') ?? '127.0.0.1'],
            ['port', null, InputOption::VALUE_OPTIONAL, 'Specify the port for the bot API server.', config('bot.api_server.port') ?? 8081],
            ['dir', null, InputOption::VALUE_OPTIONAL, 'Specify the directory path for the API server.', config('bot.api_server.dir') ?? app('path.storage') . 'app/apiserver'],
        ];
    }
}