<?php

namespace LaraGram\Request;

use LaraGram\Request\Exceptions\RequestResponseException;
use Throwable;

class UpdateDumper
{
    /**
     * The longest code block sent in one message (Telegram allows 4096 characters).
     *
     * @var int
     */
    protected const CHUNK = 3900;

    /**
     * Create a new update dumper instance.
     *
     * @param  \LaraGram\Request\Request  $request
     * @return void
     */
    public function __construct(protected Request $request)
    {
        //
    }

    /**
     * Show a value as `$request->dd()` / `dump()` / `log()` asks.
     *
     * @param  mixed  $value
     * @param  string  $action  "dd", "dump" or "log"
     * @return void
     *
     * @throws \LaraGram\Request\Exceptions\RequestResponseException
     */
    public function handle(mixed $value, string $action): void
    {
        if ($action === 'log' || ! $this->send($value)) {
            $this->log($value);
        }

        if ($action === 'dd') {
            // Stop the handler like dd(), without exiting a long-running worker.
            throw new RequestResponseException(new Response);
        }
    }

    /**
     * Send the value to the chat of the update, as pretty-printed JSON.
     *
     * Falls back to the user's private chat for updates without a chat, such
     * as inline queries. Returns false when there is nowhere to send it.
     *
     * @param  mixed  $value
     * @return bool
     */
    public function send(mixed $value): bool
    {
        $chatId = chat()->id ?? user()->id ?? null;

        if ($chatId === null) {
            return false;
        }

        try {
            foreach ($this->chunks($this->json($value)) as $chunk) {
                $response = $this->request->silent()->withoutAntiFlood()->sendMessage(
                    $chatId,
                    "```json\n".str_replace(['\\', '`'], ['\\\\', '\\`'], $chunk)."\n```",
                    parse_mode: 'MarkdownV2',
                );

                if (! ($response['ok'] ?? false)) {
                    return false;
                }
            }
        } catch (Throwable) {
            return false;
        }

        return true;
    }

    /**
     * Write the value to the log, as pretty-printed JSON.
     *
     * @param  mixed  $value
     * @return void
     */
    public function log(mixed $value): void
    {
        logger()->debug('Telegram update #'.($this->request->update_id ?? '?')."\n".$this->json($value));
    }

    /**
     * Encode the value as pretty-printed JSON.
     *
     * @param  mixed  $value
     * @return string
     */
    protected function json(mixed $value): string
    {
        return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE) ?: 'null';
    }

    /**
     * Split the JSON on line breaks into pieces that fit in one message.
     *
     * @param  string  $json
     * @return array<int, string>
     */
    protected function chunks(string $json): array
    {
        $chunks = [];
        $current = '';

        foreach (explode("\n", $json) as $line) {
            foreach (mb_str_split($line, static::CHUNK) ?: [''] as $piece) {
                if ($current !== '' && mb_strlen($current) + mb_strlen($piece) + 1 > static::CHUNK) {
                    $chunks[] = $current;
                    $current = '';
                }

                $current .= ($current === '' ? '' : "\n").$piece;
            }
        }

        $chunks[] = $current;

        return $chunks;
    }
}
