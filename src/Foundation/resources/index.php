<?php

$serverPath = __DIR__."/server.php";

if (($content = json_decode(file_get_contents('php://input'), true)) == null) {
    require_once $serverPath;
} else {
    $request = escapeshellarg(json_encode([
        'TIME' => time(),
        'HASH' => md5(uniqid(rand(), true)),
        '_' => [
            '_GET' => $_GET,
            '_POST' => $_POST,
            '_SERVER' => $_SERVER,
            '_CONTENTS' => $content,
        ]
    ]));

    popen("php \"{$serverPath}\" {$request} >> /dev/null 2>&1 &", "r");
}
