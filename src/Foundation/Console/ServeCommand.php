<?php

namespace LaraGram\Foundation\Console;

use LaraGram\Console\Command;
use LaraGram\Console\Process\Process;
use LaraGram\Support\InteractsWithTime;
use LaraGram\Console\Attribute\AsCommand;
use LaraGram\Console\Input\InputOption;

use function LaraGram\Console\Prompts\Convertor\terminal;

#[AsCommand(name: 'serve')]
class ServeCommand extends Command
{
    use InteractsWithTime;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'serve';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start Development Server';

    /**
     * The current port offset.
     *
     * @var int
     */
    protected $portOffset = 0;

    /**
     * The list of lines that are pending to be output.
     *
     * @var string
     */
    protected $outputBuffer = '';

    /**
     * The list of requests being handled and their start time.
     *
     * @var array<int, \DateTimeInterface>
     */
    protected $requestsPool;

    /**
     * Indicates if the "Server running on..." output message has been displayed.
     *
     * @var bool
     */
    protected $serverRunningHasBeenDisplayed = false;

    /**
     * Execute the console command.
     *
     * @return int|null
     */
    public function handle()
    {
        if ($this->option('openswoole')) {
            if (!extension_loaded('swoole') && !extension_loaded('openswoole')) {
                $this->components->error('Extension swoole/openswoole is not installed!');
                return 0;
            }

            if (!in_array(config('laraquest.update_type'), ['openswoole', 'swoole'])) {
                $this->components->error('laraquest.update_type is not swoole/openswoole!');
                return 0;
            }

            $this->components->info("Server running on [http://{$this->host()}:{$this->port()}].");
            $this->comment('  <fg=yellow;options=bold>Press Ctrl+C to stop the server</>');

            config('server.openswoole.ip', $this->host());
            config('server.openswoole.port', $this->port());

            require_once $this->laragram->bootstrapPath('server.php');
        } elseif ($this->option('polling')) {
            if (config('laraquest.update_type') != 'polling') {
                $this->components->error('laraquest.update_type is not polling!');
                return 0;
            }

            $this->components->info("Polling Started...");
            $this->comment('  <fg=yellow;options=bold>Press Ctrl+C to stop the server</>');

            require_once $this->laragram->bootstrapPath('server.php');
        } else {
            $process = $this->startProcess();

            while ($process->isRunning()) {
                usleep(500 * 1000);
            }

            $status = $process->getExitCode();

            if ($status && $this->canTryAnotherPort()) {
                $this->portOffset += 1;

                return $this->handle();
            }

            return $status;
        }
    }

    /**
     * Start a new server process.
     *
     * @return Process
     */
    protected function startProcess()
    {
        $process = new Process($this->serveCommand(), $this->laragram->basePath());

        $this->trap(fn() => [SIGTERM, SIGINT, SIGHUP, SIGUSR1, SIGUSR2, SIGQUIT], function ($signal) use ($process) {
            if ($process->isRunning()) {
                $process->stop(10, $signal);
            }

            exit;
        });

        $process->start($this->handleProcessOutput());

        return $process;
    }

    /**
     * Flush the output buffer.
     *
     * @return void
     */
    protected function flushOutputBuffer()
    {
        $lines = explode("\n", $this->outputBuffer);

        $this->outputBuffer = (string)array_pop($lines);

        foreach ($lines as $line) {
            $line = trim($line);

            if (empty($line)) {
                continue;
            }

            if (str_contains($line, 'Development Server (http')) {
                if ($this->serverRunningHasBeenDisplayed === false) {
                    $this->serverRunningHasBeenDisplayed = true;

                    $this->components->info("Server running on [http://{$this->host()}:{$this->port()}].");
                    $this->comment('  <fg=yellow;options=bold>Press Ctrl+C to stop the server</>');

                    $this->newLine();
                }

                continue;
            }

            if (str_contains($line, ' Accepted')) {
                $requestPort = static::getRequestPortFromLine($line);

                $this->requestsPool[$requestPort] = [
                    $this->getDateFromLine($line),
                    $this->requestsPool[$requestPort][1] ?? false,
                    microtime(true),
                ];
            } elseif (str_contains($line, ' [200]: GET ')) {
                $requestPort = static::getRequestPortFromLine($line);

                $this->requestsPool[$requestPort][1] = trim(explode('[200]: GET', $line)[1]);
            } elseif (str_contains($line, 'URI:')) {
                $requestPort = static::getRequestPortFromLine($line);

                $this->requestsPool[$requestPort][1] = trim(explode('URI: ', $line)[1]);
            } elseif (str_contains($line, ' Closing')) {
                $requestPort = static::getRequestPortFromLine($line);

                if (empty($this->requestsPool[$requestPort])) {
                    $this->requestsPool[$requestPort] = [
                        $this->getDateFromLine($line),
                        false,
                        microtime(true),
                    ];
                }

                [$startDate, $file, $startMicrotime] = $this->requestsPool[$requestPort];

                $formattedStartedAt = $startDate->format('Y-m-d H:i:s');

                unset($this->requestsPool[$requestPort]);

                [$date, $time] = explode(' ', $formattedStartedAt);

                $this->output->write("  <fg=gray>$date</> $time");

                $runTime = $this->runTimeForHumans($startMicrotime);

                if ($file) {
                    $this->output->write(" $file");
                }

                $dots = max(terminal()->width() - mb_strlen($formattedStartedAt) - mb_strlen($file) - mb_strlen($runTime) - 9, 0);

                $this->output->write(' ' . str_repeat('<fg=gray>.</>', $dots));
                $this->output->writeln(" <fg=gray>~ {$runTime}</>");
            } elseif (str_contains($line, 'Closed without sending a request') || str_contains($line, 'Failed to poll event')) {
                // ...
            } elseif (!empty($line)) {
                if (str_starts_with($line, '[')) {
                    $line = substr($line, strpos($line, ']') + 2);
                }

                $this->output->writeln("  <fg=gray>$line</>");
            }
        }
    }

    /**
     * Returns a "callable" to handle the process output.
     *
     * @return callable(string, string): void
     */
    protected function handleProcessOutput()
    {
        return function ($type, $buffer) {
            $this->outputBuffer .= $buffer;

            $this->flushOutputBuffer();
        };
    }

    public function serveCommand()
    {
        return [
            'php',
            '-S',
            $this->host() . ':' . $this->port(),
        ];
    }

    /**
     * Get the host for the command.
     *
     * @return string
     */
    protected function host()
    {
        [$host] = $this->getHostAndPort();

        if (empty($host[0]) && in_array(config('laraquest.update_type'), ['openswoole', 'swoole']))
            return config('server.openswoole.ip');

        return $host ?? "127.0.0.1";
    }

    /**
     * Get the port for the command.
     *
     * @return string
     */
    protected function port()
    {
        $port = $this->input->getOption('port');

        if (is_null($port)) {
            if (in_array(config('laraquest.update_type'), ['openswoole', 'swoole']))
                return config('server.openswoole.port');

            [, $port] = $this->getHostAndPort();
        }

        $port = $port ?: 9000;

        return $port + $this->portOffset;
    }

    /**
     * Get the host and port from the host option string.
     *
     * @return array
     */
    protected function getHostAndPort()
    {
        if (preg_match('/(\[.*\]):?([0-9]+)?/', $this->input->getOption('host') ?? '127.0.0.1', $matches) !== false) {
            return [
                $matches[1] ?? $this->input->getOption('host'),
                $matches[2] ?? null,
            ];
        }

        $hostParts = explode(':', $this->input->getOption('host'));

        return [
            $hostParts[0],
            $hostParts[1] ?? null,
        ];
    }

    /**
     * Check if the command has reached its maximum number of port tries.
     *
     * @return bool
     */
    protected function canTryAnotherPort()
    {
        return is_null($this->input->getOption('port')) &&
            ($this->input->getOption('tries') > $this->portOffset);
    }

    /**
     * Get the date from the given PHP server output.
     *
     * @param string $line
     * @return \DateTimeInterface
     */
    protected function getDateFromLine($line)
    {
        $regex = windows_os()
            ? '/^\[\d+]\s\[([a-zA-Z0-9: ]+)\]/'
            : '/^\[([^\]]+)\]/';

        $line = str_replace('  ', ' ', $line);

        preg_match($regex, $line, $matches);

        $date = \DateTime::createFromFormat('D M d H:i:s Y', $matches[1]);

        return $date;
    }

    /**
     * Get the request port from the given PHP server output.
     *
     * @param string $line
     * @return int
     */
    public static function getRequestPortFromLine($line)
    {
        preg_match('/(\[\w+\s\w+\s\d+\s[\d:]+\s\d{4}\]\s)?:(\d+)\s(?:(?:\w+$)|(?:\[.*))/', $line, $matches);

        if (!isset($matches[2])) {
            throw new \InvalidArgumentException("Failed to extract the request port. Ensure the log line contains a valid port: {$line}");
        }

        return (int)$matches[2];

    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['host', null, InputOption::VALUE_OPTIONAL, "Specify the host IP address for the development server"],
            ['port', null, InputOption::VALUE_OPTIONAL, 'Specify the port for the development server.'],
            ['openswoole', null, InputOption::VALUE_NONE, 'If set, the server will use OpenSwoole for serving requests.'],
            ['polling', null, InputOption::VALUE_NONE, 'If set, enables polling mode for handling requests.'],
            ['tries', null, InputOption::VALUE_OPTIONAL, 'The max number of ports to attempt to serve from', 10],
        ];
    }
}