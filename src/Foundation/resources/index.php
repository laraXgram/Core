<?php

$server = escapeshellarg(json_encode($_SERVER));
$inputs = escapeshellarg(file_get_contents('php://input'));

$serverPath = __DIR__."/server.php";

$output = '/dev/null'; // You can change it to specifics file.
$output = 'log.log'; // You can change it to specifics file.

popen("php \"{$serverPath}\" {$inputs} {$server} >> {$output} 2>&1 &", "r");
