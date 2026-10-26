<?php

namespace LaraGram\Foundation\Console;

use CurlHandle;
use CurlMultiHandle;
use LaraGram\Console\Command;
use LaraGram\Console\Attribute\AsCommand;
use LaraGram\Console\Process\Process;
use LaraGram\Request\ConnectionResolver;
use LaraGram\Support\Collection;
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
     * The number of times a forwarded update is tried before it is given up.
     *
     * @var int
     */
    protected const FORWARD_ATTEMPTS = 5;

    /**
     * Indicates if polling should stop.
     *
     * @var bool
     */
    protected $shouldStop = false;

    /**
     * The cURL multi handle every request of the poll loop runs on.
     *
     * @var \CurlMultiHandle
     */
    protected CurlMultiHandle $multi;

    /**
     * The getUpdates request in flight, if any.
     *
     * @var \CurlHandle|null
     */
    protected ?CurlHandle $poll = null;

    /**
     * The time before which no getUpdates request is sent (after a failure).
     *
     * @var float
     */
    protected float $pollAfter = 0.0;

    /**
     * The updates waiting for a free slot, with their attempt number and earliest start.
     *
     * @var list<array{update: array, attempt: int, after: float}>
     */
    protected array $queue = [];

    /**
     * The updates being handled: a process, or a forwarding request.
     *
     * @var array<int, array{update: array, attempt: int, startedAt: float, process?: \LaraGram\Console\Process\Process, handle?: \CurlHandle}>
     */
    protected array $running = [];

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
     * Nothing blocks the loop: the getUpdates request, the forwarded updates
     * and the handler processes all advance together. A full queue only holds
     * the next getUpdates request back.
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

        $this->multi = curl_multi_init();

        try {
            while (! $this->shouldStop) {
                if ($this->poll === null && $this->queue === [] && microtime(true) >= $this->pollAfter) {
                    $this->poll = $this->startPoll($connection, $offset);
                }

                $this->startQueued($connection);

                if (! $this->tick()) {
                    return self::FAILURE;
                }

                $offset = $this->handleFinishedRequests($connection, $offset) ?? $offset;

                if ($offset === -1) {
                    return self::FAILURE;
                }

                $this->collectFinished($connection);
            }

            // Stop waiting for updates; the ones it would have brought are delivered again later.
            $this->closeRequest($this->poll);
            $this->poll = null;

            $this->drain($connection);
        } finally {
            curl_multi_close($this->multi);
        }

        $this->finish($connection, $offset);

        return self::SUCCESS;
    }

    /**
     * Run the requests on the multi handle and wait briefly for activity.
     *
     * @return bool
     */
    protected function tick()
    {
        do {
            $status = curl_multi_exec($this->multi, $active);
        } while ($status === CURLM_CALL_MULTI_PERFORM);

        if ($status !== CURLM_OK) {
            $this->components->error('cURL failed: '.curl_multi_strerror($status));

            return false;
        }

        // Check on running processes often; otherwise just wait for the network.
        $wait = $this->hasRunningProcesses() || $this->queue !== [] ? 0.02 : 0.5;

        if ($active > 0) {
            curl_multi_select($this->multi, $wait);
        } else {
            usleep((int) ($wait * 1_000_000));
        }

        return true;
    }

    /**
     * Handle the requests that finished: getUpdates answers and forwarded updates.
     *
     * Returns the new offset, -1 when polling cannot go on, or null when the
     * offset did not change.
     *
     * @param  string  $connection
     * @param  int|null  $offset
     * @return int|null
     */
    protected function handleFinishedRequests(string $connection, ?int $offset)
    {
        $newOffset = null;

        while (($info = curl_multi_info_read($this->multi)) !== false) {
            $handle = $info['handle'];

            if ($handle === $this->poll) {
                $this->poll = null;

                $response = $this->decode($handle, $info['result']);

                $this->closeRequest($handle);

                if (! ($response['ok'] ?? false)) {
                    if (! $this->recover($connection, $response)) {
                        return -1;
                    }

                    continue;
                }

                foreach ($response['result'] ?? [] as $update) {
                    $newOffset = $update['update_id'] + 1;

                    $this->queue[] = ['update' => $update, 'attempt' => 1, 'after' => 0.0];
                }

                continue;
            }

            $this->finishForward($handle, $info['result']);
        }

        return $newOffset;
    }

    /**
     * Start the queued updates that are due, while there is a free slot.
     *
     * The queue is first-in, first-out, so with a concurrency of 1 updates are
     * handled one by one, in the order they arrived.
     *
     * @param  string  $connection
     * @return void
     */
    protected function startQueued(string $connection)
    {
        $concurrency = max(1, (int) $this->option('concurrency'));
        $now = microtime(true);

        while ($this->queue !== [] && count($this->running) < $concurrency && $this->queue[0]['after'] <= $now) {
            $item = array_shift($this->queue);

            $entry = ['update' => $item['update'], 'attempt' => $item['attempt'], 'startedAt' => microtime(true)];

            if ($this->option('forward')) {
                $entry['handle'] = $this->startForward($connection, $item['update']);
            } else {
                $entry['process'] = $this->startProcess($connection, $item['update']);
            }

            $this->running[] = $entry;
        }
    }

    /**
     * Report the handler processes that finished and forget them.
     *
     * @param  string  $connection
     * @return void
     */
    protected function collectFinished(string $connection)
    {
        foreach ($this->running as $key => $entry) {
            if (! isset($entry['process']) || $entry['process']->isRunning()) {
                continue;
            }

            unset($this->running[$key]);

            $process = $entry['process'];

            $this->writeLine($entry['update'], $entry['startedAt'], ! $process->isSuccessful());

            foreach (array_filter(explode("\n", $process->getOutput().$process->getErrorOutput())) as $line) {
                $this->output->writeln('  <fg=gray>'.trim($line).'</>');
            }
        }
    }

    /**
     * Wait for the queued and running updates to be handled.
     *
     * @param  string  $connection
     * @return void
     */
    protected function drain(string $connection)
    {
        while ($this->queue !== [] || $this->running !== []) {
            $this->startQueued($connection);

            $this->tick();

            $this->handleFinishedRequests($connection, null);

            $this->collectFinished($connection);
        }
    }

    /**
     * Start the getUpdates request.
     *
     * @param  string  $connection
     * @param  int|null  $offset
     * @return \CurlHandle
     */
    protected function startPoll(string $connection, ?int $offset)
    {
        $allowed = config("bot.connections.{$connection}.allowed_updates");
        $timeout = (int) $this->option('timeout');

        $handle = $this->request($this->apiUrl($connection, 'getUpdates'), array_filter([
            'offset' => $offset,
            'limit' => (int) $this->option('limit'),
            'timeout' => $timeout,
            'allowed_updates' => $allowed == ['*'] ? null : json_encode($allowed),
        ], fn ($value) => $value !== null), [], $timeout + 15);

        curl_multi_add_handle($this->multi, $handle);

        return $handle;
    }

    /**
     * Start handling the given update in its own process, like a webhook request.
     *
     * @param  string  $connection
     * @param  array  $update
     * @return \LaraGram\Console\Process\Process
     */
    protected function startProcess(string $connection, array $update)
    {
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

        return $process;
    }

    /**
     * Start posting the given update to the forward URL, the way Telegram calls a webhook.
     *
     * @param  string  $connection
     * @param  array  $update
     * @return \CurlHandle
     */
    protected function startForward(string $connection, array $update)
    {
        $headers = array_filter([
            'X-Telegram-Bot-Api-Secret-Token' => config("bot.connections.{$connection}.secret_token"),
            ConnectionResolver::FORWARDED_HEADER => $connection,
            ConnectionResolver::SIGNATURE_HEADER => app(ConnectionResolver::class)->signature($connection, $update['update_id']),
        ]);

        $handle = $this->request(
            $this->option('forward'),
            json_encode($update),
            ['Content-Type: application/json', ...array_map(fn ($name, $value) => "{$name}: {$value}", array_keys($headers), $headers)],
            (int) config('surge.max_execution_time', 30) + 5,
        );

        curl_multi_add_handle($this->multi, $handle);

        return $handle;
    }

    /**
     * Handle a finished forwarding request, queueing the update again when it failed.
     *
     * @param  \CurlHandle  $handle
     * @param  int  $result
     * @return void
     */
    protected function finishForward(CurlHandle $handle, int $result)
    {
        foreach ($this->running as $key => $entry) {
            if (($entry['handle'] ?? null) !== $handle) {
                continue;
            }

            unset($this->running[$key]);

            $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);

            $error = match (true) {
                $result !== CURLE_OK => curl_strerror($result).' ('.curl_error($handle).')',
                $status < 200 || $status >= 300 => "HTTP {$status}",
                default => null,
            };

            $this->closeRequest($handle);

            if ($error !== null && $entry['attempt'] < static::FORWARD_ATTEMPTS) {
                // Back off without blocking: the update waits in the queue.
                array_unshift($this->queue, [
                    'update' => $entry['update'],
                    'attempt' => $entry['attempt'] + 1,
                    'after' => microtime(true) + $entry['attempt'],
                ]);

                return;
            }

            $this->writeLine($entry['update'], $entry['startedAt'], $error !== null);

            if ($error !== null) {
                $this->output->writeln("  <fg=red>Could not forward the update: {$error}</>");
            }

            return;
        }
    }

    /**
     * Build a POST request.
     *
     * @param  string  $url
     * @param  array|string  $body
     * @param  list<string>  $headers
     * @param  int  $timeout
     * @return \CurlHandle
     */
    protected function request(string $url, array|string $body, array $headers, int $timeout)
    {
        $handle = curl_init($url);

        curl_setopt_array($handle, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_TCP_NODELAY => true,
        ]);

        return $handle;
    }

    /**
     * Remove a request from the multi handle and close it.
     *
     * @param  \CurlHandle|null  $handle
     * @return void
     */
    protected function closeRequest(?CurlHandle $handle)
    {
        if ($handle === null) {
            return;
        }

        curl_multi_remove_handle($this->multi, $handle);
        curl_close($handle);
    }

    /**
     * Decode the answer of a finished Bot API request.
     *
     * @param  \CurlHandle  $handle
     * @param  int  $result
     * @return array
     */
    protected function decode(CurlHandle $handle, int $result)
    {
        if ($result !== CURLE_OK) {
            return ['ok' => false, 'error_code' => 0, 'description' => curl_strerror($result).' ('.curl_error($handle).')'];
        }

        $decoded = json_decode((string) curl_multi_getcontent($handle), true);

        return is_array($decoded)
            ? $decoded
            : ['ok' => false, 'error_code' => 0, 'description' => 'Malformed response from the Bot API server'];
    }

    /**
     * Handle a failed getUpdates call, returning false when polling cannot go on.
     *
     * @param  string  $connection
     * @param  array  $response
     * @return bool
     */
    protected function recover(string $connection, array $response)
    {
        $code = (int) ($response['error_code'] ?? 0);
        $description = (string) ($response['description'] ?? 'Unknown error');

        // 409: a webhook was set (or another poller started) meanwhile.
        if (in_array($code, [401, 404, 409], true)) {
            $this->components->error("[{$connection}] {$description}");

            return false;
        }

        $delay = max(1, (int) ($response['parameters']['retry_after'] ?? 3));

        $this->components->warn("[{$connection}] {$description}. Retrying in {$delay}s.");

        $this->pollAfter = microtime(true) + $delay;

        return true;
    }

    /**
     * Determine if any update is being handled by a process.
     *
     * @return bool
     */
    protected function hasRunningProcesses()
    {
        foreach ($this->running as $entry) {
            if (isset($entry['process'])) {
                return true;
            }
        }

        return false;
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
     * Confirm the received updates, so they are not delivered again by the next poll.
     *
     * @param  string  $connection
     * @param  int|null  $offset
     * @return void
     */
    protected function finish(string $connection, ?int $offset)
    {
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
     * Write the line of a handled update.
     *
     * @param  array  $update
     * @param  float  $startedAt
     * @param  bool  $failed
     * @return void
     */
    protected function writeLine(array $update, float $startedAt, bool $failed)
    {
        $type = (string) (array_key_last(array_diff_key($update, ['update_id' => 0])) ?? 'update');

        $time = now()->format('Y-m-d H:i:s');
        $runTime = number_format((microtime(true) - $startedAt) * 1000).'ms';
        $label = "{$type} #{$update['update_id']}";

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
     * Get the URL of a Bot API method of the given connection.
     *
     * @param  string  $connection
     * @param  string  $method
     * @return string
     */
    protected function apiUrl(string $connection, string $method)
    {
        $endpoint = rtrim((string) config('bot.api_server.endpoint', 'https://api.telegram.org'), '/');

        return $endpoint.'/bot'.config("bot.connections.{$connection}.token").'/'.$method;
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
