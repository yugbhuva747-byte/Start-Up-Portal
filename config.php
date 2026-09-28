<?php
/**
 * Application Global Configuration
 * Automatically loads .env and defines standard constants & session setup
 */

// 1. Secure Session Cookie Configuration
if (session_status() === PHP_SESSION_NONE) {
    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_trans_sid', '0');
    ini_set('session.cookie_httponly', '1');

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start();
}

// 2. Global HTTP Security Headers
if (!headers_sent()) {
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('X-XSS-Protection: 1; mode=block');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

// Helper to load .env
$envFile = __DIR__ . '/.env';
$env = [];
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $val) = explode('=', $line, 2);
            $key = trim($key);
            $val = trim($val, " \t\n\r\0\x0B\"'");
            $env[$key] = $val;
            $_ENV[$key] = $val;
            putenv("$key=$val");
        }
    }
}

// Database Constants
define('DB_HOST', $env['DB_HOST'] ?? '127.0.0.1');
define('DB_PORT', $env['DB_PORT'] ?? '3306');
define('DB_NAME', $env['DB_NAME'] ?? 'startup_portal');
define('DB_USER', $env['DB_USER'] ?? 'root');
define('DB_PASS', $env['DB_PASS'] ?? '');

// Security Key for Hash IDs & Encryption
define('APP_KEY', $env['APP_KEY'] ?? 'sec_key_startup_hub_9876543210_portal');
define('APP_NAME', $env['APP_NAME'] ?? 'STARTUP × INVESTOR');

// Calculate dynamic Base URL
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || ($_SERVER['SERVER_PORT'] ?? 80) == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));

// If within a subfolder like /start up portal/founder, extract root
$docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? '');
$currentDir = str_replace('\\', '/', __DIR__);
$relativeAppPath = str_ireplace($docRoot, '', $currentDir);
$relativeAppPath = '/' . ltrim($relativeAppPath, '/');

if (!empty($env['APP_URL'])) {
    $baseUrl = rtrim($env['APP_URL'], '/');
} else {
    $baseUrl = rtrim($protocol . $host . $relativeAppPath, '/');
}

define('BASE_URL', $baseUrl);
define('ROOT_PATH', __DIR__);

// Mail Configuration
define('MAIL_MAILER', $env['MAIL_MAILER'] ?? 'mail');
define('MAIL_HOST', $env['MAIL_HOST'] ?? ($env['SMTP_HOST'] ?? ''));
define('MAIL_PORT', (int)($env['MAIL_PORT'] ?? ($env['SMTP_PORT'] ?? 587)));
define('MAIL_USERNAME', $env['MAIL_USERNAME'] ?? ($env['SMTP_USER'] ?? ''));
define('MAIL_PASSWORD', $env['MAIL_PASSWORD'] ?? ($env['SMTP_PASS'] ?? ''));
define('MAIL_ENCRYPTION', $env['MAIL_ENCRYPTION'] ?? 'tls');
define('MAIL_FROM_ADDRESS', $env['MAIL_FROM_ADDRESS'] ?? 'notifications@startupportal.com');
define('MAIL_FROM_NAME', $env['MAIL_FROM_NAME'] ?? APP_NAME);

// Include database, helpers & mailer
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/mailer.php';
