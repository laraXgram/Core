<?php

namespace LaraGram\Image\Drivers;

use GdImage;
use LaraGram\Image\ImageException;

/**
 * Processes images with the GD extension.
 *
 * GD has no support for animations, so only the first frame of an animated GIF is kept.
 *
 * @extends \LaraGram\Image\Drivers\NativeDriver<\GdImage>
 */
class GdDriver extends NativeDriver
{
    /**
     * Ensure the GD extension is installed.
     */
    public function ensureRequirementsAreMet(): void
    {
        if (! extension_loaded('gd')) {
            throw new ImageException('The GD extension is required to use the [gd] image driver.');
        }
    }

    /**
     * Decode the given contents, applying their EXIF orientation.
     */
    protected function decode(string $contents): array
    {
        $image = @imagecreatefromstring($contents);

        if ($image === false) {
            throw new ImageException('Failed to decode unsupported image format from binary data.');
        }

        if (! imageistruecolor($image)) {
            imagepalettetotruecolor($image);
        }

        imagesavealpha($image, true);

        $mediaType = getimagesizefromstring($contents)['mime'] ?? 'image/png';

        return [$this->orient($image, $this->exifOrientation($contents, $mediaType)), $mediaType];
    }

    /**
     * Encode the given image into the given format.
     */
    protected function encode(object $image, string $format, int $quality): string
    {
        ob_start();

        try {
            $result = match ($format) {
                'jpg', 'jpeg' => $this->encodeJpeg($image, $quality),
                'png' => imagepng($image, null, -1),
                'webp' => imagewebp($image, null, $quality === 100 && defined('IMG_WEBP_LOSSLESS') ? IMG_WEBP_LOSSLESS : $quality),
                'avif' => function_exists('imageavif')
                    ? imageavif($image, null, $quality)
                    : throw new ImageException('The installed GD extension does not support the [avif] format.'),
                'gif' => imagegif($this->copy($image)),
                'bmp' => imagebmp($image, null, false),
                default => throw new ImageException("The [{$format}] format is not supported by the [gd] image driver."),
            };

            if ($result === false) {
                throw new ImageException("Failed to encode the image to the [{$format}] format.");
            }

            return ob_get_contents();
        } finally {
            ob_end_clean();
        }
    }

    /**
     * Encode the image as JPEG, blending transparent areas onto the background.
     */
    protected function encodeJpeg(GdImage $image, int $quality): bool
    {
        $output = $this->canvas(imagesx($image), imagesy($image), $this->parseColor(static::DEFAULT_BACKGROUND), $image);

        imagecopy($output, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));
        imageinterlace($output, false);

        return imagejpeg($output, null, $quality);
    }

    /**
     * Release the memory of the given image.
     */
    protected function destroy(object $image): void
    {
        // GD images are freed once they are no longer referenced...
    }

    /**
     * Get a copy of the image that may be modified without changing the original.
     */
    protected function duplicate(object $image): object
    {
        // Resampling GD images always creates a new image...
        return $image;
    }

    /**
     * Get the dimensions of the given image.
     */
    protected function size(object $image): array
    {
        return [imagesx($image), imagesy($image)];
    }

    /**
     * Copy the given area of the image, resampled to the given size.
     */
    protected function resample(object $image, int $x, int $y, int $width, int $height, int $toWidth, int $toHeight): object
    {
        $modified = $this->canvas($toWidth, $toHeight, [255, 255, 255, 0], $image);

        imagecopyresampled($modified, $image, 0, 0, $x, $y, $toWidth, $toHeight, $width, $height);

        return $modified;
    }

    /**
     * Fit the image into the given canvas at the given position, filling the rest with the background.
     */
    protected function place(object $image, int $canvasWidth, int $canvasHeight, int $x, int $y, int $width, int $height, array $background): object
    {
        $modified = $this->canvas($canvasWidth, $canvasHeight, $background, $image);

        // Keep the transparency of the image even when a background color is set...
        $transparent = imagecolorallocatealpha($modified, $background[0], $background[1], $background[2], 127);

        imagealphablending($modified, false);
        imagecolortransparent($modified, $transparent);
        imagefilledrectangle($modified, $x, $y, $x + $width - 1, $y + $height - 1, $transparent);

        imagealphablending($modified, true);
        imagecopyresampled($modified, $image, $x, $y, 0, 0, $width, $height, imagesx($image), imagesy($image));

        return $modified;
    }

    /**
     * Crop the given area of the image, filling areas outside of the image with the background.
     */
    protected function cropImage(object $image, int $width, int $height, int $x, int $y, array $background): object
    {
        $modified = $this->canvas($width, $height, $background, $image);

        $originalWidth = imagesx($image);
        $originalHeight = imagesy($image);

        $targetWidth = min($width, $originalWidth);
        $targetHeight = min($height, $originalHeight);
        $targetWidth = $targetWidth < $originalWidth ? $targetWidth + $x : $targetWidth;
        $targetHeight = $targetHeight < $originalHeight ? $targetHeight + $y : $targetHeight;

        imagealphablending($modified, false);
        imagecopyresampled($modified, $image, -$x, -$y, 0, 0, $targetWidth, $targetHeight, $targetWidth, $targetHeight);

        return $modified;
    }

    /**
     * Rotate the image clockwise by the given angle.
     */
    protected function rotateImage(object $image, float $angle, array $background): object
    {
        if ($angle == 0.0 && imagecolortransparent($image) === -1) {
            return $image;
        }

        $source = $this->withoutTransparentColor($image);

        $rotated = $angle == 0.0 ? $source : imagerotate($source, $angle * -1, $this->allocate($source, $background));

        if ($rotated === false) {
            throw new ImageException('Failed to rotate the image.');
        }

        $modified = $this->canvas(imagesx($rotated), imagesy($rotated), $background, $image);

        imagealphablending($modified, false);
        imagecopy($modified, $rotated, 0, 0, 0, 0, imagesx($rotated), imagesy($rotated));
        imagealphablending($modified, true);

        return $modified;
    }

    /**
     * Blur the image by applying a gaussian blur the given number of times.
     */
    protected function blurImage(object $image, int $amount): object
    {
        for ($i = 0; $i < $amount; $i++) {
            imagefilter($image, IMG_FILTER_GAUSSIAN_BLUR);
        }

        return $image;
    }

    /**
     * Convert the image to grayscale.
     */
    protected function grayscaleImage(object $image): object
    {
        imagefilter($image, IMG_FILTER_GRAYSCALE);

        return $image;
    }

    /**
     * Sharpen the image with a convolution matrix.
     */
    protected function sharpenImage(object $image, int $amount): object
    {
        $min = $amount >= 10 ? $amount * -0.01 : 0;
        $max = $amount * -0.025;
        $abs = ((4 * $min + 4 * $max) * -1) + 1;

        imageconvolution($image, [
            [$min, $max, $min],
            [$max, $abs, $max],
            [$min, $max, $min],
        ], 1, 0);

        return $image;
    }

    /**
     * Mirror the image.
     */
    protected function flipImage(object $image, bool $horizontally): object
    {
        imageflip($image, $horizontally ? IMG_FLIP_HORIZONTAL : IMG_FLIP_VERTICAL);

        return $image;
    }

    /**
     * Get the color of the given pixel as RGB values.
     */
    protected function colorAt(object $image, int $x, int $y): array
    {
        $color = imagecolorsforindex($image, imagecolorat($image, $x, $y));

        return [$color['red'], $color['green'], $color['blue']];
    }

    /**
     * Create an empty true color canvas filled with the given background.
     *
     * @param  array{0: int, 1: int, 2: int, 3: int}  $background
     */
    protected function canvas(int $width, int $height, array $background, ?GdImage $source = null): GdImage
    {
        if ($width < 1 || $height < 1) {
            throw new ImageException('Invalid image size.');
        }

        $canvas = imagecreatetruecolor($width, $height);

        if ($source && is_array($resolution = imageresolution($source))) {
            imageresolution($canvas, $resolution[0], $resolution[1]);
        }

        $color = $this->allocate($canvas, $background);

        imagealphablending($canvas, false);
        imagefilledrectangle($canvas, 0, 0, $width - 1, $height - 1, $color);
        imagealphablending($canvas, true);
        imagesavealpha($canvas, true);

        if ($background[3] === 0) {
            imagecolortransparent($canvas, $color);
        }

        return $canvas;
    }

    /**
     * Copy the given image onto a new canvas.
     */
    protected function copy(GdImage $image): GdImage
    {
        $copy = $this->canvas(imagesx($image), imagesy($image), [255, 255, 255, 0], $image);

        imagealphablending($copy, false);
        imagecopy($copy, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));

        return $copy;
    }

    /**
     * Get a true color copy of the image without a transparent color index.
     */
    protected function withoutTransparentColor(GdImage $image): GdImage
    {
        if (imageistruecolor($image) && imagecolortransparent($image) === -1) {
            return $image;
        }

        $converted = imagecreatetruecolor(imagesx($image), imagesy($image));

        imagealphablending($converted, false);
        imagesavealpha($converted, true);
        imagefill($converted, 0, 0, imagecolorallocatealpha($converted, 0, 0, 0, 127));
        imagecopy($converted, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));

        return $converted;
    }

    /**
     * Allocate the given RGBA color, converting its alpha to the range of GD (127 is transparent).
     *
     * @param  array{0: int, 1: int, 2: int, 3: int}  $color
     */
    protected function allocate(GdImage $image, array $color): int
    {
        return imagecolorallocatealpha($image, $color[0], $color[1], $color[2], 127 - (int) round($color[3] / 255 * 127));
    }
}
