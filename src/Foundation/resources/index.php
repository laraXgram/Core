<?php

$ignore = [
    '/favicon.ico',
    '/robots.txt',
    '/apple-touch-icon.png',
    '/apple-touch-icon-precomposed.png',
    '/manifest.json',
    '/service-worker.js',
];

if (in_array(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), $ignore, true)) {
    http_response_code(204);
    exit;
}

$request = escapeshellarg(json_encode([
    '_' => [
        'GET' => $_GET,
        'POST' => $_POST,
        'SERVER' => $_SERVER,
        'UPDATE' => json_decode(file_get_contents('php://input'), true),
    ]
]));

$serverPath = __DIR__.'/server.php';

popen("php \"{$serverPath}\" {$request} >> /dev/null 2>&1 &", "r");
