<?php

namespace LaraGram\Foundation\Console;

use LaraGram\Console\Command;
use LaraGram\Console\Attribute\AsCommand;
use LaraGram\Console\Process\Process;
use LaraGram\Request\ConnectionResolver;
use LaraGram\Http\Client\Pool;
use LaraGram\Support\Collection;
use LaraGram\Support\Facades\Http;
use Throwable;
use function LaraGram\Console\Prompts\Convertor\terminal;
use function LaraGram\Support\php_binary;

#[AsCommand(name: 'poll')]
class PollCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'poll
                            {--c|connection=* : The bot connections to poll (the default one, or every one when it is "auto")}
                            {--timeout=30 : The number of seconds each getUpdates call waits for an update}
                            {--limit=100 : The maximum number of updates fetched at once}
                            {--concurrency=10 : The number of updates handled at the same time (1 handles them one by one, in order)}
                            {--drop-pending : Skip the updates that arrived before polling started}
                            {--delete-webhook : Delete the webhook of a connection that has one, instead of failing}
                            {--forward= : POST the updates to this URL (a running Surge or web server) instead of handling each in its own process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Receive bot updates with long polling';

    /**
     * Indicates if polling should stop.
     *
     * @var bool
     */
    protected $shouldStop = false;

    /**
     * The update handlers that are still running.
     *
     * @var array<int, array{process: \LaraGram\Console\Process\Process, type: string, id: int, startedAt: float}>
     */
    protected $running = [];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $connections = $this->connections();

        if ($connections === null) {
            return self::FAILURE;
        }

        if ($connections === []) {
            $this->components->error('No bot connection with a token is configured.');

            return self::FAILURE;
        }

        if (count($connections) > 1 && config('bot.default') !== 'auto') {
            $this->components->warn('Set the default bot connection to "auto" (BOT_CONNECTION=auto) so each update is handled with the bot it was sent to.');
        }

        if ($this->option('forward') && config('bot.default') === 'auto' && blank(config('app.key'))) {
            $this->components->warn('Forwarded updates are signed with the application key, which is not set; their connection is detected like a webhook\'s instead.');
        }

        $this->trap([SIGINT, SIGTERM], fn () => $this->shouldStop = true);

        return count($connections) === 1
            ? $this->poll($connections[0])
            : $this->pollEach($connections);
    }

    /**
     * Poll the given connection until the command is stopped.
     *
     * @param  string  $connection
     * @return int
     */
    protected function poll(string $connection)
    {
        if (! $this->prepare($connection)) {
            return self::FAILURE;
        }

        $offset = $this->option('drop-pending') ? $this->latestOffset($connection) : null;

        $this->components->info("Polling the [{$connection}] connection for updates.");
        $this->comment('  <fg=yellow;options=bold>Press Ctrl+C to stop polling</>');
        $this->newLine();

        $allowed = config("bot.connections.{$connection}.allowed_updates");

        while (! $this->shouldStop) {
            $response = $this->api($connection)->getUpdates(
                $offset,
                (int) $this->option('limit'),
                // Check on the running handlers every second while there are any.
                $this->running === [] ? (int) $this->option('timeout') : 1,
                $allowed == ['*'] ? null : $allowed,
            );

            if (! ($response['ok'] ?? false)) {
                if (! $this->recover($connection, $response)) {
                    return self::FAILURE;
                }

                continue;
            }

            $updates = $response['result'] ?? [];

            if ($updates !== []) {
                $offset = end($updates)['update_id'] + 1;
            }

            if ($this->option('forward')) {
                $this->forward($connection, $updates);
            } else {
                foreach ($updates as $update) {
                    $this->dispatch($connection, $update);
                }
            }

            $this->collectFinished();
        }

        $this->finish($connection, $offset);

        return self::SUCCESS;
    }

    /**
     * Poll every given connection in its own process.
     *
     * @param  list<string>  $connections
     * @return int
     */
    protected function pollEach(array $connections)
    {
        $processes = (new Collection($connections))->mapWithKeys(fn ($connection) => [
            $connection => tap(new Process($this->pollCommand($connection), base_path(), null, null, null))->start(),
        ]);

        while ($processes->contains(fn ($process) => $process->isRunning())) {
            foreach ($processes as $connection => $process) {
                $this->forwardOutput($connection, $process);
            }

            if ($this->shouldStop) {
                $processes->each(fn ($process) => $process->isRunning() && $process->signal(SIGTERM));
            }

            usleep(100 * 1000);
        }

        $processes->each(fn ($process, $connection) => $this->forwardOutput($connection, $process));

        return $processes->every(fn ($process) => $process->getExitCode() === 0) ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Get the connections to poll, or null when one of the given connections does not exist.
     *
     * @return list<string>|null
     */
    protected function connections()
    {
        $configured = (array) config('bot.connections', []);

        if (($names = $this->option('connection')) !== []) {
            foreach ($names as $name) {
                if (! isset($configured[$name])) {
                    $this->components->error("The bot connection [{$name}] is not configured.");

                    return null;
                }
            }

            return array_values(array_unique($names));
        }

        $default = config('bot.default');

        if (! blank($default) && $default !== 'auto') {
            return [(string) $default];
        }

        return array_keys(array_filter($configured, fn ($connection) => ! blank($connection['token'] ?? null)));
    }

    /**
     * Make sure the connection may poll: a connection with a webhook cannot.
     *
     * @param  string  $connection
     * @return bool
     */
    protected function prepare(string $connection)
    {
        $info = $this->api($connection)->getWebhookInfo();

        if (! ($info['ok'] ?? false)) {
            $this->components->error("[{$connection}] ".($info['description'] ?? 'The Bot API server could not be reached.'));

            return false;
        }

        if (($url = $info['result']['url'] ?? '') === '') {
            return true;
        }

        if (! $this->option('delete-webhook')) {
            $this->components->error("The [{$connection}] connection has a webhook ({$url}). Run [php laragram webhook:delete --connection={$connection}] or poll with --delete-webhook.");

            return false;
        }

        $this->api($connection)->deleteWebhook();

        $this->components->warn("Deleted the webhook of the [{$connection}] connection ({$url}).");

        return true;
    }

    /**
     * Handle a failed getUpdates call, returning false when polling cannot go on.
     *
     * @param  string  $connection
     * @param  \LaraGram\Laraquest\Response|array  $response
     * @return bool
     */
    protected function recover(string $connection, $response)
    {
        $code = (int) ($response['error_code'] ?? 0);
        $description = (string) ($response['description'] ?? 'Unknown error');

        if ($this->shouldStop) {
            return true;
        }

        // 409: a webhook was set (or another poller started) meanwhile.
        if (in_array($code, [401, 404, 409], true)) {
            $this->components->error("[{$connection}] {$description}");

            return false;
        }

        $delay = (int) ($response['parameters']['retry_after'] ?? 3);

        $this->components->warn("[{$connection}] {$description}. Retrying in {$delay}s.");

        sleep(max(1, $delay));

        return true;
    }

    /**
     * Handle the given update in its own process, like a webhook request.
     *
     * @param  string  $connection
     * @param  array  $update
     * @return void
     */
    protected function dispatch(string $connection, array $update)
    {
        while (count($this->running) >= max(1, (int) $this->option('concurrency'))) {
            usleep(10 * 1000);

            $this->collectFinished();
        }

        $process = new Process([
            php_binary(),
            $this->serverPath(),
            json_encode($update),
            json_encode([
                ConnectionResolver::SERVER_VARIABLE => $connection,
                'REQUEST_TIME' => time(),
                'REQUEST_TIME_FLOAT' => microtime(true),
            ]),
        ], base_path(), null, null, null);

        $process->start();

        $this->running[] = [
            'process' => $process,
            'type' => (string) (array_key_last(array_diff_key($update, ['update_id' => 0])) ?? 'update'),
            'id' => $update['update_id'],
            'startedAt' => microtime(true),
        ];

        if ((int) $this->option('concurrency') <= 1) {
            $process->wait();

            $this->collectFinished();
        }
    }

    /**
     * POST the given updates to the forward URL the way Telegram calls a webhook,
     * retrying the ones the server could not take for a few seconds.
     *
     * @param  string  $connection
     * @param  list<array>  $updates
     * @return void
     */
    protected function forward(string $connection, array $updates)
    {
        $resolver = app(ConnectionResolver::class);
        $secret = config("bot.connections.{$connection}.secret_token");

        for ($attempt = 1; $updates !== [] && $attempt <= 5; $attempt++) {
            $startedAt = microtime(true);

            $responses = Http::pool(fn (Pool $pool) => array_map(fn ($update) => $pool->as((string) $update['update_id'])
                ->withHeaders(array_filter([
                    'X-Telegram-Bot-Api-Secret-Token' => $secret,
                    ConnectionResolver::FORWARDED_HEADER => $connection,
                    ConnectionResolver::SIGNATURE_HEADER => $resolver->signature($connection, $update['update_id']),
                ]))
                ->timeout((int) config('surge.max_execution_time', 30) + 5)
                ->withBody(json_encode($update), 'application/json')
                ->post($this->option('forward')), $updates), max(1, (int) $this->option('concurrency')));

            $failed = [];

            foreach ($updates as $update) {
                $response = $responses[(string) $update['update_id']] ?? null;
                $error = $response instanceof Throwable
                    ? $response->getMessage()
                    : ($response?->successful() ? null : 'HTTP '.$response?->status());

                if ($error !== null && $attempt < 5) {
                    $failed[] = $update;

                    continue;
                }

                $this->writeForwarded($update, $startedAt, $error);
            }

            if (($updates = $failed) !== []) {
                sleep($attempt);
            }
        }
    }

    /**
     * Write the line of a forwarded update.
     *
     * @param  array  $update
     * @param  float  $startedAt
     * @param  string|null  $error
     * @return void
     */
    protected function writeForwarded(array $update, float $startedAt, ?string $error)
    {
        $this->writeLine(
            (string) (array_key_last(array_diff_key($update, ['update_id' => 0])) ?? 'update'),
            $update['update_id'],
            $startedAt,
            $error !== null,
        );

        if ($error !== null) {
            $this->output->writeln("  <fg=red>Could not forward the update: {$error}</>");
        }
    }

    /**
     * Report the update handlers that finished and forget them.
     *
     * @return void
     */
    protected function collectFinished()
    {
        foreach ($this->running as $key => $handler) {
            if ($handler['process']->isRunning()) {
                continue;
            }

            unset($this->running[$key]);

            $this->writeHandled($handler);
        }
    }

    /**
     * Write the line of a handled update, followed by whatever its handler printed.
     *
     * @param  array{process: \LaraGram\Console\Process\Process, type: string, id: int, startedAt: float}  $handler
     * @return void
     */
    protected function writeHandled(array $handler)
    {
        $process = $handler['process'];

        $this->writeLine($handler['type'], $handler['id'], $handler['startedAt'], ! $process->isSuccessful());

        foreach (array_filter(explode("\n", $process->getOutput().$process->getErrorOutput())) as $line) {
            $this->output->writeln('  <fg=gray>'.trim($line).'</>');
        }
    }

    /**
     * Write the line of a handled update.
     *
     * @param  string  $type
     * @param  int  $id
     * @param  float  $startedAt
     * @param  bool  $failed
     * @return void
     */
    protected function writeLine(string $type, int $id, float $startedAt, bool $failed)
    {
        $time = now()->format('Y-m-d H:i:s');
        $runTime = number_format((microtime(true) - $startedAt) * 1000).'ms';
        $label = "{$type} #{$id}";

        $dots = max(terminal()->width() - mb_strlen($time) - mb_strlen($label) - mb_strlen($runTime) - 9, 0);

        $this->output->writeln(sprintf(
            '  <fg=gray>%s</> %s %s <fg=%s>~ %s</>',
            $time,
            $failed ? "<fg=red>{$label}</>" : $label,
            str_repeat('<fg=gray>.</>', $dots),
            $failed ? 'red' : 'gray',
            $runTime,
        ));
    }

    /**
     * Wait for the running handlers and confirm the received updates, so they
     * are not delivered again by the next poll.
     *
     * @param  string  $connection
     * @param  int|null  $offset
     * @return void
     */
    protected function finish(string $connection, ?int $offset)
    {
        while ($this->running !== []) {
            usleep(10 * 1000);

            $this->collectFinished();
        }

        if ($offset !== null) {
            $this->api($connection)->getUpdates($offset, 1, 0);
        }

        $this->components->info("Stopped polling the [{$connection}] connection.");
    }

    /**
     * Get the offset after the newest pending update, skipping every pending update.
     *
     * @param  string  $connection
     * @return int|null
     */
    protected function latestOffset(string $connection)
    {
        $response = $this->api($connection)->getUpdates(-1, 1, 0);

        $last = $response['result'][0]['update_id'] ?? null;

        return $last === null ? null : $last + 1;
    }

    /**
     * Get the Bot API client of the given connection.
     *
     * @param  string  $connection
     * @return \LaraGram\Laraquest\Methode
     */
    protected function api(string $connection)
    {
        return app('request')->connection($connection)->silent();
    }

    /**
     * Get the command that polls the given connection on its own.
     *
     * @param  string  $connection
     * @return list<string>
     */
    protected function pollCommand(string $connection)
    {
        return array_values(array_filter([
            php_binary(),
            base_path('laragram'),
            'poll',
            '--connection='.$connection,
            '--timeout='.$this->option('timeout'),
            '--limit='.$this->option('limit'),
            '--concurrency='.$this->option('concurrency'),
            $this->option('drop-pending') ? '--drop-pending' : null,
            $this->option('delete-webhook') ? '--delete-webhook' : null,
            $this->option('forward') ? '--forward='.$this->option('forward') : null,
            $this->output->isDecorated() ? '--ansi' : '--no-ansi',
        ]));
    }

    /**
     * Forward the new output of a connection's poller, prefixed with its name.
     *
     * @param  string  $connection
     * @param  \LaraGram\Console\Process\Process  $process
     * @return void
     */
    protected function forwardOutput(string $connection, $process)
    {
        $output = $process->getIncrementalOutput().$process->getIncrementalErrorOutput();

        foreach (array_filter(explode("\n", $output), fn ($line) => trim($line) !== '') as $line) {
            $this->output->writeln("<fg=blue>[{$connection}]</>{$line}");
        }
    }

    /**
     * Get the path of the script that handles a single update.
     *
     * @return string
     */
    protected function serverPath()
    {
        // The script finds the application from its own path, so keep the vendor path when it exists.
        $vendor = base_path('vendor/laraxgram/core/src/Foundation/resources/server.php');

        return is_file($vendor) ? $vendor : realpath(__DIR__.'/../resources/server.php');
    }
}
