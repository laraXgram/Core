<?php

namespace LaraGram\Support\Facades;

/**
 * @method static \LaraGram\Image\Image fromBytes(string $contents)
 * @method static \LaraGram\Image\Image fromStream(resource $stream)
 * @method static \LaraGram\Image\Image fromBase64(string $base64)
 * @method static \LaraGram\Image\Image fromPath(string $path)
 * @method static \LaraGram\Image\Image fromStorage(string $path, \BackedEnum|string|null $disk = null)
 * @method static \LaraGram\Image\Image fromUpload(\LaraGram\Http\UploadedFile $file)
 * @method static \LaraGram\Image\Image fromUrl(string $url)
 * @method static \LaraGram\Image\ImageManager transformUsing(string $driver, string $transformation, callable $callback)
 * @method static string getDefaultDriver()
 * @method static mixed driver(\UnitEnum|string|null $driver = null)
 * @method static \LaraGram\Image\ImageManager extend(string $driver, \Closure $callback)
 * @method static array getDrivers()
 * @method static \LaraGram\Contracts\Container\Container getContainer()
 * @method static \LaraGram\Image\ImageManager setContainer(\LaraGram\Contracts\Container\Container $container)
 * @method static \LaraGram\Image\ImageManager forgetDrivers()
 *
 * @see \LaraGram\Image\ImageManager
 */
class Image extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'image';
    }
}
