<?php

namespace LaraGram\Filesystem;

use Closure;
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
