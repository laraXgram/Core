<?php

$server = escapeshellarg(json_encode($_SERVER));
$inputs = escapeshellarg(file_get_contents('php://input'));

$serverPath = __DIR__."/server.php";

popen("php \"{$serverPath}\" {$inputs} {$server} >> /dev/null 2>&1 &", "r");
