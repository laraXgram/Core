<?php

namespace LaraGram\Foundation;

use Exception;
use LaraGram\Filesystem\Filesystem;

class PackageManifest
{
    /**
     * The filesystem instance.
     *
     * @var Filesystem
     */
    public $files;

    /**
     * The base path.
     *
     * @var string
     */
    public $basePath;

    /**
     * The vendor path.
     *
     * @var string
     */
    public $vendorPath;

    /**
     * The manifest path.
     *
     * @var string|null
     */
    public $manifestPath;

    /**
     * The loaded manifest array.
     *
     * @var array
     */
    public $manifest;

    /**
     * Create a new package manifest instance.
     *
     * @param  Filesystem  $files
     * @param  string  $basePath
     * @param  string  $manifestPath
     * @return void
     */
    public function __construct(Filesystem $files, $basePath, $manifestPath)
    {
        $this->files = $files;
        $this->basePath = $basePath;
        $this->manifestPath = $manifestPath;
        $this->vendorPath = $basePath.'/vendor';
    }

    /**
     * Get all of the service provider class names for all packages.
     *
     * @return array
     */
    public function providers()
    {
        return $this->config('providers');
    }

    public function aliases()
    {
        return $this->config('aliases');
    }

    /**
     * Get all of the values for all packages for the given configuration name.
     *
     * @param  string  $key
     * @return array
     */
    public function config($key)
    {
        $manifest = $this->getManifest();
        $result = [];

        foreach ($manifest as $configuration) {
            if (isset($configuration[$key])) {
                $result = array_merge($result, (array) $configuration[$key]);
            }
        }

        return array_filter($result);
    }

    protected function getManifest()
    {
        if (! is_null($this->manifest)) {
            return $this->manifest;
        }

        if (! is_file($this->manifestPath)) {
            $this->build();
        }

        return $this->manifest = is_file($this->manifestPath) ?
            $this->files->getRequire($this->manifestPath) : [];
    }

    /**
     * Build the manifest and write it to disk.
     *
     * @return void
     */
    public function build()
    {
        $packages = [];

        if ($this->files->exists($path = $this->vendorPath.'/composer/installed.json')) {
            $installed = json_decode($this->files->get($path), true);

            $packages = $installed['packages'] ?? $installed;
        }

        $ignore = $this->packagesToIgnore();
        $ignoreAll = in_array('*', $ignore);

        $result = [];

        foreach ($packages as $package) {
            $packageName = $this->format($package['name']);
            $laragramConfig = $package['extra']['laragram'] ?? [];

            $result[$packageName] = $laragramConfig;

            if (isset($laragramConfig['dont-discover'])) {
                $ignore = array_merge($ignore, $laragramConfig['dont-discover']);
            }
        }

        $filtered = [];
        foreach ($result as $package => $configuration) {
            if (!$ignoreAll && !in_array($package, $ignore) && !empty($configuration)) {
                $filtered[$package] = $configuration;
            }
        }

        $this->write($filtered);
    }

    /**
     * Format the given package name.
     *
     * @param  string  $package
     * @return string
     */
    protected function format($package)
    {
        return str_replace($this->vendorPath.'/', '', $package);
    }

    /**
     * Get all of the package names that should be ignored.
     *
     * @return array
     */
    protected function packagesToIgnore()
    {
        if (! is_file($this->basePath.'/composer.json')) {
            return [];
        }

        return json_decode(file_get_contents(
            $this->basePath.'/composer.json'
        ), true)['extra']['laragram']['dont-discover'] ?? [];
    }

    /**
     * Write the given manifest array to disk.
     *
     * @param  array  $manifest
     * @return void
     *
     * @throws \Exception
     */
    protected function write(array $manifest)
    {
        if (! is_writable($dirname = dirname($this->manifestPath))) {
            throw new Exception("The {$dirname} directory must be present and writable.");
        }

        $this->files->replace(
            $this->manifestPath, '<?php return '.var_export($manifest, true).';'
        );
    }
}
