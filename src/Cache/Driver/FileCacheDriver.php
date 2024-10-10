<?php

namespace LaraGram\Cache\Driver;

use LaraGram\Cache\Database\Cache;
use LaraGram\Contracts\CacheDriver;

class FileCacheDriver implements CacheDriver {
    protected string $cacheDir;

    public function __construct($cacheDir) {
        $this->cacheDir = $cacheDir;
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0777, true);
        }
    }

    public function get($key) {
        $filePath = $this->cacheDir . '/' . md5($key);
        if (file_exists($filePath)) {
            return unserialize(file_get_contents($filePath));
        }
        return null;
    }

    public function set($key, $value): void
    {
        $filePath = $this->cacheDir . '/' . md5($key);
        file_put_contents($filePath, serialize($value));
    }

    public function forgot($key): void
    {
        $filePath = $this->cacheDir . '/' . md5($key);
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

    public function pull($key)
    {
        $data = $this->get($key);
        $this->forgot($key);
        return $data;
    }
}