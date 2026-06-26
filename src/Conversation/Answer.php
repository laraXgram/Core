<?php

namespace LaraGram\Conversation;

use LaraGram\Request\Files\FileBag;
use LaraGram\Request\Files\MediaFile;
use LaraGram\Request\Request;
use Stringable;

final class Answer implements Stringable
{
    /**
     * Telegram message field kinds that represent downloadable media.
     *
     * @var array<int, string>
     */
    private const MEDIA = [
        'photo', 'video', 'audio', 'voice', 'document',
        'animation', 'video_note', 'sticker', 'live_photo', 'paid_media',
    ];

    /**
     * @param  int|string  $key   
     * @param  string  $type  
     * @param  mixed  $value
     * @param  \LaraGram\Request\Request|null
     */
    public function __construct(
        private readonly int|string $key,
        private readonly string $type,
        private readonly mixed $value,
        private readonly ?Request $request = null,
    ) {
    }

    /**
     * Get the answer key.
     *
     * @return int|string
     */
    public function key(): int|string
    {
        return $this->key;
    }

    /**
     * Get the resolved content kind.
     *
     * @return string
     */
    public function type(): string
    {
        return $this->type;
    }

    /**
     * Get the raw, unwrapped value.
     *
     * @return mixed
     */
    public function raw(): mixed
    {
        return $this->value;
    }

    /**
     * Determine if this is a text answer.
     */
    public function isText(): bool
    {
        return $this->type === 'text';
    }

    /**
     * Determine if this is a callback answer.
     */
    public function isCallback(): bool
    {
        return $this->type === 'callback';
    }

    /**
     * Determine if this is a downloadable media answer.
     */
    public function isMedia(): bool
    {
        return in_array($this->type, self::MEDIA, true);
    }

    /**
     * Determine if this question was skipped.
     */
    public function isSkipped(): bool
    {
        return $this->value === null;
    }

    /**
     * Determine if the answer is of the given kind.
     */
    public function is(string $type): bool
    {
        return $this->type === $type;
    }

    /**
     * Get the text of a text answer (or any string-valued answer).
     *
     * @return string|null
     */
    public function text(): ?string
    {
        if ($this->isText()) {
            return $this->value;
        }

        return is_string($this->value) ? $this->value : null;
    }

    /**
     * Get the callback data of a callback answer.
     *
     * @return string|null
     */
    public function data(): ?string
    {
        return $this->isCallback() ? $this->value : null;
    }

    /**
     * Get the media files of a media answer.
     *
     * @return \LaraGram\Request\Files\FileBag|null
     */
    public function file(): ?FileBag
    {
        if (! $this->isMedia() || $this->value === null || $this->request === null) {
            return null;
        }

        return $this->request->fileFrom((object) [$this->type => $this->value]);
    }

    /**
     * Get the (largest) media file of a media answer.
     *
     * @return \LaraGram\Request\Files\MediaFile|null
     */
    public function media(): ?MediaFile
    {
        return $this->file()?->last();
    }

    /**
     * Download a media answer to the given path.
     *
     * @param  string  $path
     * @param  string|null  $disk
     * @return bool
     */
    public function download(string $path, ?string $disk = null): bool
    {
        return (bool) $this->media()?->download($path, $disk);
    }

    /**
     * Get the most natural value for the answer kind.
     *
     * @return mixed
     */
    public function value(): mixed
    {
        return match (true) {
            $this->isText()     => $this->text(),
            $this->isCallback() => $this->data(),
            $this->isMedia()    => $this->media(),
            default             => $this->value,
        };
    }

    /**
     * Render the answer as text (text/callback data, empty otherwise).
     *
     * @return string
     */
    public function __toString(): string
    {
        return (string) ($this->text() ?? $this->data() ?? '');
    }
}
