<?php
/**
 * Application Global Configuration
 * Automatically loads .env and defines standard constants & session setup
 */

// Set Global Timezone (IST - India / Asia/Kolkata)
date_default_timezone_set('Asia/Kolkata');

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

// Support Cloud / Docker DATABASE_URL if present (e.g. mysql://user:pass@host:port/dbname)
$databaseUrl = $env['DATABASE_URL'] ?? (getenv('DATABASE_URL') ?: '');
if (!empty($databaseUrl)) {
    $parsed = parse_url($databaseUrl);
    if (!empty($parsed['host'])) {
        $env['DB_HOST'] = $parsed['host'];
        $env['DB_PORT'] = (string)($parsed['port'] ?? 3306);
        $env['DB_USER'] = $parsed['user'] ?? 'root';
        $env['DB_PASS'] = $parsed['pass'] ?? '';
        $env['DB_NAME'] = ltrim($parsed['path'] ?? '', '/');
    }
}

// Database Configuration Constants with Smart Defaults
define('DB_HOST', $env['DB_HOST'] ?? (getenv('DB_HOST') ?: '127.0.0.1'));
define('DB_PORT', (string)($env['DB_PORT'] ?? (getenv('DB_PORT') ?: '3306')));
define('DB_NAME', $env['DB_NAME'] ?? (getenv('DB_NAME') ?: 'startup_portal'));
define('DB_USER', $env['DB_USER'] ?? (getenv('DB_USER') ?: 'root'));
define('DB_PASS', $env['DB_PASS'] ?? (getenv('DB_PASS') ?: ''));
define('DB_SOCKET', $env['DB_SOCKET'] ?? (getenv('DB_SOCKET') ?: ''));

// Security Key & App Name
define('APP_KEY', $env['APP_KEY'] ?? (getenv('APP_KEY') ?: 'sec_key_startup_hub_9876543210_portal'));
define('APP_NAME', $env['APP_NAME'] ?? (getenv('APP_NAME') ?: 'STARTUP × INVESTOR'));
define('APP_ENV', $env['APP_ENV'] ?? (getenv('APP_ENV') ?: 'development'));

// -----------------------------------------------------------------------------
// Dynamic Environment & Base URL Resolution
// -----------------------------------------------------------------------------
// Determine if running on localhost or on a live production domain
$httpHost = strtolower(trim($_SERVER['HTTP_HOST'] ?? 'localhost'));
$hostWithoutPort = explode(':', $httpHost)[0];
$isLocalhost = in_array($hostWithoutPort, ['localhost', '127.0.0.1', '::1'])
               || str_starts_with($hostWithoutPort, '192.168.')
               || str_starts_with($hostWithoutPort, '10.')
               || str_ends_with($hostWithoutPort, '.local')
               || str_ends_with($hostWithoutPort, '.test');

// Protocol detection (supports HTTPS, SSL termination, Cloudflare, AWS ALB)
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
           || (($_SERVER['SERVER_PORT'] ?? 80) == 443)
           || (strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
           || (isset($_SERVER['HTTP_CF_VISITOR']) && str_contains($_SERVER['HTTP_CF_VISITOR'], '"https"'));
$protocol = $isHttps ? "https://" : "http://";

// Calculate relative path from web server DOCUMENT_ROOT
$docRoot = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: ($_SERVER['DOCUMENT_ROOT'] ?? ''));
$currentDir = str_replace('\\', '/', realpath(__DIR__) ?: __DIR__);

if (!empty($docRoot) && strpos($currentDir, $docRoot) === 0) {
    $relativeAppPath = substr($currentDir, strlen($docRoot));
} else {
    $relativeAppPath = str_ireplace($docRoot, '', $currentDir);
}
$relativeAppPath = '/' . ltrim(str_replace('\\', '/', $relativeAppPath), '/');
if ($relativeAppPath === '/') {
    $relativeAppPath = '';
}

// Calculate final BASE_URL:
// If APP_URL is specified in .env, check if it's safe to use:
// If running on a live server, but APP_URL has 'localhost', ignore localhost and auto-adapt to live domain!
$configuredUrl = trim($env['APP_URL'] ?? (getenv('APP_URL') ?: ''));
if (!empty($configuredUrl) && (!$isLocalhost && (str_contains($configuredUrl, 'localhost') || str_contains($configuredUrl, '127.0.0.1')))) {
    // Zero-Touch Live Adaptation: Automatically use the live domain instead of broken localhost
    $baseUrl = rtrim($protocol . $httpHost . $relativeAppPath, '/');
} elseif (!empty($configuredUrl)) {
    $baseUrl = rtrim($configuredUrl, '/');
} else {
    $baseUrl = rtrim($protocol . $httpHost . $relativeAppPath, '/');
}

define('BASE_URL', $baseUrl);
define('ROOT_PATH', __DIR__);
define('IS_LOCALHOST', $isLocalhost);

// Mail Configuration
define('MAIL_MAILER', $env['MAIL_MAILER'] ?? 'mail');
define('MAIL_HOST', $env['MAIL_HOST'] ?? ($env['SMTP_HOST'] ?? ''));
define('MAIL_PORT', (int)($env['MAIL_PORT'] ?? ($env['SMTP_PORT'] ?? 587)));
define('MAIL_USERNAME', $env['MAIL_USERNAME'] ?? ($env['SMTP_USER'] ?? ''));
define('MAIL_PASSWORD', $env['MAIL_PASSWORD'] ?? ($env['SMTP_PASS'] ?? ''));
define('MAIL_ENCRYPTION', $env['MAIL_ENCRYPTION'] ?? 'tls');
define('MAIL_FROM_ADDRESS', $env['MAIL_FROM_ADDRESS'] ?? 'notifications@startupportal.com');
define('MAIL_FROM_NAME', $env['MAIL_FROM_NAME'] ?? APP_NAME);

// EmailJS Service Configuration (Client-side & Server API)
define('EMAILJS_SERVICE_ID', $env['EMAILJS_SERVICE_ID'] ?? (getenv('EMAILJS_SERVICE_ID') ?: 'service_vhn18xd'));
define('EMAILJS_TEMPLATE_ID', $env['EMAILJS_TEMPLATE_ID'] ?? (getenv('EMAILJS_TEMPLATE_ID') ?: 'template_fpdqjwe'));
define('EMAILJS_PUBLIC_KEY', $env['EMAILJS_PUBLIC_KEY'] ?? (getenv('EMAILJS_PUBLIC_KEY') ?: 'H5UX1F22-jBkc8RZP'));

// Include database, helpers & mailer
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/security_guard.php';
require_once __DIR__ . '/includes/mailer.php';

// Boot Multi-Layer Security Guard & WAF
SecurityGuard::init();

// Auto-restore session from persistent remember-me cookie if not logged in
if (empty($_SESSION['user_id']) && !empty($_COOKIE['remember_token'])) {
    check_remember_me_cookie();
}

// Enforce global maintenance mode across all public and non-admin routes
if (function_exists('enforce_maintenance_mode')) {
    enforce_maintenance_mode();
}

