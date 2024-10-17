<?php

namespace LaraGram\Cache\Driver;

use LaraGram\Cache\Database\Cache;
use LaraGram\Contracts\CacheDriver;

class FileCacheDriver implements CacheDriver
{
    protected string $cacheDir;

    public function __construct($cacheDir)
    {
        $this->cacheDir = $cacheDir;
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0777, true);
        }
    }

    private function getFilePath($key): string
    {
        $keyParts = explode('.', $key);

        if (count($keyParts) > 1) {
            $directory = implode('/', array_slice($keyParts, 0, -1));
            $filePath = $this->cacheDir . '/' . $directory;
        } else {
            $filePath = $this->cacheDir;
        }

        if (!file_exists($filePath)) {
            mkdir($filePath, recursive: true);
        }

        return $filePath . '/' . md5($key) . '.cache';
    }

    public function get($key)
    {
        $filePath = $this->getFilePath($key);
        if (file_exists($filePath)) {
            return unserialize(file_get_contents($filePath));
        }
        return null;
    }

    public function set($key, $value): void
    {
        $filePath = $this->getFilePath($key);
        file_put_contents($filePath, serialize($value));
    }

    public function forgot($key): void
    {
        $filePath = $this->getFilePath($key);
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    public function clear(): void
    {
        $files = glob($this->cacheDir . '/*');
        foreach ($files as $file) {
            unlink($file);
        }
    }
}