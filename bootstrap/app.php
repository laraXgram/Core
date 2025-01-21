<?php

use LaraGram\Foundation\Application;

require_once 'vendor/autoload.php';

return Application::configure(dirname(__DIR__))
    ->create();