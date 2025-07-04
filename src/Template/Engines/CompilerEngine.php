<?php

namespace LaraGram\Template\Engines;

use LaraGram\Database\RecordNotFoundException;
use LaraGram\Database\RecordsNotFoundException;
use LaraGram\Filesystem\Filesystem;
use LaraGram\Support\Str;
use LaraGram\Template\Compilers\CompilerInterface;
use LaraGram\Template\TemplateException;
use Throwable;

class CompilerEngine extends PhpEngine
{
    /**
     * The Temple8 compiler instance.
     *
     * @var \LaraGram\Template\Compilers\CompilerInterface
     */
    protected $compiler;

    /**
     * A stack of the last compiled templates.
     *
     * @var array
     */
    protected $lastCompiled = [];

    /**
     * The template paths that were compiled or are not expired, keyed by the path.
     *
     * @var array<string, true>
     */
    protected $compiledOrNotExpired = [];

    /**
     * Create a new compiler engine instance.
     *
     * @param  \LaraGram\Template\Compilers\CompilerInterface  $compiler
     * @param  \LaraGram\Filesystem\Filesystem|null  $files
     * @return void
     */
    public function __construct(CompilerInterface $compiler, ?Filesystem $files = null)
    {
        parent::__construct($files ?: new Filesystem);

        $this->compiler = $compiler;
    }

    /**
     * Get the evaluated contents of the template.
     *
     * @param  string  $path
     * @param  array  $data
     * @return string
     */
    public function get($path, array $data = [])
    {
        $this->lastCompiled[] = $path;

        // If this given template has expired, which means it has simply been edited since
        // it was last compiled, we will re-compile the templates so we can evaluate a
        // fresh copy of the template. We'll pass the compiler the path of the template.
        if (! isset($this->compiledOrNotExpired[$path]) && $this->compiler->isExpired($path)) {
            $this->compiler->compile($path);
        }

        // Once we have the path to the compiled file, we will evaluate the paths with
        // typical PHP just like any other templates. We also keep a stack of templates
        // which have been rendered for right exception messages to be generated.

        try {
            $results = $this->evaluatePath($this->compiler->getCompiledPath($path), $data);
        } catch (TemplateException $e) {
            if (! Str::of($e->getMessage())->contains(['No such file or directory', 'File does not exist at path'])) {
                throw $e;
            }

            if (! isset($this->compiledOrNotExpired[$path])) {
                throw $e;
            }

            $this->compiler->compile($path);

            $results = $this->evaluatePath($this->compiler->getCompiledPath($path), $data);
        }

        $this->compiledOrNotExpired[$path] = true;

        array_pop($this->lastCompiled);

        return $results;
    }

    /**
     * Handle a template exception.
     *
     * @param  \Throwable  $e
     * @param  int  $obLevel
     * @return void
     *
     * @throws \Throwable
     */
    protected function handleTemplateException(Throwable $e, $obLevel)
    {
        if ($e instanceof RecordNotFoundException ||
            $e instanceof RecordsNotFoundException) {
            parent::handleTemplateException($e, $obLevel);
        }

        $e = new TemplateException($this->getMessage($e), 0, 1, $e->getFile(), $e->getLine(), $e);

        parent::handleTemplateException($e, $obLevel);
    }

    /**
     * Get the exception message for an exception.
     *
     * @param  \Throwable  $e
     * @return string
     */
    protected function getMessage(Throwable $e)
    {
        return $e->getMessage().' (Template: '.realpath(last($this->lastCompiled)).')';
    }

    /**
     * Get the compiler implementation.
     *
     * @return \LaraGram\Template\Compilers\CompilerInterface
     */
    public function getCompiler()
    {
        return $this->compiler;
    }

    /**
     * Clear the cache of templates that were compiled or not expired.
     *
     * @return void
     */
    public function forgetCompiledOrNotExpired()
    {
        $this->compiledOrNotExpired = [];
    }
}
