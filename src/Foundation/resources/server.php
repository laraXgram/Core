<?php

use LaraGram\Foundation\Application;
use LaraGram\Request\Request;

define('LARAGRAM_START', microtime(true));

// Register the Composer autoloader...
require __DIR__.'/../../../../../autoload.php';

// Bootstrap LaraGram and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../../../../../../bootstrap/app.php';

$app->handleRequest(Request::capture());