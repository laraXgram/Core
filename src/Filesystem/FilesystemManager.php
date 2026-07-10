<?php

namespace LaraGram\Filesystem;

use Aws\S3\S3Client;
use LaraGram\Filesystem\Connections\Aws\PortableVisibilityConverter as AwsS3PortableVisibilityConverter;
use LaraGram\Filesystem\Connections\Aws\AwsS3V3Adapter as S3Adapter;
use League\Flysystem\PhpseclibV3\SftpAdapter;
use League\Flysystem\PhpseclibV3\SftpConnectionProvider;
use Closure;
use LaraGram\Filesystem\Connections\Ftp\FtpAdapter;
use LaraGram\Filesystem\Connections\Ftp\FtpConnectionOptions;
use LaraGram\Support\Arr;
use LaraGram\Contracts\Filesystem\Factory as FactoryContract;
use InvalidArgumentException;
use LaraGram\Contracts\Filesystem\FilesystemAdapter as FilesystemAdapterContract;
use LaraGram\Filesystem\UnixVisibility\PortableVisibilityConverter;
use LaraGram\Support\RebindsCallbacksToSelf;
use function LaraGram\Support\enum_value;

/**
 * @mixin \LaraGram\Contracts\Filesystem\Filesystem
 */
class FilesystemManager implements FactoryContract
{
    use RebindsCallbacksToSelf;

    /**
     * The application instance.
     *
     * @var \LaraGram\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * The array of resolved filesystem drivers.
     *
     * @var array
     */
    protected $disks = [];

    /**
     * The registered custom driver creators.
     *
     * @var array
     */
    protected $customCreators = [];

    /**
     * Create a new filesystem manager instance.
     *
     * @param  \LaraGram\Contracts\Foundation\Application  $app
     * @return void
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * Get a filesystem instance.
     *
     * @param  string|null  $name
     * @return \LaraGram\Contracts\Filesystem\Filesystem
     */
    public function drive($name = null)
    {
        return $this->disk($name);
    }

    /**
     * Get a filesystem instance.
     *
     * @param  \UnitEnum|string|null  $name
     * @return \LaraGram\Contracts\Filesystem\Filesystem
     */
    public function disk($name = null)
    {
        $name = enum_value($name) ?: $this->getDefaultDriver();

        return $this->disks[$name] = $this->get($name);
    }

    /**
     * Get a default cloud filesystem instance.
     *
     * @return \LaraGram\Contracts\Filesystem\Cloud
     */
    public function cloud()
    {
        $name = $this->getDefaultCloudDriver();

        return $this->disks[$name] = $this->get($name);
    }

    /**
     * Build an on-demand disk.
     *
     * @param  string|array  $config
     * @return \LaraGram\Contracts\Filesystem\Filesystem
     */
    public function build($config)
    {
        return $this->resolve('ondemand', is_array($config) ? $config : [
            'driver' => 'local',
            'root' => $config,
        ]);
    }

    /**
     * Attempt to get the disk from the local cache.
     *
     * @param  string  $name
     * @return \LaraGram\Contracts\Filesystem\Filesystem
     */
    protected function get($name)
    {
        return $this->disks[$name] ?? $this->resolve($name);
    }

    /**
     * Resolve the given disk.
     *
     * @param  string  $name
     * @param  array|null  $config
     * @return \LaraGram\Contracts\Filesystem\Filesystem
     *
     * @throws \InvalidArgumentException
     */
    protected function resolve($name, $config = null)
    {
        $config ??= $this->getConfig($name);

        if (empty($config['driver'])) {
            throw new InvalidArgumentException("Disk [{$name}] does not have a configured driver.");
        }

        $driver = $config['driver'];

        if (isset($this->customCreators[$driver])) {
            return $this->callCustomCreator($config);
        }

        $driverMethod = 'create'.ucfirst($driver).'Driver';

        if (! method_exists($this, $driverMethod)) {
            throw new InvalidArgumentException("Driver [{$driver}] is not supported.");
        }

        return $this->{$driverMethod}($config, $name);
    }

    /**
     * Call a custom driver creator.
     *
     * @param  array  $config
     * @return \LaraGram\Contracts\Filesystem\Filesystem
     */
    protected function callCustomCreator(array $config)
    {
        return $this->customCreators[$config['driver']]($this->app, $config);
    }

    /**
     * Create an instance of the local driver.
     *
     * @param  array  $config
     * @param  string  $name
     * @return LocalFilesystemAdapter
     */
    public function createLocalDriver(array $config, string $name = 'local')
    {
        $visibility = PortableVisibilityConverter::fromArray(
            $config['permissions'] ?? [],
            $config['directory_visibility'] ?? $config['visibility'] ?? Visibility::PRIVATE
        );

        $links = ($config['links'] ?? null) === 'skip'
            ? FlysystemLocalFilesystemAdapter::SKIP_LINKS
            : FlysystemLocalFilesystemAdapter::DISALLOW_LINKS;

        $adapter = new FlysystemLocalFilesystemAdapter(
            $config['root'], $visibility, $config['lock'] ?? LOCK_EX, $links
        );

        return (new LocalFilesystemAdapter(
            $this->createFlysystem($adapter, $config), $adapter, $config
        ))->diskName(
            $name
        )->shouldServeSignedUrls(
            $config['serve'] ?? false,
            fn () => $this->app['url'],
        );
    }

    /**
     * Create an instance of the ftp driver.
     *
     * @param  array  $config
     * @return \LaraGram\Contracts\Filesystem\Filesystem
     */
    public function createFtpDriver(array $config)
    {
        if (! isset($config['root'])) {
            $config['root'] = '';
        }

        $adapter = new FtpAdapter(FtpConnectionOptions::fromArray($config));

        return new FilesystemAdapter($this->createFlysystem($adapter, $config), $adapter, $config);
    }

    /**
     * Create an instance of the sftp driver.
     *
     * @param  array  $config
     * @return \LaraGram\Contracts\Filesystem\Filesystem
     */
    public function createSftpDriver(array $config)
    {
        if (! class_exists('League\Flysystem\PhpseclibV3\SftpConnectionProvider')) {
            throw new InvalidArgumentException('SftpConnectionProvider is not available. Please install league/flysystem-php-v3');
        }

        $provider = SftpConnectionProvider::fromArray($config);

        $root = $config['root'] ?? '';

        $visibility = PortableVisibilityConverter::fromArray(
            $config['permissions'] ?? []
        );

        $adapter = new SftpAdapter($provider, $root, $visibility);

        return new FilesystemAdapter($this->createFlysystem($adapter, $config), $adapter, $config);
    }

    /**
     * Create an instance of the Amazon S3 driver.
     *
     * @param  array  $config
     * @return \LaraGram\Contracts\Filesystem\Cloud
     */
    public function createS3Driver(array $config)
    {
        if (! class_exists('Aws\S3\S3Client')) {
            throw new InvalidArgumentException('S3Client is not available. Please install aws/aws-sdk-php.');
        }

        $s3Config = $this->formatS3Config($config);

        $root = (string) ($s3Config['root'] ?? '');

        $visibility = new AwsS3PortableVisibilityConverter(
            $config['visibility'] ?? Visibility::PUBLIC
        );

        $streamReads = $s3Config['stream_reads'] ?? false;

        $client = new S3Client($s3Config);

        $adapter = new S3Adapter($client, $s3Config['bucket'], $root, $visibility, null, $config['options'] ?? [], $streamReads);

        return new AwsS3V3Adapter(
            $this->createFlysystem($adapter, $config), $adapter, $s3Config, $client
        );
    }

    /**
     * Format the given S3 configuration with the default options.
     *
     * @param  array  $config
     * @return array
     */
    protected function formatS3Config(array $config)
    {
        $config += ['version' => 'latest'];

        if (! empty($config['key']) && ! empty($config['secret'])) {
            $config['credentials'] = Arr::only($config, ['key', 'secret']);

            if (! empty($config['token'])) {
                $config['credentials']['token'] = $config['token'];
            }
        }

        return Arr::except($config, ['token']);
    }

    /**
     * Create a scoped driver.
     *
     * @param  array  $config
     * @return \LaraGram\Contracts\Filesystem\Filesystem
     *
     * @throws \InvalidArgumentException
     */
    public function createScopedDriver(array $config)
    {
        if (empty($config['disk'])) {
            throw new InvalidArgumentException('Scoped disk is missing "disk" configuration option.');
        } elseif (empty($config['prefix'])) {
            throw new InvalidArgumentException('Scoped disk is missing "prefix" configuration option.');
        }

        return $this->build(tap(
            is_string($config['disk']) ? $this->getConfig($config['disk']) : $config['disk'],
            function (&$parent) use ($config) {
                if (empty($parent['prefix'])) {
                    $parent['prefix'] = $config['prefix'];
                } else {
                    $separator = $parent['directory_separator'] ?? DIRECTORY_SEPARATOR;

                    $parentPrefix = rtrim($parent['prefix'], $separator);
                    $scopedPrefix = ltrim($config['prefix'], $separator);

                    $parent['prefix'] = "{$parentPrefix}{$separator}{$scopedPrefix}";
                }

                if (isset($config['visibility'])) {
                    $parent['visibility'] = $config['visibility'];
                }

                if (isset($config['throw'])) {
                    $parent['throw'] = $config['throw'];
                }
            }
        ));
    }

    /**
     * Create a Flysystem instance with the given adapter.
     *
     * @param  \LaraGram\Contracts\Filesystem\FilesystemAdapter  $adapter
     * @param  array  $config
     * @return \LaraGram\Contracts\Filesystem\FilesystemOperator
     */
    protected function createFlysystem(FilesystemAdapterContract $adapter, array $config)
    {
        if ($config['read-only'] ?? false) {
            $adapter = new ReadOnlyFilesystemAdapter($adapter);
        }

        if (! empty($config['prefix'])) {
            $adapter = new PathPrefixedAdapter($adapter, $config['prefix']);
        }

        if (str_contains($config['endpoint'] ?? '', 'r2.cloudflarestorage.com')) {
            $config['retain_visibility'] = false;
        }

        return new Flysystem($adapter, Arr::only($config, [
            'directory_visibility',
            'disable_asserts',
            'retain_visibility',
            'temporary_url',
            'url',
            'visibility',
        ]));
    }

    /**
     * Set the given disk instance.
     *
     * @param  string  $name
     * @param  mixed  $disk
     * @return $this
     */
    public function set($name, $disk)
    {
        $this->disks[$name] = $disk;

        return $this;
    }

    /**
     * Get the filesystem connection configuration.
     *
     * @param  string  $name
     * @return array
     */
    protected function getConfig($name)
    {
        return $this->app['config']["filesystems.disks.{$name}"] ?: [];
    }

    /**
     * Get the default driver name.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return $this->app['config']['filesystems.default'];
    }

    /**
     * Get the default cloud driver name.
     *
     * @return string
     */
    public function getDefaultCloudDriver()
    {
        return $this->app['config']['filesystems.cloud'] ?? 's3';
    }

    /**
     * Unset the given disk instances.
     *
     * @param  array|string  $disk
     * @return $this
     */
    public function forgetDisk($disk)
    {
        foreach ((array) $disk as $diskName) {
            unset($this->disks[$diskName]);
        }

        return $this;
    }

    /**
     * Disconnect the given disk and remove from local cache.
     *
     * @param  string|null  $name
     * @return void
     */
    public function purge($name = null)
    {
        $name ??= $this->getDefaultDriver();

        unset($this->disks[$name]);
    }

    /**
     * Register a custom driver creator Closure.
     *
     * @param  string  $driver
     * @param  \Closure  $callback
     * @return $this
     */
    public function extend($driver, Closure $callback)
    {
        $this->customCreators[$driver] = $callback;

        return $this;
    }

    /**
     * Set the application instance used by the manager.
     *
     * @param  \LaraGram\Contracts\Foundation\Application  $app
     * @return $this
     */
    public function setApplication($app)
    {
        $this->app = $app;

        return $this;
    }

    /**
     * Dynamically call the default driver instance.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->disk()->$method(...$parameters);
    }
}
