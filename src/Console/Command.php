<?php

namespace LaraGram\Console;

use LaraGram\Contracts\Foundation\Application;

abstract class Command
{
    protected Application $app;
    protected $signature;
    protected $description;
    protected $options = [];
    protected $arguments = [];

    protected $output;
    public $silent;

    public function __construct()
    {
        $this->app = app();
        $this->output = app()->make(Output::class);

        $commands = app('console.commands');
        $commands[$this->signature] = get_called_class();
        app()->instance('console.commands', $commands);

        $this->parseSignature();
    }

    abstract public function handle();

    protected function parseSignature()
    {
        preg_match_all('/\{(\w+)(=.*?)?\}/', $this->signature, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $name = $match[1];
            $default = isset($match[2]) ? trim($match[2], '=') : null;
            $this->arguments[$name] = $default;
        }
    }

    public function setArgument($index, $value)
    {
        $this->arguments[$index] = $value;
    }

    public function setOption($name, $value)
    {
        $this->options[$name] = $value;
    }

    public function getArgument($index)
    {
        return $this->arguments[$index] ?? null;
    }

    public function getOption($name)
    {
        return $this->options[$name] ?? null;
    }

    public function getSignature()
    {
        return $this->signature;
    }

    public function getDescription()
    {
        return $this->description;
    }
}
