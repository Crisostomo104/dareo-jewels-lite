<?php
declare(strict_types=1);

namespace App;

require_once __DIR__ . '/Env.php';
$envPath = getenv('APP_ENV_FILE') ?: (dirname(__DIR__) . '/.env');
Env::load($envPath);

$debug = Env::bool('APP_DEBUG', false);
date_default_timezone_set(Env::get('APP_TIMEZONE', 'Asia/Manila'));
mb_internal_encoding('UTF-8');
ini_set('display_errors', $debug ? '1' : '0');
error_reporting($debug ? E_ALL : (E_ALL & ~E_DEPRECATED & ~E_NOTICE));

require_once __DIR__ . '/Http.php';
require_once __DIR__ . '/Money.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Pricing.php';
require_once __DIR__ . '/Report.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/Router.php';
require_once __DIR__ . '/controllers/PublicController.php';
require_once __DIR__ . '/controllers/AdminController.php';

if (PHP_SAPI !== 'cli' && !headers_sent()) {
    if (Env::bool('FORCE_HTTPS', false)) {
        $forwarded = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
        $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        if (!$https && $forwarded !== 'https') {
            $host = (string) ($_SERVER['HTTP_HOST'] ?? '');
            $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
            if ($host !== '') {
                header('Location: https://' . $host . $uri, true, 301);
                exit;
            }
        }
    }
    if (Env::bool('SECURITY_HEADERS', true)) {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://challenges.cloudflare.com; style-src 'self' 'unsafe-inline'; img-src 'self' data: blob:; font-src 'self' data:; connect-src 'self' https://challenges.cloudflare.com; frame-src https://challenges.cloudflare.com; form-action 'self'; frame-ancestors 'none'; base-uri 'self'; object-src 'none'");
    }
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    $sameSite = strtolower(Env::get('SESSION_SAMESITE', 'Lax'));
    if (!in_array($sameSite, ['lax', 'strict', 'none'], true)) {
        $sameSite = 'lax';
    }
    session_name(Env::get('SESSION_NAME', 'dareo_jewels_lite'));
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => ucfirst($sameSite),
        'secure' => Env::bool('SESSION_SECURE', !empty($_SERVER['HTTPS'])),
    ]);
    session_start();
}

foreach (['Database', 'Money', 'Pricing', 'Report', 'Auth', 'Env'] as $class) {
    if (!class_exists($class, false)) {
        class_alias(__NAMESPACE__ . '\\' . $class, $class);
    }
}

Database::init();
