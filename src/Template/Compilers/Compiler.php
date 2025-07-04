<?php

namespace LaraGram\Template\Compilers;

use ErrorException;
use LaraGram\Filesystem\Filesystem;
use LaraGram\Support\Str;
use InvalidArgumentException;

abstract class Compiler
{
    /**
     * The filesystem instance.
     *
     * @var \LaraGram\Filesystem\Filesystem
     */
    protected $files;

    /**
     * The cache path for the compiled templates.
     *
     * @var string
     */
    protected $cachePath;

    /**
     * The base path that should be removed from paths before hashing.
     *
     * @var string
     */
    protected $basePath;

    /**
     * Determines if compiled templates should be cached.
     *
     * @var bool
     */
    protected $shouldCache;

    /**
     * The compiled template file extension.
     *
     * @var string
     */
    protected $compiledExtension = 'php';

    /**
     * Create a new compiler instance.
     *
     * @param  \LaraGram\Filesystem\Filesystem  $files
     * @param  string  $cachePath
     * @param  string  $basePath
     * @param  bool  $shouldCache
     * @param  string  $compiledExtension
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(
        Filesystem $files,
        $cachePath,
        $basePath = '',
        $shouldCache = true,
        $compiledExtension = 'php',
    ) {
        if (! $cachePath) {
            throw new InvalidArgumentException('Please provide a valid cache path.');
        }

        $this->files = $files;
        $this->cachePath = $cachePath;
        $this->basePath = $basePath;
        $this->shouldCache = $shouldCache;
        $this->compiledExtension = $compiledExtension;
    }

    /**
     * Get the path to the compiled version of a template.
     *
     * @param  string  $path
     * @return string
     */
    public function getCompiledPath($path)
    {
        return $this->cachePath.'/'.hash('xxh128', 'v2'.Str::after($path, $this->basePath)).'.'.$this->compiledExtension;
    }

    /**
     * Determine if the template at the given path is expired.
     *
     * @param  string  $path
     * @return bool
     *
     * @throws \ErrorException
     */
    public function isExpired($path)
    {
        if (! $this->shouldCache) {
            return true;
        }

        $compiled = $this->getCompiledPath($path);

        // If the compiled file doesn't exist we will indicate that the template is expired
        // so that it can be re-compiled. Else, we will verify the last modification
        // of the templates is less than the modification times of the compiled templates.
        if (! $this->files->exists($compiled)) {
            return true;
        }

        try {
            return $this->files->lastModified($path) >=
                $this->files->lastModified($compiled);
        } catch (ErrorException $exception) {
            if (! $this->files->exists($compiled)) {
                return true;
            }

            throw $exception;
        }
    }

    /**
     * Create the compiled file directory if necessary.
     *
     * @param  string  $path
     * @return void
     */
    protected function ensureCompiledDirectoryExists($path)
    {
        if (! $this->files->exists(dirname($path))) {
            $this->files->makeDirectory(dirname($path), 0777, true, true);
        }
    }
}
