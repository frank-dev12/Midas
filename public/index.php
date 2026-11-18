<?php

declare(strict_types=1);

if (PHP_SAPI === 'cli-server') {
    $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    if (is_string($requestPath) && $requestPath !== '/' && !str_contains($requestPath, '..')) {
        $file = realpath(__DIR__ . $requestPath);
        $root = realpath(__DIR__);
        if ($file !== false && $root !== false && is_file($file) && str_starts_with($file, $root . DIRECTORY_SEPARATOR)) {
            return false;
        }
    }
}

require dirname(__DIR__) . '/src/bootstrap.php';

auth_start();
header('Content-Type: text/html; charset=utf-8');
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header('X-Robots-Tag: noindex');
header('Cache-Control: no-store');

set_exception_handler(static function (Throwable $error): void {
    error_log($error->__toString());
    if (str_starts_with(request_path(), '/api/')) {
        json_out(['ok' => false, 'error' => 'No se pudo completar la operación.'], 500);
    }
    http_response_code(500);
    echo 'Ocurrió un error. Inténtalo de nuevo.';
});

midas_dispatch();
