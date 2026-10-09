<?php
declare(strict_types=1);

$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/');
header('X-Content-Type-Options: nosniff');

if ($path === '/api.php') {
    require __DIR__ . '/api.php';
    return true;
}

if (in_array($path, ['/', '/index.html'], true)) {
    header('Content-Type: text/html; charset=utf-8');
    readfile(__DIR__ . '/index.html');
    return true;
}

http_response_code(404);
header('Content-Type: text/plain; charset=utf-8');
echo 'Halaman tidak ditemukan.';
return true;
