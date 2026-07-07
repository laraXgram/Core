<?php

namespace LaraGram\Request\Files;

use LaraGram\Filesystem\Mime\MimeTypes;

class FileBag
{
    /**
     * Create a new FileBag instance.
     *
     * @param MediaFile[] $files
     * @param string|null $mediaGroupId
     * @param int|null $starCount
     */
    public function __construct(
        private readonly array   $files = [],
        private readonly ?string $mediaGroupId = null,
        private readonly ?int    $starCount = null,
    ) { }

    /**
     * Build a FileBag from a raw Telegram update message object.
     *
     * @param object $message
     * @param string $token
     * @param string $apiServer
     * @return static
     */
    public static function fromMessage(object $message, string $token, string $apiServer): static
    {
        $groupId = $message->media_group_id ?? null;

        return match (true) {
            isset($message->photo) => static::fromPhoto($message->photo, $token, $apiServer, $groupId),
            isset($message->video) => static::fromSingleWithQualities('video', $message->video, $token, $apiServer, $groupId),
            isset($message->document) => static::fromSingle('document', $message->document, $token, $apiServer, $groupId),
            isset($message->sticker) => static::fromSingle('sticker', $message->sticker, $token, $apiServer),
            isset($message->audio) => static::fromSingle('audio', $message->audio, $token, $apiServer),
            isset($message->voice) => static::fromSingle('voice', $message->voice, $token, $apiServer),
            isset($message->video_note) => static::fromSingle('video_note', $message->video_note, $token, $apiServer),
            isset($message->animation) => static::fromSingle('animation', $message->animation, $token, $apiServer),
            isset($message->live_photo) => static::fromSingle('live_photo', $message->live_photo, $token, $apiServer, $groupId),
            isset($message->paid_media) => static::fromPaidMedia($message->paid_media, $token, $apiServer),
            default => new static(),
        };
    }

    /**
     * Build a FileBag from a photo update.
     *
     * @param object[] $sizes
     * @param string $token
     * @param string $apiServer
     * @param string|null $mediaGroupId
     * @return static
     */
    private static function fromPhoto(array $sizes, string $token, string $apiServer, ?string $mediaGroupId = null): static
    {
        $files = array_map(
            fn(object $size) => new MediaFile($size, 'photo', $token, $apiServer),
            $sizes
        );

        // Pass all sizes as siblings so each MediaFile can access the full set
        $files = array_map(
            fn(MediaFile $file) => new MediaFile($file->raw(), 'photo', $token, $apiServer, $files),
            $files
        );

        return new static($files, $mediaGroupId);
    }

    /**
     * Build a FileBag from a single media object with no variants.
     *
     * Used for document, sticker, audio, voice, video_note, animation, and live_photo types.
     *
     * @param string $type
     * @param object $data
     * @param string $token
     * @param string $apiServer
     * @param string|null $mediaGroupId
     * @return static
     */
    private static function fromSingle(string $type, object $data, string $token, string $apiServer, ?string $mediaGroupId = null): static
    {
        return new static([new MediaFile($data, $type, $token, $apiServer)], $mediaGroupId);
    }

    /**
     * Build a FileBag from a media object that may have multiple quality variants.
     *
     * Used for video files where Bot API 9.4+ may provide a `qualities` array.
     * If qualities are present, each quality is wrapped as a sibling MediaFile.
     *
     * @param string $type
     * @param object $data
     * @param string $token
     * @param string $apiServer
     * @param string|null $mediaGroupId
     * @return static
     */
    private static function fromSingleWithQualities(string $type, object $data, string $token, string $apiServer, ?string $mediaGroupId = null): static
    {
        $siblings = null;

        if (!empty($data->qualities)) {
            $siblings = array_map(
                fn(object $q) => new MediaFile($q, $type, $token, $apiServer),
                $data->qualities
            );
        }

        return new static([new MediaFile($data, $type, $token, $apiServer, $siblings)], $mediaGroupId);
    }

    /**
     * Build a FileBag from a PaidMediaInfo object (Bot API 7.4+).
     *
     * Iterates the `paid_media` array and wraps each accessible item as a MediaFile.
     * `PaidMediaPreview` entries (no file_id) are skipped.
     *
     * @param object $paidMediaInfo Message::paid_media (PaidMediaInfo)
     * @param string $token
     * @param string $apiServer
     * @return static
     */
    private static function fromPaidMedia(object $paidMediaInfo, string $token, string $apiServer): static
    {
        $files = [];

        foreach ($paidMediaInfo->paid_media as $media) {
            $newFiles = match ($media->type) {
                'photo' => static::fromPhoto($media->photo, $token, $apiServer)->all(),
                'video'  => [new MediaFile($media->video, 'video', $token, $apiServer)],
                'live_photo' => [new MediaFile($media->live_photo, 'live_photo', $token, $apiServer)],
                default => [],
            };
            array_push($files, ...$newFiles);
        }

        return new static($files, null, $paidMediaInfo->star_count);
    }

    /**
     * Get the first (or only) file in the bag.
     * For photos, this is the smallest available size.
     * Returns null if the bag is empty.
     *
     * @return MediaFile|null
     */
    public function first(): ?MediaFile
    {
        return $this->files[0] ?? null;
    }

    /**
     * Get the last file in the bag.
     * For photos, this is the largest available size.
     * Returns null if the bag is empty.
     *
     * @return MediaFile|null
     */
    public function last(): ?MediaFile
    {
        $key = array_key_last($this->files);

        return $key === null ? null : $this->files[$key];
    }

    /**
     * Get a file by its index in the bag.
     *
     * @param int $index
     * @return MediaFile|null
     */
    public function get(int $index): ?MediaFile
    {
        return $this->files[$index] ?? null;
    }

    /**
     * Get all files in the bag.
     *
     * @return MediaFile[]
     */
    public function all(): array
    {
        return $this->files;
    }

    /**
     * Get the total number of files in the bag.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->files);
    }

    /**
     * Determine whether the bag contains no files.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->files);
    }

    /**
     * Determine whether the bag contains at least one file.
     *
     * @return bool
     */
    public function isNotEmpty(): bool
    {
        return !empty($this->files);
    }

    /**
     * Get the media type of the files in this bag.
     * Derived from the first file. Returns null if the bag is empty.
     *
     * @return string|null
     */
    public function type(): ?string
    {
        return $this->first()?->type();
    }

    /**
     * Get the MIME type of the first file in the bag, if available.
     *
     * @return string|null
     */
    public function mimeType(): ?string
    {
        return $this->first()?->mimeType();
    }

    /**
     * Determine whether this file belongs to a media group (album).
     *
     * @return bool
     */
    public function isAlbum(): bool
    {
        return $this->mediaGroupId !== null;
    }

    /**
     * Get the media_group_id if this file is part of an album.
     * Returns null for standalone media.
     *
     * @return string|null
     */
    public function mediaGroupId(): ?string
    {
        return $this->mediaGroupId;
    }

    /**
     * Determine whether this bag contains paid media (Bot API 7.4+).
     *
     * @return bool
     */
    public function isPaidMedia(): bool
    {
        return $this->starCount !== null;
    }

    /**
     * Download all files in the bag and store them using LaraGram's Storage system.
     *
     * @param string|string[] $pathOrDirectory
     * @param string|null $disk
     * @return bool[]
     */
    public function downloadAll(string|array $pathOrDirectory, ?string $disk = null): array
    {
        $results = [];

        foreach ($this->files as $index => $file) {
            if (is_array($pathOrDirectory)) {
                if (!isset($pathOrDirectory[$index])) {
                    break;
                }
                $path = $pathOrDirectory[$index];
            } else {
                $extension = $this->resolveExtension($file->mimeType(), $file->type());
                $path = rtrim($pathOrDirectory, '/') . '/' . $file->fileUniqueId() . '.' . $extension;
            }

            $results[$index] = $file->download($path, $disk);
        }

        return $results;
    }

    private static array $typeExtensionMap = [
        'photo' => 'jpg',
        'video' => 'mp4',
        'video_note' => 'mp4',
        'animation' => 'mp4',
        'audio' => 'mp3',
        'voice' => 'ogg',
        'sticker' => 'webp',
        'live_photo' => 'jpg',
    ];

    private function resolveExtension(?string $mimeType, string $type): string
    {
        if ($mimeType !== null) {
            $extensions = MimeTypes::getDefault()->getExtensions($mimeType);
            if (!empty($extensions)) {
                return $extensions[0];
            }
        }

        return self::$typeExtensionMap[$type] ?? 'bin';
    }
}
