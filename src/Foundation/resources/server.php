<?php

$publicPath = getcwd();

if (!file_exists($index = $publicPath.'/index.php')) {
    $index = $publicPath.'/public/index.php';
}

$server = escapeshellarg(json_encode($_SERVER));
$inputs = escapeshellarg(file_get_contents('php://input'));

$output = '/dev/null'; // You can change it to specifics file.
$output = 'log.log'; // You can change it to specifics file.

popen("php \"{$index}\" {$inputs} {$server} >> {$output} 2>&1 &", "r");