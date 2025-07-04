<?php

namespace LaraGram\Template;

use LaraGram\Filesystem\Filesystem;
use InvalidArgumentException;

class FileTemplateFinder implements TemplateFinderInterface
{
    /**
     * The filesystem instance.
     *
     * @var \LaraGram\Filesystem\Filesystem
     */
    protected $files;

    /**
     * The array of active template paths.
     *
     * @var array
     */
    protected $paths;

    /**
     * The array of templates that have been located.
     *
     * @var array
     */
    protected $templates = [];

    /**
     * The namespace to file path hints.
     *
     * @var array
     */
    protected $hints = [];

    /**
     * Register a template extension with the finder.
     *
     * @var string[]
     */
    protected $extensions = ['t8.php', 'php', 'css', 'html'];

    /**
     * Create a new file template loader instance.
     *
     * @param  \LaraGram\Filesystem\Filesystem  $files
     * @param  array  $paths
     * @param  array|null  $extensions
     * @return void
     */
    public function __construct(Filesystem $files, array $paths, ?array $extensions = null)
    {
        $this->files = $files;
        $this->paths = array_map($this->resolvePath(...), $paths);

        if (isset($extensions)) {
            $this->extensions = $extensions;
        }
    }

    /**
     * Get the fully qualified location of the template.
     *
     * @param  string  $name
     * @return string
     */
    public function find($name)
    {
        if (isset($this->templates[$name])) {
            return $this->templates[$name];
        }

        if ($this->hasHintInformation($name = trim($name))) {
            return $this->templates[$name] = $this->findNamespacedTemplate($name);
        }

        return $this->templates[$name] = $this->findInPaths($name, $this->paths);
    }

    /**
     * Get the path to a template with a named path.
     *
     * @param  string  $name
     * @return string
     */
    protected function findNamespacedTemplate($name)
    {
        [$namespace, $template] = $this->parseNamespaceSegments($name);

        return $this->findInPaths($template, $this->hints[$namespace]);
    }

    /**
     * Get the segments of a template with a named path.
     *
     * @param  string  $name
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    protected function parseNamespaceSegments($name)
    {
        $segments = explode(static::HINT_PATH_DELIMITER, $name);

        if (count($segments) !== 2) {
            throw new InvalidArgumentException("Template [{$name}] has an invalid name.");
        }

        if (! isset($this->hints[$segments[0]])) {
            throw new InvalidArgumentException("No hint path defined for [{$segments[0]}].");
        }

        return $segments;
    }

    /**
     * Find the given template in the list of paths.
     *
     * @param  string  $name
     * @param  array  $paths
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    protected function findInPaths($name, $paths)
    {
        foreach ((array) $paths as $path) {
            foreach ($this->getPossibleTemplateFiles($name) as $file) {
                $templatePath = $path.'/'.$file;

                if (strlen($templatePath) < (PHP_MAXPATHLEN - 1) && $this->files->exists($templatePath)) {
                    return $templatePath;
                }
            }
        }

        throw new InvalidArgumentException("Template [{$name}] not found.");
    }

    /**
     * Get an array of possible template files.
     *
     * @param  string  $name
     * @return array
     */
    protected function getPossibleTemplateFiles($name)
    {
        return array_map(fn ($extension) => str_replace('.', '/', $name).'.'.$extension, $this->extensions);
    }

    /**
     * Add a location to the finder.
     *
     * @param  string  $location
     * @return void
     */
    public function addLocation($location)
    {
        $this->paths[] = $this->resolvePath($location);
    }

    /**
     * Prepend a location to the finder.
     *
     * @param  string  $location
     * @return void
     */
    public function prependLocation($location)
    {
        array_unshift($this->paths, $this->resolvePath($location));
    }

    /**
     * Resolve the path.
     *
     * @param  string  $path
     * @return string
     */
    protected function resolvePath($path)
    {
        return realpath($path) ?: $path;
    }

    /**
     * Add a namespace hint to the finder.
     *
     * @param  string  $namespace
     * @param  string|array  $hints
     * @return void
     */
    public function addNamespace($namespace, $hints)
    {
        $hints = (array) $hints;

        if (isset($this->hints[$namespace])) {
            $hints = array_merge($this->hints[$namespace], $hints);
        }

        $this->hints[$namespace] = $hints;
    }

    /**
     * Prepend a namespace hint to the finder.
     *
     * @param  string  $namespace
     * @param  string|array  $hints
     * @return void
     */
    public function prependNamespace($namespace, $hints)
    {
        $hints = (array) $hints;

        if (isset($this->hints[$namespace])) {
            $hints = array_merge($hints, $this->hints[$namespace]);
        }

        $this->hints[$namespace] = $hints;
    }

    /**
     * Replace the namespace hints for the given namespace.
     *
     * @param  string  $namespace
     * @param  string|array  $hints
     * @return void
     */
    public function replaceNamespace($namespace, $hints)
    {
        $this->hints[$namespace] = (array) $hints;
    }

    /**
     * Register an extension with the template finder.
     *
     * @param  string  $extension
     * @return void
     */
    public function addExtension($extension)
    {
        if (($index = array_search($extension, $this->extensions)) !== false) {
            unset($this->extensions[$index]);
        }

        array_unshift($this->extensions, $extension);
    }

    /**
     * Returns whether or not the template name has any hint information.
     *
     * @param  string  $name
     * @return bool
     */
    public function hasHintInformation($name)
    {
        return strpos($name, static::HINT_PATH_DELIMITER) > 0;
    }

    /**
     * Flush the cache of located templates.
     *
     * @return void
     */
    public function flush()
    {
        $this->templates = [];
    }

    /**
     * Get the filesystem instance.
     *
     * @return \LaraGram\Filesystem\Filesystem
     */
    public function getFilesystem()
    {
        return $this->files;
    }

    /**
     * Set the active template paths.
     *
     * @param  array  $paths
     * @return $this
     */
    public function setPaths($paths)
    {
        $this->paths = $paths;

        return $this;
    }

    /**
     * Get the active template paths.
     *
     * @return array
     */
    public function getPaths()
    {
        return $this->paths;
    }

    /**
     * Get the templates that have been located.
     *
     * @return array
     */
    public function getTemplates()
    {
        return $this->templates;
    }

    /**
     * Get the namespace to file path hints.
     *
     * @return array
     */
    public function getHints()
    {
        return $this->hints;
    }

    /**
     * Get registered extensions.
     *
     * @return array
     */
    public function getExtensions()
    {
        return $this->extensions;
    }
}
