<?php
/**
 * Application Global Configuration
 * Automatically loads .env and defines standard constants & session setup
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
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
$relativeAppPath = str_replace($docRoot, '', $currentDir);
$relativeAppPath = '/' . ltrim($relativeAppPath, '/');

if (!empty($env['APP_URL'])) {
    $baseUrl = rtrim($env['APP_URL'], '/');
} else {
    $baseUrl = rtrim($protocol . $host . $relativeAppPath, '/');
}

define('BASE_URL', $baseUrl);
define('ROOT_PATH', __DIR__);

// Include database & helpers
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
