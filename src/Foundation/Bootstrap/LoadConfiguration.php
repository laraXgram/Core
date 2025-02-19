<?php

namespace LaraGram\Foundation\Bootstrap;

use LaraGram\Config\Repository;
use LaraGram\Contracts\Config\Repository as RepositoryContract;
use LaraGram\Contracts\Foundation\Application;
use SplFileInfo;

class LoadConfiguration
{
    public function bootstrap(Application $app)
    {
        $items = [];

        if (file_exists($cached = $app->getCachedConfigPath())) {
            $items = require $cached;

            $app->instance('config_loaded_from_cache', $loadedFromCache = true);
        }

        $app->instance('config', $config = new Repository($items));

        if (! isset($loadedFromCache)) {
            $this->loadConfigurationFiles($app, $config);
        }

        date_default_timezone_set($config->get('app.timezone', 'UTC'));

        mb_internal_encoding('UTF-8');
    }

    protected function loadConfigurationFiles(Application $app, RepositoryContract $repository)
    {
        $files = $this->getConfigurationFiles($app);

        $shouldMerge = method_exists($app, 'shouldMergeFrameworkConfiguration')
            ? $app->shouldMergeFrameworkConfiguration()
            : true;

        $base = $shouldMerge
            ? $this->getBaseConfiguration()
            : [];

        foreach (array_diff(array_keys($base), array_keys($files)) as $name => $config) {
            $repository->set($name, $config);
        }

        foreach ($files as $name => $path) {
            $base = $this->loadConfigurationFile($repository, $name, $path, $base);
        }

        foreach ($base as $name => $config) {
            $repository->set($name, $config);
        }
    }

    protected function loadConfigurationFile(RepositoryContract $repository, $name, $path, array $base)
    {
        $config = (fn () => require $path)();

        if (isset($base[$name])) {
            $config = array_merge($base[$name], $config);

            foreach ($this->mergeableOptions($name) as $option) {
                if (isset($config[$option])) {
                    $config[$option] = array_merge($base[$name][$option], $config[$option]);
                }
            }

            unset($base[$name]);
        }

        $repository->set($name, $config);

        return $base;
    }
    protected function mergeableOptions($name)
    {
        return [
            'cache' => ['stores'],
            'database' => ['connections'],
            'filesystems' => ['disks'],
            'queue' => ['connections'],
        ][$name] ?? [];
    }

    protected function getConfigurationFiles(Application $app)
    {
        $files = [];

        $configPath = realpath($app->configPath());

        if (!$configPath) {
            return [];
        }

        $this->findPhpFiles($configPath, $files, $configPath);

        ksort($files, SORT_NATURAL);

        return $files;
    }

    protected function findPhpFiles($directory, &$files, $baseDirectory)
    {
        $items = scandir($directory);

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $fullPath = $directory . DIRECTORY_SEPARATOR . $item;

            if (is_dir($fullPath)) {
                $this->findPhpFiles($fullPath, $files, $baseDirectory);
            } elseif (is_file($fullPath) && pathinfo($fullPath, PATHINFO_EXTENSION) === 'php') {
                $nestedDirectory = str_replace($baseDirectory . DIRECTORY_SEPARATOR, '', $directory . DIRECTORY_SEPARATOR);
                $files[$nestedDirectory . basename($fullPath, '.php')] = $fullPath;
            }
        }
    }

    protected function getNestedDirectory(SplFileInfo $file, $configPath)
    {
        $directory = $file->getPath();

        if ($nested = trim(str_replace($configPath, '', $directory), DIRECTORY_SEPARATOR)) {
            $nested = str_replace(DIRECTORY_SEPARATOR, '.', $nested).'.';
        }

        return $nested;
    }

    protected function getBaseConfiguration()
    {
        $config = [];
        $configPath = realpath(__DIR__ . '/../../../../../config');

        if (!$configPath) {
            return [];
        }

        $this->findPhpConfigFiles($configPath, $config);

        return $config;
    }

    protected function findPhpConfigFiles($directory, &$config)
    {
        $items = scandir($directory);

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $fullPath = $directory . DIRECTORY_SEPARATOR . $item;

            if (is_file($fullPath) && pathinfo($fullPath, PATHINFO_EXTENSION) === 'php') {
                $config[basename($fullPath, '.php')] = require $fullPath;
            }
        }
    }
}
