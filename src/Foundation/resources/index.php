<?php

$serverPath = __DIR__."/server.php";

if (($content = json_decode(file_get_contents('php://input'), true)) == null) {
    require_once $serverPath;
} else {
    $server = escapeshellarg(json_encode($_SERVER));
    $inputs = escapeshellarg(file_get_contents('php://input'));

    popen("php \"{$serverPath}\" {$inputs} {$server} >> /dev/null 2>&1 &", "r");
}
