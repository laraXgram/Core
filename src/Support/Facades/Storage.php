<?php

namespace LaraGram\Support\Facades;

/**
 * @method static \LaraGram\Contracts\Filesystem\Filesystem drive(\UnitEnum|string|null $name = null)
 * @method static \LaraGram\Contracts\Filesystem\Filesystem disk(\UnitEnum|string|null $name = null)
 * @method static \LaraGram\Contracts\Filesystem\Filesystem build(string|array $config)
 * @method static \LaraGram\Contracts\Filesystem\Filesystem createLocalDriver(array $config, string $name = 'local')
 * @method static \LaraGram\Contracts\Filesystem\Filesystem createScopedDriver(array $config)
 * @method static \LaraGram\Filesystem\FilesystemManager set(string $name, mixed $disk)
 * @method static string getDefaultDriver()
 * @method static \LaraGram\Filesystem\FilesystemManager forgetDisk(array|string $disk)
 * @method static void purge(string|null $name = null)
 * @method static \LaraGram\Filesystem\FilesystemManager extend(string $driver, \Closure $callback)
 * @method static \LaraGram\Filesystem\FilesystemManager setApplication(\LaraGram\Contracts\Foundation\Application $app)
 * @method static string path(string $path)
 * @method static bool exists(string $path)
 * @method static string|null get(string $path)
 * @method static resource|null readStream(string $path)
 * @method static bool writeStream(string $path, resource $resource, array $options = [])
 * @method static string getVisibility(string $path)
 * @method static bool setVisibility(string $path, string $visibility)
 * @method static bool prepend(string $path, string $data)
 * @method static bool append(string $path, string $data)
 * @method static bool delete(string|array $paths)
 * @method static bool copy(string $from, string $to)
 * @method static bool move(string $from, string $to)
 * @method static int size(string $path)
 * @method static int lastModified(string $path)
 * @method static array files(string|null $directory = null, bool $recursive = false)
 * @method static array allFiles(string|null $directory = null)
 * @method static array directories(string|null $directory = null, bool $recursive = false)
 * @method static array allDirectories(string|null $directory = null)
 * @method static bool makeDirectory(string $path)
 * @method static bool deleteDirectory(string $directory)
 * @method static \LaraGram\Filesystem\FilesystemAdapter assertExists(string|array $path, string|null $content = null)
 * @method static \LaraGram\Filesystem\FilesystemAdapter assertCount(string $path, int $count, bool $recursive = false)
 * @method static \LaraGram\Filesystem\FilesystemAdapter assertMissing(string|array $path)
 * @method static \LaraGram\Filesystem\FilesystemAdapter assertDirectoryEmpty(string $path)
 * @method static bool missing(string $path)
 * @method static bool fileExists(string $path)
 * @method static bool fileMissing(string $path)
 * @method static bool directoryExists(string $path)
 * @method static bool directoryMissing(string $path)
 * @method static array|null json(string $path, int $flags = 0)
 * @method static string|false checksum(string $path, array $options = [])
 * @method static string|false mimeType(string $path)
 * @method static string url(string $path)
 * @method static \LaraGram\Filesystem\FilesystemAdapter getAdapter()
 * @method static array getConfig()
 * @method static void buildTemporaryUrlsUsing(\Closure $callback)
 * @method static void buildTemporaryUploadUrlsUsing(\Closure $callback)
 * @method static \LaraGram\Filesystem\FilesystemAdapter|mixed when(\Closure|mixed|null $value = null, callable|null $callback = null, callable|null $default = null)
 * @method static \LaraGram\Filesystem\FilesystemAdapter|mixed unless(\Closure|mixed|null $value = null, callable|null $callback = null, callable|null $default = null)
 * @method static void macro(string $name, object|callable $macro)
 * @method static void mixin(object $mixin, bool $replace = true)
 * @method static bool hasMacro(string $name)
 * @method static void flushMacros()
 * @method static mixed macroCall(string $method, array $parameters)
 * @method static bool has(string $location)
 * @method static string read(string $location)
 * @method static \LaraGram\Filesystem\DirectoryListing listContents(string $location, bool $deep = false)
 * @method static int fileSize(string $path)
 * @method static string visibility(string $path)
 * @method static void write(string $location, string $contents, array $config = [])
 * @method static void createDirectory(string $location, array $config = [])
 *
 * @see \LaraGram\Filesystem\FilesystemManager
 */
class Storage extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'filesystem';
    }
}
