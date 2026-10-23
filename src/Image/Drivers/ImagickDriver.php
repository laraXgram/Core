<?php

namespace LaraGram\Image\Drivers;

use Imagick;
use ImagickDraw;
use ImagickException;
use ImagickPixel;
use LaraGram\Image\ImageException;

/**
 * Processes images with the Imagick extension, keeping the frames of animated images.
 *
 * @extends \LaraGram\Image\Drivers\NativeDriver<\Imagick>
 */
class ImagickDriver extends NativeDriver
{
    /**
     * Ensure the Imagick extension is installed.
     */
    public function ensureRequirementsAreMet(): void
    {
        if (! extension_loaded('imagick')) {
            throw new ImageException('The Imagick extension is required to use the [imagick] image driver.');
        }
    }

    /**
     * Decode the given contents, applying their EXIF orientation.
     */
    protected function decode(string $contents): array
    {
        try {
            $image = new Imagick;
            $image->readImageBlob($contents);

            $mediaType = $image->getImageMimeType();

            if ($image->getImageFormat() !== 'JPEG') {
                $image = $this->requiresCoalescing($image) ? $image->coalesceImages() : $this->resetVirtualCanvas($image);
            }

            if ($image->getImageColorspace() === Imagick::COLORSPACE_GRAY) {
                $image->setImageColorspace(Imagick::COLORSPACE_SRGB);

                if (substr($image->getImageProfiles('icc')['icc'] ?? '', 16, 4) === 'GRAY') {
                    $image->removeImageProfile('icc');
                }
            }

            if ($image->getImageColorspace() === Imagick::COLORSPACE_YCBCR) {
                $image->transformImageColorspace(Imagick::COLORSPACE_SRGB);
            }

            return [$this->orientImage($image), $mediaType];
        } catch (ImagickException $e) {
            throw new ImageException('Failed to decode unsupported image format from binary data.', previous: $e);
        }
    }

    /**
     * Rotate and flip every frame according to the EXIF orientation of the image.
     */
    protected function orientImage(Imagick $image): Imagick
    {
        $image->setFirstIterator();

        $orientation = $image->getImageOrientation();

        return $this->eachFrame($image, function (Imagick $frame) use ($orientation) {
            match ($orientation) {
                Imagick::ORIENTATION_TOPRIGHT => $frame->flopImage(),
                Imagick::ORIENTATION_BOTTOMRIGHT => $frame->rotateImage('#000', 180),
                Imagick::ORIENTATION_BOTTOMLEFT => $frame->rotateImage('#000', 180) && $frame->flopImage(),
                Imagick::ORIENTATION_LEFTTOP => $frame->rotateImage('#000', 90) && $frame->flopImage(),
                Imagick::ORIENTATION_RIGHTTOP => $frame->rotateImage('#000', 90),
                Imagick::ORIENTATION_RIGHTBOTTOM => $frame->rotateImage('#000', 270) && $frame->flopImage(),
                Imagick::ORIENTATION_LEFTBOTTOM => $frame->rotateImage('#000', 270),
                default => true,
            };

            $frame->setImageOrientation(Imagick::ORIENTATION_TOPLEFT);
        });
    }

    /**
     * Encode the given image into the given format.
     */
    protected function encode(object $image, string $format, int $quality): string
    {
        try {
            $output = clone $image;

            try {
                match ($format) {
                    'jpg', 'jpeg' => $this->prepareJpeg($output, $quality),
                    'png' => $this->prepare($output, 'PNG32', Imagick::COMPRESSION_ZIP),
                    'webp' => $output = $this->prepareWebp($output, $quality),
                    'avif' => $this->prepare($output, 'AVIF', Imagick::COMPRESSION_ZIP, $quality),
                    'heic' => $this->prepare($output, 'HEIC', null, $quality),
                    'gif' => $this->prepare($output, 'GIF', Imagick::COMPRESSION_LZW),
                    'bmp' => $this->prepare($output, 'BMP', Imagick::COMPRESSION_NO),
                    default => throw new ImageException("The [{$format}] format is not supported by the [imagick] image driver."),
                };

                return $output->getImagesBlob();
            } finally {
                $output->clear();
            }
        } catch (ImagickException $e) {
            throw new ImageException("Failed to encode the image to the [{$format}] format.", previous: $e);
        }
    }

    /**
     * Prepare the image to be encoded in the given format.
     */
    protected function prepare(Imagick $image, string $format, ?int $compression, ?int $quality = null): void
    {
        $image->setFormat($format);
        $image->setImageFormat($format);

        if ($compression !== null) {
            $image->setCompression($compression);
            $image->setImageCompression($compression);
        }

        if ($quality !== null) {
            $image->setCompressionQuality($quality);
            $image->setImageCompressionQuality($quality);
        }
    }

    /**
     * Prepare the image to be encoded as JPEG, flattening transparent areas onto the background.
     */
    protected function prepareJpeg(Imagick $image, int $quality): void
    {
        $background = $this->pixel($this->parseColor(static::DEFAULT_BACKGROUND));

        $image->setImageBackgroundColor($background);
        $image->setBackgroundColor($background);

        $this->prepare($image, 'JPEG', Imagick::COMPRESSION_JPEG, $quality);

        $image->setImageAlphaChannel(Imagick::ALPHACHANNEL_REMOVE);
    }

    /**
     * Prepare the image to be encoded as WebP, merging the layers of still images.
     */
    protected function prepareWebp(Imagick $image, int $quality): Imagick
    {
        $image->setImageBackgroundColor(new ImagickPixel('transparent'));

        if ($image->getNumberImages() === 1) {
            $merged = $image->mergeImageLayers(Imagick::LAYERMETHOD_MERGE);

            $image->clear();

            $image = $merged;
        }

        $this->prepare($image, 'WEBP', Imagick::COMPRESSION_ZIP);

        $image->setImageCompressionQuality($quality);

        if ($quality === 100) {
            $image->setOption('webp:lossless', 'true');
        }

        return $image;
    }

    /**
     * Release the memory of the given image.
     */
    protected function destroy(object $image): void
    {
        $image->clear();
    }

    /**
     * Get a copy of the image that may be modified without changing the original.
     */
    protected function duplicate(object $image): object
    {
        return clone $image;
    }

    /**
     * Get the dimensions of the given image.
     */
    protected function size(object $image): array
    {
        $image->setFirstIterator();

        return [$image->getImageWidth(), $image->getImageHeight()];
    }

    /**
     * Copy the given area of the image, resampled to the given size.
     */
    protected function resample(object $image, int $x, int $y, int $width, int $height, int $toWidth, int $toHeight): object
    {
        [$originalWidth, $originalHeight] = $this->size($image);

        $cropped = $x !== 0 || $y !== 0 || $width !== $originalWidth || $height !== $originalHeight;

        return $this->eachFrame($image, function (Imagick $frame) use ($cropped, $x, $y, $width, $height, $toWidth, $toHeight) {
            if ($cropped) {
                $frame->cropImage($width, $height, $x, $y);
            }

            $frame->scaleImage($toWidth, $toHeight);
            $frame->setImagePage(0, 0, 0, 0);
        });
    }

    /**
     * Fit the image into the given canvas at the given position, filling the rest with the background.
     */
    protected function place(object $image, int $canvasWidth, int $canvasHeight, int $x, int $y, int $width, int $height, array $background): object
    {
        $fill = $this->pixel($background);

        return $this->eachFrame($image, function (Imagick $frame) use ($canvasWidth, $canvasHeight, $x, $y, $width, $height, $fill) {
            $frame->scaleImage($width, $height);

            $transparent = new ImagickPixel('transparent');

            $frame->setBackgroundColor($transparent);
            $frame->setImageBackgroundColor($transparent);
            $frame->extentImage($canvasWidth, $canvasHeight, -$x, -$y);

            $draw = new ImagickDraw;
            $draw->setFillColor($fill);

            if ($canvasWidth > $width) {
                if ($x > 0) {
                    $draw->rectangle(0, 0, $x - 1, $canvasHeight);
                }

                $draw->rectangle($width + $x, 0, $canvasWidth, $canvasHeight);
            }

            if ($canvasHeight > $height) {
                if ($y > 0) {
                    $draw->rectangle(0, 0, $canvasWidth, $y - 1);
                }

                $draw->rectangle(0, $height + $y, $canvasWidth, $canvasHeight);
            }

            $frame->drawImage($draw);
        });
    }

    /**
     * Crop the given area of the image, filling areas outside of the image with the background.
     */
    protected function cropImage(object $image, int $width, int $height, int $x, int $y, array $background): object
    {
        $fill = $this->pixel($background);
        $cropped = new Imagick;

        foreach ($image as $frame) {
            $canvas = new Imagick;
            $canvas->newImage($width, $height, $fill, 'png');

            $resolution = $frame->getImageResolution();

            $canvas->setImageResolution($resolution['x'], $resolution['y']);
            $canvas->setImageUnits($frame->getImageUnits());
            $canvas->setImageAlphaChannel(Imagick::ALPHACHANNEL_SET);
            $canvas->setImageColorspace(Imagick::COLORSPACE_SRGB);

            if ($image->getNumberImages() > 1) {
                $canvas->setImageDelay($frame->getImageDelay());
                $canvas->setImageIterations($frame->getImageIterations());
                $canvas->setImageDispose($frame->getImageDispose());
            }

            $clearer = new Imagick;
            $clearer->newImage($frame->getImageWidth(), $frame->getImageHeight(), new ImagickPixel('black'));

            $canvas->compositeImage($clearer, Imagick::COMPOSITE_DSTOUT, -$x, -$y);
            $clearer->clear();

            $canvas->compositeImage($frame, Imagick::COMPOSITE_DEFAULT, -$x, -$y);

            $cropped->addImage($canvas);
            $canvas->clear();
        }

        $image->clear();

        return $cropped;
    }

    /**
     * Rotate the image clockwise by the given angle.
     */
    protected function rotateImage(object $image, float $angle, array $background): object
    {
        $fill = $this->pixel($background);

        return $this->eachFrame($image, function (Imagick $frame) use ($angle, $fill) {
            $angle == 0.0 ? $frame->setImageBackgroundColor($fill) : $frame->rotateImage($fill, $angle);

            $frame->setImagePage(0, 0, 0, 0);
        });
    }

    /**
     * Blur the image.
     */
    protected function blurImage(object $image, int $amount): object
    {
        return $this->eachFrame($image, fn (Imagick $frame) => $frame->blurImage($amount, 0.5 * $amount));
    }

    /**
     * Convert the image to grayscale, keeping its colorspace.
     */
    protected function grayscaleImage(object $image): object
    {
        return $this->eachFrame($image, function (Imagick $frame) {
            $colorspace = $frame->getImageColorspace();

            $frame->transformImageColorspace(Imagick::COLORSPACE_GRAY);
            $frame->transformImageColorspace($colorspace);
        });
    }

    /**
     * Sharpen the image with an unsharp mask.
     */
    protected function sharpenImage(object $image, int $amount): object
    {
        return $this->eachFrame($image, fn (Imagick $frame) => $frame->unsharpMaskImage(1, 1, $amount / 6.25, 0));
    }

    /**
     * Mirror the image.
     */
    protected function flipImage(object $image, bool $horizontally): object
    {
        return $this->eachFrame($image, fn (Imagick $frame) => $horizontally ? $frame->flopImage() : $frame->flipImage());
    }

    /**
     * Get the color of the given pixel as RGB values.
     */
    protected function colorAt(object $image, int $x, int $y): array
    {
        $image->setFirstIterator();

        $color = $image->getImagePixelColor($x, $y)->getColor();

        return [$color['r'], $color['g'], $color['b']];
    }

    /**
     * Apply the given callback to every frame of the image.
     *
     * @param  callable(\Imagick): mixed  $callback
     */
    protected function eachFrame(Imagick $image, callable $callback): Imagick
    {
        try {
            foreach ($image as $frame) {
                $callback($frame);
            }

            $image->setFirstIterator();

            return $image;
        } catch (ImagickException $e) {
            throw new ImageException('Failed to process the image: '.$e->getMessage(), previous: $e);
        }
    }

    /**
     * Create a pixel of the given RGBA color.
     *
     * @param  array{0: int, 1: int, 2: int, 3: int}  $color
     */
    protected function pixel(array $color): ImagickPixel
    {
        return new ImagickPixel(sprintf('rgba(%d, %d, %d, %s)', $color[0], $color[1], $color[2], round($color[3] / 255, 4)));
    }

    /**
     * Determine if the frames of the image must be coalesced into full frames.
     */
    protected function requiresCoalescing(Imagick $image): bool
    {
        if ($image->getNumberImages() !== 1
            || $image->getImageColorspace() !== Imagick::COLORSPACE_SRGB
            || ! in_array($image->getImageType(), [Imagick::IMGTYPE_TRUECOLOR, Imagick::IMGTYPE_TRUECOLORMATTE], true)) {
            return true;
        }

        $page = $image->getImagePage();

        return $page['x'] !== 0
            || $page['y'] !== 0
            || ! in_array($page['width'], [0, $image->getImageWidth()], true)
            || ! in_array($page['height'], [0, $image->getImageHeight()], true);
    }

    /**
     * Reset the virtual canvas of the image.
     */
    protected function resetVirtualCanvas(Imagick $image): Imagick
    {
        $image->setImagePage($image->getImageWidth(), $image->getImageHeight(), 0, 0);
        $image->setImageDispose(Imagick::DISPOSE_NONE);

        $background = $image->getImageBackgroundColor();
        $background->setColorValue(Imagick::COLOR_ALPHA, 0);

        $image->setImageBackgroundColor($background);

        return $image;
    }
}
