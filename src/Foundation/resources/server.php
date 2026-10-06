<?php

use LaraGram\Foundation\Application;
use LaraGram\Request\Request as BotRequest;
use LaraGram\Http\Request as HttpRequest;

define('LARAGRAM_START', microtime(true));

// Find the application this file serves.
$basePath = (function () {
    $entry = PHP_SAPI === 'cli'
        ? ($_SERVER['argv'][0] ?? __FILE__)
        : ($_SERVER['SCRIPT_FILENAME'] ?? __FILE__);

    for ($directory = dirname($entry); $directory !== dirname($directory); $directory = dirname($directory)) {
        if (is_file($directory.'/bootstrap/app.php') && is_file($directory.'/vendor/autoload.php')) {
            return $directory;
        }
    }

    return dirname(__DIR__, 6);
})();

// Register the Composer autoloader...
require $basePath.'/vendor/autoload.php';

// Bootstrap LaraGram and handle the request...
/** @var Application $app */
$app = require_once $basePath.'/bootstrap/app.php';

if (isset($argv)) {
    $app->handleRequest(BotRequest::capture());
} else {
    $app->handleHttpRequest(HttpRequest::capture());
}
