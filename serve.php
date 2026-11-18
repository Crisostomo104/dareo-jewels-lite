<?php
declare(strict_types=1);

// Local development router: let the built-in PHP server serve real files,
// send everything else through the app front controller.
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if ($path !== '/' && $path !== '/serve.php' && is_file(__DIR__ . $path)) {
    return false;
}
require __DIR__ . '/index.php';
