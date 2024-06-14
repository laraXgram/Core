<?php

use LaraGram\Foundation\Application;

require_once 'vendor/autoload.php';

$start = microtime(true);

$app = new Application();

// Test Base ...

$end = (microtime(true) - $start) * 1000 . 'ms';
