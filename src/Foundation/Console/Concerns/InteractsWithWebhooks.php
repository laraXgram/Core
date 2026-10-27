<?php

namespace LaraGram\Foundation\Console\Concerns;

use LaraGram\Console\Input\InputOption;

trait InteractsWithWebhooks
{
    /**
     * Run the callback for every bot connection the command applies to.
     *
     * Without --connection every configured connection with a token is
     * handled in turn; with it, only the given ones.
     *
     * @param  callable(string): bool  $callback  Returns whether the connection succeeded.
     * @return int
     */
    protected function eachConnection(callable $callback)
    {
        $connections = $this->webhookConnections();

        if ($connections === null) {
            return self::FAILURE;
        }

        if ($connections === []) {
            $this->components->error('No bot connection with a token is configured.');

            return self::FAILURE;
        }

        $failed = false;

        foreach ($connections as $connection) {
            $failed = ! $callback($connection) || $failed;
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Get the bot connections to handle, or null when a given one does not exist.
     *
     * @return list<string>|null
     */
    protected function webhookConnections()
    {
        $configured = (array) config('bot.connections', []);

        if (($names = (array) $this->option('connection')) !== []) {
            foreach ($names as $name) {
                if (! isset($configured[$name])) {
                    $this->components->error("The bot connection [{$name}] is not configured.");

                    return null;
                }
            }

            return array_values(array_unique($names));
        }

        return array_keys(array_filter($configured, fn ($connection) => filled($connection['token'] ?? null)));
    }

    /**
     * Get the Bot API client of the given connection.
     *
     * @param  string  $connection
     * @return \LaraGram\Laraquest\Methode
     */
    protected function bot(string $connection)
    {
        return app('request')->connection($connection)->silent();
    }

    /**
     * Get the message of a Bot API answer.
     *
     * @param  \LaraGram\Laraquest\Response|array  $result
     * @return string
     */
    protected function describe($result)
    {
        return (string) ($result['description'] ?? $result['message'] ?? ($result['ok'] ?? false ? 'Done.' : 'The request failed.'));
    }

    /**
     * Get the webhook settings of a connection, as setWebhook takes them.
     *
     * @param  string  $connection
     * @return array{url: mixed, allowed_updates: mixed, secret_token: mixed}
     */
    protected function webhookSettings(string $connection)
    {
        $allowed = config("bot.connections.{$connection}.allowed_updates");

        return [
            'url' => config("bot.connections.{$connection}.url"),
            'allowed_updates' => $allowed == ['*'] ? null : $allowed,
            'secret_token' => config("bot.connections.{$connection}.secret_token"),
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['connection', 'c', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'The bot connection (every connection by default)'],
        ];
    }
}
