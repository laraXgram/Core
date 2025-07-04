<?php

namespace LaraGram\Template\Engines;

abstract class Engine
{
    /**
     * The template that was last to be rendered.
     *
     * @var string
     */
    protected $lastRendered;

    /**
     * Get the last template that was rendered.
     *
     * @return string
     */
    public function getLastRendered()
    {
        return $this->lastRendered;
    }
}
