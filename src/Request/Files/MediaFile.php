<?php

namespace LaraGram\Request\Files;

use LaraGram\Support\Facades\Storage;
use RuntimeException;

class MediaFile
{
    /**
     * Cached file path retrieved from Telegram's getFile API.
     *
     * @var string|null
     */
    private ?string $cachedFilePath = null;

    /**
     * Create a new MediaFile instance.
     *
     * @param object $data
     * @param string $type
     * @param string $token
     * @param string $apiServer
     * @param static[]|null $siblings
     */
    public function __construct(
        private readonly object $data,
        private readonly string $type,
        private readonly string $token,
        private readonly string $apiServer,
        private readonly ?array $siblings = null
    ) { }

    /**
     * Get the Telegram file identifier.
     *
     * @return string
     */
    public function fileId(): string
    {
        return $this->data->file_id;
    }

    /**
     * Get the unique file identifier which is consistent across different bots.
     *
     * @return string
     */
    public function fileUniqueId(): string
    {
        return $this->data->file_unique_id;
    }

    /**
     * Get the file size in bytes, if available.
     *
     * @return int|null
     */
    public function fileSize(): ?int
    {
        return $this->data->file_size ?? null;
    }

    /**
     * Get the media type of this file.
     * Possible values: 'photo', 'video', 'document', 'sticker', 'audio',
     *                  'voice', 'video_note', 'live_photo', 'animation'.
     *
     * @return string
     */
    public function type(): string
    {
        return $this->type;
    }

    /**
     * Get the MIME type of the file, if provided by Telegram.
     * Typically available for documents, audio, video, and voice files.
     * Not available for photos or stickers.
     *
     * @return string|null
     */
    public function mimeType(): ?string
    {
        return $this->data->mime_type ?? null;
    }

    /**
     * Get the original filename as defined by the sender, if available.
     * Only present for document-type files.
     *
     * @return string|null
     */
    public function fileName(): ?string
    {
        return $this->data->file_name ?? null;
    }

    /**
     * Get the width of the media in pixels, if applicable.
     * Available for photos, videos, animations, stickers, and video notes.
     *
     * @return int|null
     */
    public function width(): ?int
    {
        return $this->data->width ?? null;
    }

    /**
     * Get the height of the media in pixels, if applicable.
     * Available for photos, videos, animations, stickers, and video notes.
     *
     * @return int|null
     */
    public function height(): ?int
    {
        return $this->data->height ?? null;
    }

    /**
     * Get the duration of the media in seconds, if applicable.
     * Available for audio, voice, video, video_note, and animation files.
     *
     * @return int|null
     */
    public function duration(): ?int
    {
        return $this->data->duration ?? null;
    }

    /**
     * Get the thumbnail object of the media, if provided by Telegram.
     * Available for videos, documents, animations, and some stickers.
     *
     * @return object|null
     */
    public function thumbnail(): ?object
    {
        return $this->data->thumbnail ?? null;
    }

    /**
     * Get the cover photo for the video, if provided by Telegram (Bot API 8.3+).
     * Only available for video files.
     *
     * @return object|null
     */
    public function cover(): ?object
    {
        return $this->data->cover ?? null;
    }

    /**
     * Get the timestamp (in seconds) from which to start playing the video (Bot API 8.3+).
     * Only available for video files.
     *
     * @return int|null
     */
    public function startTimestamp(): ?int
    {
        return $this->data->start_timestamp ?? null;
    }

    /**
     * Determine whether this file has multiple size or quality variants.
     * For photos, Telegram always provides multiple sizes.
     * For videos (Bot API 9.4+), multiple quality levels may be available.
     *
     * @return bool
     */
    public function hasSizes(): bool
    {
        return !empty($this->siblings);
    }

    /**
     * Get all available size or quality variants of this file.
     * Variants are ordered from smallest/lowest to largest/highest quality.
     *
     * @return static[]
     */
    public function sizes(): array
    {
        return $this->siblings ?? [];
    }

    /**
     * Get the smallest or lowest-quality variant of this file.
     * Falls back to the current instance if no siblings are available.
     *
     * @return static
     */
    public function smallest(): static
    {
        return $this->siblings[0] ?? $this;
    }

    /**
     * Get the largest or highest-quality variant of this file.
     * Falls back to the current instance if no siblings are available.
     *
     * @return static
     */
    public function largest(): static
    {
        return !empty($this->siblings) ? end($this->siblings) : $this;
    }

    /**
     * Get a specific size or quality variant by its index.
     * Index 0 is the smallest/lowest quality; the last index is the largest/highest.
     *
     * @param int $index
     * @return static|null
     */
    public function size(int $index): ?static
    {
        return $this->siblings[$index] ?? null;
    }

    /**
     * Determine whether this file is a photo.
     *
     * @return bool
     */
    public function isPhoto(): bool
    {
        return $this->type === 'photo';
    }

    /**
     * Determine whether this file is a video.
     *
     * @return bool
     */
    public function isVideo(): bool
    {
        return $this->type === 'video';
    }

    /**
     * Determine whether this file is a document (generic file).
     *
     * @return bool
     */
    public function isDocument(): bool
    {
        return $this->type === 'document';
    }

    /**
     * Determine whether this file is a sticker.
     *
     * @return bool
     */
    public function isSticker(): bool
    {
        return $this->type === 'sticker';
    }

    /**
     * Determine whether this file is an audio track.
     *
     * @return bool
     */
    public function isAudio(): bool
    {
        return $this->type === 'audio';
    }

    /**
     * Determine whether this file is a voice message.
     *
     * @return bool
     */
    public function isVoice(): bool
    {
        return $this->type === 'voice';
    }

    /**
     * Determine whether this file is a video note (round video message).
     *
     * @return bool
     */
    public function isVideoNote(): bool
    {
        return $this->type === 'video_note';
    }

    /**
     * Determine whether this file is a live photo.
     * A live photo contains both a still image and a short accompanying video.
     *
     * @return bool
     */
    public function isLivePhoto(): bool
    {
        return $this->type === 'live_photo';
    }

    /**
     * Determine whether this file is an animation (GIF or MPEG4 without sound).
     *
     * @return bool
     */
    public function isAnimation(): bool
    {
        return $this->type === 'animation';
    }

    /**
     * Get the raw data object as received from the Telegram update.
     * Used internally by FileBag to reconstruct MediaFile instances with siblings.
     *
     * @return object
     */
    public function raw(): object
    {
        return $this->data;
    }

    /**
     * Get the direct download URL for this file.
     *
     * @return string
     *
     * @throws RuntimeException
     */
    public function url(): string
    {
        $filePath = $this->resolveFilePath();

        if ($this->isLocalServer()) {
            return 'file://' . $filePath;
        }

        return rtrim($this->apiServer, '/') . '/file/bot' . $this->token . '/' . $filePath;
    }

    /**
     * Download the file and store it.
     *
     * @param string $path
     * @param string|null $disk
     * @return bool
     *
     * @throws RuntimeException
     */
    public function download(string $path, ?string $disk = null): bool
    {
        $filePath = $this->resolveFilePath();

        // Build fetch path directly to avoid a redundant resolveFilePath() call inside url()
        $fetchPath = $this->isLocalServer()
            ? $filePath
            : rtrim($this->apiServer, '/') . '/file/bot' . $this->token . '/' . $filePath;

        $content = file_get_contents($fetchPath);

        if ($content === false) {
            return false;
        }

        if ($disk !== null) {
            return Storage::disk($disk)->put($path, $content);
        }

        return Storage::put($path, $content);
    }

    /**
     * Resolve the Telegram file_path for this file via the getFile API.
     *
     * @return string
     *
     * @throws RuntimeException
     */
    private function resolveFilePath(): string
    {
        if ($this->cachedFilePath !== null) {
            return $this->cachedFilePath;
        }

        $response = file_get_contents(
            rtrim($this->apiServer, '/') . '/bot' . $this->token . '/getFile?file_id=' . urlencode($this->fileId())
        );

        if ($response === false) {
            throw new \RuntimeException("Unable to reach Telegram API for file_id: {$this->fileId()}");
        }

        $data = json_decode($response, true);

        if (!($data['ok'] ?? false) || empty($data['result']['file_path'])) {
            throw new \RuntimeException("Failed to resolve file path for file_id: {$this->fileId()}");
        }

        $this->cachedFilePath = $data['result']['file_path'];

        return $this->cachedFilePath;
    }

    /**
     * Determine whether the configured API server is a local Bot API server.
     *
     * @return bool
     */
    private function isLocalServer(): bool
    {
        $host = parse_url($this->apiServer, PHP_URL_HOST);

        return match (true) {
            in_array($host, ['localhost', '::1', '0:0:0:0:0:0:0:1'], true), str_starts_with($host, '127.') => true,
            default => false,
        };
    }
}
