<?php

namespace LaraGram\Image\Drivers;

use finfo;
use LaraGram\Contracts\Image\Driver;
use LaraGram\Image\ImageException;
use LaraGram\Image\ImageOutputOptions;
use LaraGram\Image\ImagePipeline;
use LaraGram\Image\Transformations\Blur;
use LaraGram\Image\Transformations\Contain;
use LaraGram\Image\Transformations\Cover;
use LaraGram\Image\Transformations\Crop;
use LaraGram\Image\Transformations\FlipHorizontally;
use LaraGram\Image\Transformations\FlipVertically;
use LaraGram\Image\Transformations\Grayscale;
use LaraGram\Image\Transformations\Orient;
use LaraGram\Image\Transformations\Resize;
use LaraGram\Image\Transformations\Rotate;
use LaraGram\Image\Transformations\Scale;
use LaraGram\Image\Transformations\Sharpen;

/**
 * Processes images with a native PHP image extension.
 *
 * The geometry and the operations follow Intervention Image, so images are processed the same way.
 *
 * @template TImage of object
 */
abstract class NativeDriver implements Driver
{
    /**
     * The media types the driver can decode.
     */
    const MEDIA_TYPES = [
        'image/jpeg', 'image/png', 'image/bmp', 'image/gif', 'image/webp',
        'image/avif', 'image/x-avif', 'image/heic', 'image/x-heic', 'image/heif',
    ];

    /**
     * The background color used when none is given.
     */
    const DEFAULT_BACKGROUND = 'ffffff';

    /**
     * The registered transformation handlers.
     *
     * @var array<class-string<\LaraGram\Contracts\Image\Transformation>, callable>
     */
    protected array $transformationHandlers = [];

    /**
     * Ensure the image extension of the driver is installed.
     *
     * @throws \LaraGram\Image\ImageException
     */
    abstract public function ensureRequirementsAreMet(): void;

    /**
     * Decode the given contents, applying their EXIF orientation.
     *
     * @return array{0: TImage, 1: string} The image and its media type.
     */
    abstract protected function decode(string $contents): array;

    /**
     * Encode the given image into the given format.
     *
     * @param  TImage  $image
     */
    abstract protected function encode(object $image, string $format, int $quality): string;

    /**
     * Release the memory of the given image.
     *
     * @param  TImage  $image
     */
    abstract protected function destroy(object $image): void;

    /**
     * Get a copy of the image that may be modified without changing the original.
     *
     * @param  TImage  $image
     * @return TImage
     */
    abstract protected function duplicate(object $image): object;

    /**
     * Get the dimensions of the given image.
     *
     * @param  TImage  $image
     * @return array{0: int, 1: int}
     */
    abstract protected function size(object $image): array;

    /**
     * Copy the given area of the image, resampled to the given size.
     *
     * @param  TImage  $image
     * @return TImage
     */
    abstract protected function resample(object $image, int $x, int $y, int $width, int $height, int $toWidth, int $toHeight): object;

    /**
     * Fit the image into the given canvas at the given position, filling the rest with the background.
     *
     * @param  TImage  $image
     * @param  array{0: int, 1: int, 2: int, 3: int}  $background
     * @return TImage
     */
    abstract protected function place(object $image, int $canvasWidth, int $canvasHeight, int $x, int $y, int $width, int $height, array $background): object;

    /**
     * Crop the given area of the image, filling areas outside of the image with the background.
     *
     * @param  TImage  $image
     * @param  array{0: int, 1: int, 2: int, 3: int}  $background
     * @return TImage
     */
    abstract protected function cropImage(object $image, int $width, int $height, int $x, int $y, array $background): object;

    /**
     * Rotate the image clockwise by the given angle.
     *
     * @param  TImage  $image
     * @param  array{0: int, 1: int, 2: int, 3: int}  $background
     * @return TImage
     */
    abstract protected function rotateImage(object $image, float $angle, array $background): object;

    /**
     * @param  TImage  $image
     * @return TImage
     */
    abstract protected function blurImage(object $image, int $amount): object;

    /**
     * @param  TImage  $image
     * @return TImage
     */
    abstract protected function grayscaleImage(object $image): object;

    /**
     * @param  TImage  $image
     * @return TImage
     */
    abstract protected function sharpenImage(object $image, int $amount): object;

    /**
     * @param  TImage  $image
     * @return TImage
     */
    abstract protected function flipImage(object $image, bool $horizontally): object;

    /**
     * Get the color of the given pixel as RGB values.
     *
     * @param  TImage  $image
     * @return array{0: int, 1: int, 2: int}
     */
    abstract protected function colorAt(object $image, int $x, int $y): array;

    /**
     * Process the given image contents with the specified pipeline.
     */
    public function process(string $contents, ImagePipeline $pipeline): string
    {
        $mimeType = (new finfo(FILEINFO_MIME_TYPE))->buffer($contents);

        if (! in_array($mimeType, static::MEDIA_TYPES)) {
            throw new ImageException("The image format [{$mimeType}] is not supported.");
        }

        [$image, $mediaType] = $this->decode($contents);

        try {
            foreach ($pipeline->transformations as $transformation) {
                $image = ($handler = $this->transformationHandlerFor($transformation))
                    ? $handler($image, $transformation)
                    : $this->transform($image, $transformation);
            }

            return $this->encode(
                $image,
                $pipeline->output->format ?? $this->formatOf($mediaType),
                $pipeline->output->quality ?? ImageOutputOptions::DEFAULT_QUALITY,
            );
        } finally {
            $this->destroy($image);
        }
    }

    /**
     * Apply the given built-in transformation to the image.
     *
     * @param  TImage  $image
     * @return TImage
     */
    protected function transform(object $image, object $transformation): object
    {
        [$width, $height] = $this->size($image);

        return match (true) {
            // Images are oriented while they are decoded...
            $transformation instanceof Orient => $image,
            $transformation instanceof Cover => $this->cover($image, $width, $height, $transformation->width, $transformation->height),
            $transformation instanceof Contain => $this->contain(
                $image, $width, $height, $transformation->width, $transformation->height, $this->resolveBackground($image, $transformation->background),
            ),
            $transformation instanceof Crop => $this->cropImage(
                $image, $transformation->width, $transformation->height, $transformation->x, $transformation->y, $this->parseColor(static::DEFAULT_BACKGROUND),
            ),
            $transformation instanceof Resize => $this->resizeTo($image, $width, $height, $transformation->width ?? $width, $transformation->height ?? $height),
            $transformation instanceof Scale => $this->resizeTo($image, $width, $height, ...$this->scaleDownSize($width, $height, $transformation->width, $transformation->height)),
            $transformation instanceof Rotate => $this->rotateImage($image, fmod($transformation->angle, 360), $this->resolveBackground($image, $transformation->background)),
            $transformation instanceof Blur => $this->blurImage($image, $transformation->amount),
            $transformation instanceof Grayscale => $this->grayscaleImage($image),
            $transformation instanceof Sharpen => $this->sharpenImage($image, $transformation->amount),
            $transformation instanceof FlipVertically => $this->flipImage($image, false),
            $transformation instanceof FlipHorizontally => $this->flipImage($image, true),
            default => throw new ImageException('The image transformation ['.get_class($transformation).'] is not supported.'),
        };
    }

    /**
     * Crop the largest centered area of the target ratio and resize it to the target size.
     *
     * @param  TImage  $image
     * @return TImage
     */
    protected function cover(object $image, int $width, int $height, int $toWidth, int $toHeight): object
    {
        [$cropWidth, $cropHeight] = $this->containSize($toWidth, $toHeight, $width, $height);

        return $this->resample(
            $image,
            $this->centerOffset($width, $cropWidth),
            $this->centerOffset($height, $cropHeight),
            $cropWidth,
            $cropHeight,
            $toWidth,
            $toHeight,
        );
    }

    /**
     * Fit the whole image into the target size, centered on the background.
     *
     * @param  TImage  $image
     * @param  array{0: int, 1: int, 2: int, 3: int}  $background
     * @return TImage
     */
    protected function contain(object $image, int $width, int $height, int $toWidth, int $toHeight, array $background): object
    {
        [$fitWidth, $fitHeight] = $this->containSize($width, $height, $toWidth, $toHeight);

        return $this->place(
            $image,
            $toWidth,
            $toHeight,
            $this->centerOffset($toWidth, $fitWidth),
            $this->centerOffset($toHeight, $fitHeight),
            $fitWidth,
            $fitHeight,
            $background,
        );
    }

    /**
     * Resize the whole image to the given size.
     *
     * @param  TImage  $image
     * @return TImage
     */
    protected function resizeTo(object $image, int $width, int $height, int $toWidth, int $toHeight): object
    {
        return $this->resample($image, 0, 0, $width, $height, $toWidth, $toHeight);
    }

    /**
     * Get the size of the given size proportionally fitted into the target size.
     *
     * @return array{0: int, 1: int}
     */
    protected function containSize(int $width, int $height, int $toWidth, int $toHeight): array
    {
        $ratio = $width / $height;

        $fitHeight = max(1, (int) round($toWidth / $ratio));

        if ($fitHeight <= $toHeight) {
            return [$toWidth, $fitHeight];
        }

        return [max(1, (int) round($toHeight * $ratio)), $toHeight];
    }

    /**
     * Get the size of the given size proportionally scaled down to fit the target size, never upscaling.
     *
     * @return array{0: int, 1: int}
     */
    protected function scaleDownSize(int $width, int $height, ?int $toWidth, ?int $toHeight): array
    {
        $ratio = $width / $height;

        $proportionalWidth = $toHeight !== null ? max(1, (int) round($toHeight * $ratio)) : $width;
        $proportionalHeight = $toWidth !== null ? max(1, (int) round($toWidth / $ratio)) : $height;

        return match (true) {
            $toWidth !== null && $toHeight !== null => [
                min($proportionalWidth, $toWidth, $width),
                min($proportionalHeight, $toHeight, $height),
            ],
            $toWidth !== null => [min($toWidth, $width), min($proportionalHeight, $height)],
            $toHeight !== null => [min($proportionalWidth, $width), min($toHeight, $height)],
            default => [$width, $height],
        };
    }

    /**
     * Get the offset that centers the given length within the container length.
     */
    protected function centerOffset(int $container, int $length): int
    {
        return (int) round($container / 2) - (int) round($length / 2);
    }

    /**
     * Get the format of the given media type.
     */
    protected function formatOf(string $mediaType): string
    {
        return match ($mediaType) {
            'image/jpeg', 'image/jpg', 'image/pjpeg' => 'jpg',
            'image/png', 'image/x-png' => 'png',
            'image/gif', 'image/x-gif' => 'gif',
            'image/webp', 'image/x-webp' => 'webp',
            'image/avif', 'image/x-avif' => 'avif',
            'image/heic', 'image/x-heic', 'image/heif', 'image/x-heif' => 'heic',
            'image/bmp', 'image/x-bmp', 'image/x-ms-bmp', 'image/ms-bmp' => 'bmp',
            default => throw new ImageException("The image format [{$mediaType}] is not supported."),
        };
    }

    /**
     * Get the dimensions of the given image contents.
     *
     * @return array{0: int, 1: int}
     */
    public function dimensions(string $contents): array
    {
        [$image] = $this->decode($contents);

        try {
            return $this->size($image);
        } finally {
            $this->destroy($image);
        }
    }

    /**
     * Get the dominant (average) color of the image as a hex string.
     */
    public function dominantColor(string $contents): string
    {
        [$image] = $this->decode($contents);

        try {
            return $this->dominantColorFrom($image);
        } finally {
            $this->destroy($image);
        }
    }

    /**
     * Sample the dominant color by resizing the image to a single pixel.
     *
     * @param  TImage  $image
     */
    protected function dominantColorFrom(object $image): string
    {
        [$width, $height] = $this->size($image);

        $sample = $this->resample($this->duplicate($image), 0, 0, $width, $height, 1, 1);

        try {
            return vsprintf('#%02x%02x%02x', $this->colorAt($sample, 0, 0));
        } finally {
            if ($sample !== $image) {
                $this->destroy($sample);
            }
        }
    }

    /**
     * Resolve a background color, expanding the "dominant" sentinel when needed.
     *
     * @param  TImage  $image
     * @return array{0: int, 1: int, 2: int, 3: int}
     */
    protected function resolveBackground(object $image, ?string $background): array
    {
        return $this->parseColor(match ($background) {
            'dominant' => $this->dominantColorFrom($image),
            null => static::DEFAULT_BACKGROUND,
            default => $background,
        });
    }

    /**
     * Parse the given color into RGBA values, with the alpha between 0 (transparent) and 255 (opaque).
     *
     * Supports hex colors ("#fff", "#ffffff", "#ffffff80"), rgb() / rgba() and "transparent".
     *
     * @return array{0: int, 1: int, 2: int, 3: int}
     *
     * @throws \LaraGram\Image\ImageException
     */
    protected function parseColor(string $color): array
    {
        $color = strtolower(trim($color));

        if ($color === 'transparent') {
            return [255, 255, 255, 0];
        }

        if (preg_match('/^rgba?\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*(?:,\s*([\d.]+%?)\s*)?\)$/', $color, $matches)) {
            $alpha = $matches[4] ?? '1';

            $alpha = str_ends_with($alpha, '%') ? (float) $alpha / 100 : (float) $alpha;

            return [
                min(255, (int) $matches[1]),
                min(255, (int) $matches[2]),
                min(255, (int) $matches[3]),
                (int) round(max(0, min(1, $alpha)) * 255),
            ];
        }

        $hex = ltrim($color, '#');

        if (preg_match('/^[0-9a-f]{3,4}$/', $hex)) {
            $hex = implode('', array_map(fn ($digit) => $digit.$digit, str_split($hex)));
        }

        if (! preg_match('/^[0-9a-f]{6}([0-9a-f]{2})?$/', $hex)) {
            throw new ImageException("The color [{$color}] is not supported.");
        }

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
            strlen($hex) === 8 ? hexdec(substr($hex, 6, 2)) : 255,
        ];
    }

    /**
     * Get the EXIF orientation of the given JPEG contents.
     */
    protected function exifOrientation(string $contents, string $mediaType): int
    {
        if ($mediaType !== 'image/jpeg' || ! function_exists('exif_read_data')) {
            return 0;
        }

        $stream = fopen('php://memory', 'r+');

        fwrite($stream, $contents);
        rewind($stream);

        try {
            $data = @exif_read_data($stream, null, true);
        } finally {
            fclose($stream);
        }

        $orientation = is_array($data) ? ($data['IFD0']['Orientation'] ?? 0) : 0;

        return is_numeric($orientation) ? (int) $orientation : 0;
    }

    /**
     * Rotate and flip the image according to the given EXIF orientation.
     *
     * @param  TImage  $image
     * @return TImage
     */
    protected function orient(object $image, int $orientation): object
    {
        $background = $this->parseColor(static::DEFAULT_BACKGROUND);

        return match ($orientation) {
            2 => $this->flipImage($image, true),
            3 => $this->rotateImage($image, 180, $background),
            4 => $this->flipImage($this->rotateImage($image, 180, $background), true),
            5 => $this->flipImage($this->rotateImage($image, 90, $background), true),
            6 => $this->rotateImage($image, 90, $background),
            7 => $this->flipImage($this->rotateImage($image, 270, $background), true),
            8 => $this->rotateImage($image, 270, $background),
            default => $image,
        };
    }

    /**
     * Register a transformation handler.
     *
     * The handler receives the native image of the driver and must return it.
     *
     * @param  class-string<\LaraGram\Contracts\Image\Transformation>  $transformation
     */
    public function transformUsing(string $transformation, callable $callback): static
    {
        $this->transformationHandlers[$transformation] = $callback;

        return $this;
    }

    /**
     * Get the handler for the given transformation.
     */
    protected function transformationHandlerFor(object $transformation): ?callable
    {
        foreach ($this->transformationHandlers as $class => $handler) {
            if ($transformation instanceof $class) {
                return $handler;
            }
        }

        return null;
    }
}
