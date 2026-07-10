<?php

use LaraGram\Foundation\Application;
use LaraGram\Request\Request as BotRequest;
use LaraGram\Http\Request as HttpRequest;

define('LARAGRAM_START', microtime(true));

// Register the Composer autoloader...
require __DIR__.'/../../../vendor/autoload.php';

// Bootstrap LaraGram and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../../../bootstrap/app.php';

if (isset($argv)) {
    $app->handleRequest(BotRequest::capture());
} else {
    $app->handleHttpRequest(HttpRequest::capture());
}
