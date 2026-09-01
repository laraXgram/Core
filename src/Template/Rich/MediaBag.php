<?php

namespace LaraGram\Template\Rich;

use LaraGram\Template\Rich\Exceptions\RichMessageException;
use LaraGram\Template\Rich\Exceptions\RichMessageLimitException;

/**
 * Collects the media referenced by a rich message.
 *
 * @see https://core.telegram.org/bots/api#inputrichmessagemedia
 */
class MediaBag
{
    /**
     * The kinds of media a rich message can carry, mapped to the tg:// scheme
     * used to reference them and the HTML tag that renders them.
     *
     * @var array<string, array{scheme: string, tag: string}>
     */
    public const TYPES = [
        'photo'      => ['scheme' => 'photo',    'tag' => 'img'],
        'video'      => ['scheme' => 'video',    'tag' => 'video'],
        'animation'  => ['scheme' => 'video',    'tag' => 'video'],
        'audio'      => ['scheme' => 'audio',    'tag' => 'audio'],
        'voice_note' => ['scheme' => 'audio',    'tag' => 'audio'],
        'document'   => ['scheme' => 'document', 'tag' => 'tg-document'],
    ];

    /**
     * The registered media, keyed by identifier.
     *
     * @var array<string, array<string, mixed>>
     */
    protected array $items = [];

    /**
     * Counter backing automatically generated identifiers.
     *
     * @var int
     */
    protected int $sequence = 0;

    /**
     * Register a file and get back the tg:// link that references it.
     *
     * @param  mixed  $file  A file_id, an HTTP URL, or an "attach://name" reference.
     * @param  string  $type  One of the keys of {@see self::TYPES}.
     * @param  string|null  $id  An explicit identifier, or null to generate one.
     * @param  array<string, mixed>  $options  Extra InputMedia fields (width, duration, ...).
     * @return string
     */
    public function add(mixed $file, string $type, ?string $id = null, array $options = []): string
    {
        if (! isset(self::TYPES[$type])) {
            throw new RichMessageException(
                "Unknown rich media type [{$type}]. Expected one of: "
                .implode(', ', array_keys(self::TYPES)).'.'
            );
        }

        $id = $id === null ? $this->nextId() : $this->validateId($id);

        if (isset($this->items[$id])) {
            throw new RichMessageException(
                "Rich media id [{$id}] is already used in this message."
            );
        }

        if (count($this->items) >= Limits::MEDIA) {
            throw RichMessageLimitException::exceeded('media', count($this->items) + 1, Limits::MEDIA);
        }

        $this->items[$id] = [
            'id' => $id,
            'media' => array_merge($options, [
                'type' => $type,
                'media' => $file,
            ]),
        ];

        return $this->link($id, $type);
    }

    /**
     * Build the tg:// link that references a registered identifier.
     *
     * @param  string  $id
     * @param  string  $type
     * @return string
     */
    public function link(string $id, string $type): string
    {
        return 'tg://'.self::TYPES[$type]['scheme'].'?id='.$id;
    }

    /**
     * Get the HTML tag that renders a given media type.
     *
     * @param  string  $type
     * @return string
     */
    public static function tagFor(string $type): string
    {
        return self::TYPES[$type]['tag'] ?? 'img';
    }

    /**
     * Get the registered media as the message's `media` array.
     *
     * @return array<int, array<string, mixed>>
     */
    public function toArray(): array
    {
        return array_values($this->items);
    }

    /**
     * Determine whether any media has been registered.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    /**
     * Count the registered media.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->items);
    }

    /**
     * Generate the next unused automatic identifier.
     *
     * @return string
     */
    protected function nextId(): string
    {
        do {
            $id = 'm'.(++$this->sequence);
        } while (isset($this->items[$id]));

        return $id;
    }

    /**
     * Make sure a caller supplied identifier is one Telegram accepts.
     *
     * @param  string  $id
     * @return string
     */
    protected function validateId(string $id): string
    {
        if (! preg_match('/^[A-Za-z0-9_-]{1,'.Limits::MEDIA_ID_LENGTH.'}$/', $id)) {
            throw new RichMessageException(
                "Rich media id [{$id}] is invalid: use 1-".Limits::MEDIA_ID_LENGTH
                .' characters from A-Z, a-z, 0-9, _ and - only.'
            );
        }

        return $id;
    }
}
