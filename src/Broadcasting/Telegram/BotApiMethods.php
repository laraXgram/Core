<?php

namespace LaraGram\Broadcasting\Telegram;

use LaraGram\Laraquest\Schema\ApiSchema;
use Throwable;

class BotApiMethods
{
    /**
     * The fields Laraquest puts first, as in its generator.
     *
     * @var array<int, string>
     */
    protected const TEXT_FIELDS = ['text', 'caption'];

    /**
     * @var array<int, string>
     */
    protected const MEDIA_FIELDS = ['photo', 'audio', 'document', 'video', 'animation', 'voice', 'video_note', 'sticker', 'media', 'thumbnail', 'thumb'];

    /**
     * @var array<int, string>
     */
    protected const MESSAGE_ID_FIELDS = ['message_id', 'message_ids'];

    /**
     * The version of the generated code; bump it when the output changes.
     *
     * @var int
     */
    protected const FORMAT = 2;

    /**
     * Indicates if the trait was already checked in this process.
     */
    protected static bool $checked = false;

    /**
     * Regenerate the trait when the schema changed. Never throws.
     *
     * @return void
     */
    public static function ensure(): void
    {
        if (static::$checked) {
            return;
        }

        static::$checked = true;

        try {
            if (! class_exists(ApiSchema::class) || ! ApiSchema::exists()) {
                return;
            }

            $fingerprint = static::fingerprint();

            if (static::currentFingerprint() === $fingerprint) {
                return;
            }

            static::write(static::render(ApiSchema::methods(), $fingerprint));
        } catch (Throwable) {
            //
        }
    }

    /**
     * Get the path of the generated trait.
     *
     * @return string
     */
    public static function path(): string
    {
        return __DIR__.'/Concerns/BroadcastsBotApiMethods.php';
    }

    /**
     * Render the trait for the given schema methods.
     *
     * @param  array<string, array>  $methods
     * @param  string  $fingerprint
     * @return string
     */
    public static function render(array $methods, string $fingerprint): string
    {
        ksort($methods);

        $body = '';

        foreach ($methods as $name => $method) {
            $names = array_column($method['parameters'] ?? [], 'name');

            $recipient = match (true) {
                in_array('chat_id', $names, true) => 'chat_id',
                in_array('user_id', $names, true) => 'user_id',
                default => null,
            };

            if ($recipient !== null) {
                $body .= static::renderMethod($name, $method, $recipient);
            }
        }

        return <<<PHP
<?php

// schema: {$fingerprint}

namespace LaraGram\\Broadcasting\\Telegram\\Concerns;

use LaraGram\\Broadcasting\\Telegram\\TelegramBroadcast;

/**
 * @generated
 *
 * The Bot API methods that target a chat or a user, for Telegram broadcasts.
 *
 * Each method takes Laraquest's parameters, without the recipient (chat_id,
 * or user_id for methods that have no chat_id), which the broadcast fills in
 * for every recipient, and returns the broadcast so only its delivery
 * methods can follow.
 *
 * Generated from the Laraquest Bot API schema. Changes are overwritten when
 * the schema changes.
 */
trait BroadcastsBotApiMethods
{
{$body}}

PHP;
    }

    /**
     * Render one method.
     *
     * @param  string  $name
     * @param  array  $method
     * @param  string  $recipient
     * @return string
     */
    protected static function renderMethod(string $name, array $method, string $recipient): string
    {
        $isEdit = str_starts_with($name, 'edit');

        $parameters = array_values(array_filter($method['parameters'] ?? [], fn ($p) => $p['name'] !== $recipient));

        $ordered = array_merge(
            static::order(array_filter($parameters, fn ($p) => ! empty($p['required'])), $isEdit, false),
            static::order(array_filter($parameters, fn ($p) => empty($p['required'])), $isEdit, true),
        );

        $signature = [];
        $docs = [];

        foreach ($ordered as $parameter) {
            $signature[] = '$'.$parameter['name'].(empty($parameter['required']) ? ' = null' : '');
            $docs[] = '     * @param  '.static::type($parameter['type']).'  $'.$parameter['name'].'  '.static::escape($parameter['description']);
        }

        $docblock = '     * '.static::escape($method['description'] ?? '')
            .($docs ? "\n     *\n".implode("\n", $docs) : '')
            ."\n     * @return \\LaraGram\\Broadcasting\\Telegram\\TelegramBroadcast";

        $arguments = implode(', ', $signature);

        return <<<PHP
    /**
{$docblock}
     */
    public function {$name}({$arguments}): TelegramBroadcast
    {
        return \$this->endpoint('{$name}', get_defined_vars(), '{$recipient}');
    }


PHP;
    }

    /**
     * Order parameters the way Laraquest's generator does.
     *
     * @param  array  $parameters
     * @param  bool  $isEdit
     * @param  bool  $optionalGroup
     * @return array
     */
    protected static function order(array $parameters, bool $isEdit, bool $optionalGroup): array
    {
        $ranked = [];

        foreach (array_values($parameters) as $index => $parameter) {
            $ranked[] = ['parameter' => $parameter, 'rank' => static::rank($parameter['name'], $isEdit, $optionalGroup), 'index' => $index];
        }

        usort($ranked, fn ($a, $b) => $a['rank'] <=> $b['rank'] ?: $a['index'] <=> $b['index']);

        return array_column($ranked, 'parameter');
    }

    /**
     * Rank a parameter the way Laraquest's generator does.
     *
     * @param  string  $name
     * @param  bool  $isEdit
     * @param  bool  $optionalGroup
     * @return int
     */
    protected static function rank(string $name, bool $isEdit, bool $optionalGroup): int
    {
        if ($isEdit) {
            if (in_array($name, static::TEXT_FIELDS, true)) return 0;
            if ($name === 'chat_id') return 1;
            if ($optionalGroup && in_array($name, static::MESSAGE_ID_FIELDS, true)) return 2;
            if (in_array($name, static::MEDIA_FIELDS, true)) return 3;
        } else {
            if ($name === 'chat_id') return 0;
            if (in_array($name, static::TEXT_FIELDS, true)) return 1;
            if (in_array($name, static::MEDIA_FIELDS, true)) return 2;
            if ($optionalGroup && in_array($name, static::MESSAGE_ID_FIELDS, true)) return 3;
        }

        if ($optionalGroup) {
            if ($name === 'parse_mode') return 10;
            if ($name === 'reply_markup') return 11;
        }

        return 100;
    }

    /**
     * Convert a schema type into a docblock type, as Laraquest does.
     *
     * @param  string  $type
     * @return string
     */
    protected static function type(string $type): string
    {
        $hasUpdateClass = false;

        $parts = array_map(function (string $part) use (&$hasUpdateClass) {
            $base = preg_replace('/(\[\])+$/', '', $part);

            if ($base !== $part) {
                if (in_array($base, ['int', 'string', 'bool', 'float', 'true'], true)) {
                    return $part;
                }

                $hasUpdateClass = true;

                return '\\LaraGram\\Laraquest\\Updates\\'.$part;
            }

            return in_array($part, ['int', 'string', 'bool', 'float', 'true'], true) ? $part : 'array';
        }, explode('|', static::phpType($type)));

        if (in_array('int', $parts, true) || in_array('float', $parts, true)) {
            $parts[] = 'string';
        }

        if ($hasUpdateClass) {
            $parts[] = 'array';
            $parts[] = 'string';
        }

        return implode('|', array_unique($parts));
    }

    /**
     * Convert a schema type into a PHP type name.
     *
     * @param  string  $type
     * @return string
     */
    protected static function phpType(string $type): string
    {
        if (preg_match('/^Array of (.+)$/i', $type, $matches)) {
            return static::phpType(trim($matches[1])).'[]';
        }

        if (stripos($type, ' or ') !== false) {
            return implode('|', array_map([static::class, 'phpType'], array_map('trim', explode(' or ', $type))));
        }

        return ['Integer' => 'int', 'String' => 'string', 'Boolean' => 'bool', 'Float' => 'float', 'True' => 'true'][$type] ?? $type;
    }

    /**
     * Make a description safe for a docblock.
     *
     * @param  string  $description
     * @return string
     */
    protected static function escape(string $description): string
    {
        return str_replace(['*/', '/*'], ['* /', '/ *'], trim(preg_replace('/\s+/', ' ', $description)));
    }

    /**
     * Get the fingerprint of the schema file.
     *
     * @return string
     */
    protected static function fingerprint(): string
    {
        return md5(static::FORMAT.'|'.ApiSchema::path().'|'.filemtime(ApiSchema::path()).'|'.filesize(ApiSchema::path()));
    }

    /**
     * Read the fingerprint the current trait was generated from.
     *
     * @return string|null
     */
    protected static function currentFingerprint(): ?string
    {
        $head = @file_get_contents(static::path(), false, null, 0, 128);

        return is_string($head) && preg_match('/\/\/ schema: ([a-f0-9]{32})/', $head, $match) ? $match[1] : null;
    }

    /**
     * Atomically replace the trait file.
     *
     * @param  string  $contents
     * @return void
     */
    protected static function write(string $contents): void
    {
        $directory = dirname(static::path());

        if (! is_dir($directory) || ! is_writable($directory)) {
            return;
        }

        $temporary = @tempnam($directory, 'broadcast-methods');

        if ($temporary === false || @file_put_contents($temporary, $contents) === false) {
            return;
        }

        @chmod($temporary, 0644);

        if (! @rename($temporary, static::path())) {
            @unlink($temporary);

            return;
        }

        if (function_exists('opcache_invalidate')) {
            @opcache_invalidate(static::path(), true);
        }
    }
}
