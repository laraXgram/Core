<?php

namespace LaraGram\Console\Events;

class ArtisanStarting
{
    /**
     * The Artisan application instance.
     *
     * @var \LaraGram\Console\Application
     */
    public $artisan;

    /**
     * Create a new event instance.
     *
     * @param  \LaraGram\Console\Application  $artisan
     * @return void
     */
    public function __construct($artisan)
    {
        $this->artisan = $artisan;
    }
}
