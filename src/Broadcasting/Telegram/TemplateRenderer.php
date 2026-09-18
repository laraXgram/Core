<?php

namespace LaraGram\Broadcasting\Telegram;

use LaraGram\Broadcasting\BroadcastException;
use LaraGram\Contracts\Container\Container;
use LaraGram\Request\Request;
use LaraGram\Template\Compilers\Temple8Compiler;
use Throwable;

class TemplateRenderer
{
    /**
     * Create a new template renderer.
     *
     * @param  \LaraGram\Contracts\Container\Container  $app
     */
    public function __construct(protected Container $app)
    {
        //
    }

    /**
     * Render the template action for a recipient.
     *
     * @param  \LaraGram\Broadcasting\Telegram\Action  $action
     * @param  \LaraGram\Broadcasting\Telegram\Recipient  $recipient
     * @param  string  $bot
     * @param  string  $broadcastId
     * @param  bool  $localized  Render with the recipient's language as the locale.
     * @return array<int, array{method: string, parameters: array}>
     *
     * @throws \LaraGram\Broadcasting\BroadcastException
     */
    public function render(Action $action, Recipient $recipient, string $bot, string $broadcastId, bool $localized = false): array
    {
        $template = $action->template;

        $data = array_merge((array) ($template['data'] ?? []), [
            'recipient' => $recipient,
            'broadcast' => $broadcastId,
        ]);

        $previousRequest = $this->app->bound('request') ? $this->app->make('request') : null;
        $previousLocale = $localized ? $this->app->getLocale() : null;

        $this->app->instance('request', $this->fakeRequest($recipient, $bot));

        if ($localized && $recipient->language_code) {
            $this->app->setLocale($recipient->language_code);
        }

        try {
            $calls = Request::recordCalls(fn () => $this->renderTemplate($template, $data));
        } catch (Throwable $e) {
            throw new BroadcastException('Unable to render broadcast template ['.$this->label($template).']: '.$e->getMessage(), 0, $e);
        } finally {
            if ($previousRequest !== null) {
                $this->app->instance('request', $previousRequest);
            } else {
                $this->app->forgetInstance('request');
            }

            if ($previousLocale !== null) {
                $this->app->setLocale($previousLocale);
            }
        }

        return array_map(fn (array $call) => [
            'method' => $call['method'],
            'parameters' => $call['parameters'],
        ], $calls);
    }

    /**
     * Render the template from its source.
     *
     * @param  array  $template
     * @param  array  $data
     * @return void
     */
    protected function renderTemplate(array $template, array $data): void
    {
        $factory = $this->app->make(\LaraGram\Contracts\Template\Factory::class);

        match ($template['source'] ?? 'name') {
            'inline' => Temple8Compiler::render($template['template'], $data),
            'path' => $factory->file($template['template'], $data)->render(),
            default => $factory->make($template['template'], $data)->render(),
        };
    }

    /**
     * Get a short label of the template for error messages.
     *
     * @param  array  $template
     * @return string
     */
    protected function label(array $template): string
    {
        return ($template['source'] ?? 'name') === 'inline'
            ? 'inline: '.mb_strimwidth(preg_replace('/\s+/', ' ', $template['template']), 0, 40, '...')
            : $template['template'];
    }

    /**
     * Build a request whose update comes from the recipient.
     *
     * @param  \LaraGram\Broadcasting\Telegram\Recipient  $recipient
     * @param  string  $bot
     * @return \LaraGram\Request\Request
     */
    protected function fakeRequest(Recipient $recipient, string $bot): Request
    {
        $chat = array_filter([
            'id' => $recipient->chat_id,
            'type' => $recipient->type,
            'title' => $recipient->type === 'private' ? null : $recipient->title,
            'username' => $recipient->username,
            'first_name' => $recipient->first_name,
            'last_name' => $recipient->last_name,
        ], fn ($value) => $value !== null);

        $user = array_filter([
            'id' => $recipient->chat_id,
            'is_bot' => false,
            'first_name' => $recipient->first_name ?? $recipient->title ?? '',
            'last_name' => $recipient->last_name,
            'username' => $recipient->username,
            'language_code' => $recipient->language_code,
        ], fn ($value) => $value !== null);

        $request = Request::createFromBase([null, json_encode([
            'update_id' => 0,
            'message' => [
                'message_id' => 0,
                'date' => time(),
                'chat' => $chat,
                'from' => $user,
            ],
        ]), json_encode([])]);

        return $request->connection($bot);
    }
}
