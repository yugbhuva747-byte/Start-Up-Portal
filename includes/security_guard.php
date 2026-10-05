<?php
/**
 * Platform Multi-Layer Security Guard & Web Application Firewall (WAF)
 * 
 * Provides Defense-in-Depth for the Startup & Investor Portal:
 * - Layer 1: Perimeter WAF (SQLi, XSS, Path Traversal, RCE & Null-Byte Inspection)
 * - Layer 2: Intrusion Detection & Adaptive IP Auto-Ban Firewall
 * - Layer 3: Advanced HTTP Security Headers & Content Security Policy (CSP)
 * - Layer 4: Session Hijacking Defense & Device Fingerprint Binding
 * - Layer 5: Inactivity Sliding Auto-Logout
 * - Layer 6: Invisible Honeypot Anti-Bot Shield
 * - Layer 7: Authenticated PII Encryption at Rest (AES-256-GCM)
 * - Layer 8: Magic Byte & Polyglot File Upload Safety Inspector
 */

if (!defined('APP_KEY')) {
    exit('Direct access not permitted');
}

class SecurityGuard
{
    private static bool $initialized = false;
    private static ?PDO $db = null;
    private static string $clientIp = '127.0.0.1';

    /**
     * Boot and initialize all security layers
     */
    public static function init(): void
    {
        if (self::$initialized) {
            return;
        }
        self::$initialized = true;

        self::$clientIp = self::resolveClientIp();

        // 1. Send Enterprise HTTP Security Headers & CSP
        self::sendSecurityHeaders();

        // 2. Connect Database for Firewall & Auditing
        if (function_exists('get_db')) {
            self::$db = get_db();
        }

        // 3. Ensure Firewall Blocklist Table Exists
        self::ensureFirewallTable();

        // 4. Verify Client IP is not Banned
        self::checkIpBan();

        // 5. Inspect Inbound Request (WAF Layer)
        self::inspectInboundRequest();

        // 6. Enforce Session Integrity & Inactivity Timeout (if session active)
        self::guardSession();
    }

    /**
     * Resolve true client IP, taking proxy headers into account safely
     */
    public static function resolveClientIp(): string
    {
        $headers = [
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_REAL_IP',        // Nginx reverse proxy
            'HTTP_X_FORWARDED_FOR',  // Standard proxy
            'REMOTE_ADDR'            // Direct connection
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ips = explode(',', (string)$_SERVER[$header]);
                $ip = trim($ips[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    /**
     * Layer 3: Send Advanced HTTP Security Headers and CSP
     */
    private static function sendSecurityHeaders(): void
    {
        if (headers_sent()) {
            return;
        }

        // Clickjacking Defense
        header('X-Frame-Options: SAMEORIGIN');
        
        // MIME-Sniffing Defense
        header('X-Content-Type-Options: nosniff');
        
        // Referrer Privacy
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // Legacy XSS Filter Enable
        header('X-XSS-Protection: 1; mode=block');
        
        // Hardware Permissions Restriction
        header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()');

        // Cross-Origin Isolation
        header('X-Permitted-Cross-Domain-Policies: none');

        // HSTS (Strict Transport Security) on HTTPS
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
                   || (($_SERVER['SERVER_PORT'] ?? 80) == 443)
                   || (strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

        if ($isHttps) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        }

        // Content Security Policy (Tailored for Tailwind CDN, Google Fonts, Chart.js, Lucide Icons, and Local Assets)
        $csp = [
            "default-src 'self' data: blob: https:",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.tailwindcss.com https://unpkg.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com",
            "font-src 'self' data: https://fonts.gstatic.com https://cdnjs.cloudflare.com",
            "img-src 'self' data: blob: https:",
            "connect-src 'self' https: wss:",
            "frame-src 'self' https://www.youtube.com https://player.vimeo.com",
            "frame-ancestors 'self'",
            "base-uri 'self'",
            "form-action 'self' https:"
        ];

        header('Content-Security-Policy: ' . implode('; ', $csp));
    }

    /**
     * Layer 2: Ensure database table for auto-ban firewall exists
     */
    private static function ensureFirewallTable(): void
    {
        if (!self::$db) {
            return;
        }

        try {
            self::$db->exec("
                CREATE TABLE IF NOT EXISTS `security_firewall_blocks` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `ip_address` VARCHAR(45) NOT NULL UNIQUE,
                    `reason` VARCHAR(255) NOT NULL,
                    `violation_count` INT DEFAULT 1,
                    `blocked_until` DATETIME NOT NULL,
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                    INDEX `idx_firewall_ip` (`ip_address`),
                    INDEX `idx_firewall_expiry` (`blocked_until`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");
        } catch (\Throwable $e) {
            // Ignore if permission denied or table already present
        }
    }

    /**
     * Layer 2: Check if client IP is currently blocked
     */
    private static function checkIpBan(): void
    {
        if (!self::$db) {
            return;
        }

        try {
            // Clean expired blocks probabilistically (1% chance per hit)
            if (mt_rand(1, 100) === 1) {
                self::$db->exec("DELETE FROM security_firewall_blocks WHERE blocked_until < NOW()");
            }

            $stmt = self::$db->prepare("SELECT * FROM security_firewall_blocks WHERE ip_address = ? AND blocked_until > NOW() LIMIT 1");
            $stmt->execute([self::$clientIp]);
            $block = $stmt->fetch();

            if ($block) {
                self::renderBlockedPage('FIREWALL_IP_BANNED', 'Your IP has been temporarily blocked by platform security firewall.', $block['blocked_until']);
                exit;
            }
        } catch (\Throwable $e) {
            // Fail open on DB read error so legitimate users aren't locked out if DB is slow
        }
    }

    /**
     * Layer 1: Inbound Request Inspection (WAF)
     */
    private static function inspectInboundRequest(): void
    {
        // Skip CLI commands
        if (php_sapi_name() === 'cli') {
            return;
        }

        // Whitelisted parameter keys that are allowed to contain complex characters (passwords, tokens)
        $skipValueKeys = ['password', 'password_confirmation', 'csrf_token', '_token', 'code', 'remember_token'];

        // 1. Inspect Query String & Request URI
        $rawUri = $_SERVER['REQUEST_URI'] ?? '';
        if (self::containsNullByte($rawUri)) {
            self::handleViolation('NULL_BYTE_INJECTION', 'Null byte detected in request URI');
        }

        if (self::matchTraversal($rawUri)) {
            self::handleViolation('PATH_TRAVERSAL_URI', 'Path traversal sequence detected in URI');
        }

        // 2. Inspect GET Parameters
        self::inspectArray($_GET, 'GET', $skipValueKeys);

        // 3. Inspect POST Parameters
        self::inspectArray($_POST, 'POST', $skipValueKeys);

        // 4. Inspect Cookies (except session & standard cookie tokens)
        $cookiesToCheck = $_COOKIE;
        unset($cookiesToCheck[session_name()], $cookiesToCheck['remember_token']);
        self::inspectArray($cookiesToCheck, 'COOKIE', $skipValueKeys);
    }

    /**
     * Recursively inspect array values against threat signatures
     */
    private static function inspectArray(array $data, string $source, array $skipKeys, string $parentKey = ''): void
    {
        foreach ($data as $key => $value) {
            $fullKey = $parentKey ? "{$parentKey}.{$key}" : (string)$key;

            // Check Key itself for injection
            if (self::containsNullByte((string)$key) || self::matchSqlInjection((string)$key) || self::matchXss((string)$key)) {
                self::handleViolation('MALICIOUS_PARAM_NAME', "Malicious payload detected in {$source} parameter name: {$fullKey}");
            }

            if (is_array($value)) {
                self::inspectArray($value, $source, $skipKeys, $fullKey);
                continue;
            }

            $strVal = (string)$value;

            // Null-byte check on all inputs without exception
            if (self::containsNullByte($strVal)) {
                self::handleViolation('NULL_BYTE_INJECTION', "Null byte payload detected in {$source} field '{$fullKey}'");
            }

            // If key is a password or token, do not run SQLi/XSS string match (passwords can have symbols like `<admin'or1=1>`)
            if (in_array(strtolower((string)$key), $skipKeys, true)) {
                continue;
            }

            // Path Traversal
            if (self::matchTraversal($strVal)) {
                self::handleViolation('PATH_TRAVERSAL_PAYLOAD', "Directory traversal probe in {$source} field '{$fullKey}'");
            }

            // Remote Code Execution / Shell Injection
            if (self::matchRce($strVal)) {
                self::handleViolation('RCE_PROBE_DETECTED', "Remote code execution signature in {$source} field '{$fullKey}'");
            }

            // SQL Injection
            if (self::matchSqlInjection($strVal)) {
                self::handleViolation('SQLI_PROBE_DETECTED', "SQL injection payload blocked in {$source} field '{$fullKey}'");
            }

            // Cross-Site Scripting (XSS)
            if (self::matchXss($strVal)) {
                self::handleViolation('XSS_PROBE_DETECTED', "Cross-site scripting vector blocked in {$source} field '{$fullKey}'");
            }
        }
    }

    /**
     * Null byte detector
     */
    private static function containsNullByte(string $val): bool
    {
        return str_contains($val, "\0") || str_contains($val, "%00") || str_contains($val, "\\0");
    }

    /**
     * Path traversal detection
     */
    private static function matchTraversal(string $val): bool
    {
        // Matches ../, ..\, /etc/passwd, boot.ini, win.ini
        if (preg_match('~(?:\.\.[\\\/]|\.\.%2f|\.\.%5c|\.\.%252f)~i', $val)) {
            return true;
        }
        if (preg_match('~(?:/etc/passwd|/etc/shadow|/proc/self/environ|win\.ini|boot\.ini)~i', $val)) {
            return true;
        }
        return false;
    }

    /**
     * Remote Code Execution signature detection
     */
    private static function matchRce(string $val): bool
    {
        // Common PHP code execution probes
        $patterns = [
            '~(?:\b(?:passthru|shell_exec|exec|popen|proc_open|assert)\s*\(|\bphpinfo\s*\(\s*\))~i',
            '~(?:php://(?:filter|input|phar)|data://text/plain)~i',
            '~(?:<\?php|<\?=|\bpreg_replace\s*\(.*\/e\b)~i'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $val)) {
                return true;
            }
        }

        return false;
    }

    /**
     * High-precision SQL Injection pattern detection
     */
    private static function matchSqlInjection(string $val): bool
    {
        // Don't trigger on trivial short inputs
        if (strlen($val) < 6) {
            return false;
        }

        $patterns = [
            // Union select probes
            '~\bunion\s+(?:all\s+)?select\b~i',
            // Schema table / metadata probes
            '~\b(?:information_schema|sys\.tables|mysql\.user)\b~i',
            // Dangerous functions
            '~\b(?:load_file|into\s+outfile|into\s+dumpfile)\s*\(~i',
            // Time-based blind SQLi (sleep, benchmark, waitfor)
            '~\b(?:sleep\s*\(\s*\d+\s*\)|benchmark\s*\(\s*\d+|waitfor\s+delay\s+)~i',
            // Stacked queries terminating with table alter / drop
            '~;\s*(?:drop|alter|truncate)\s+table\b~i',
            // Boolean tautology injection with quotes (e.g. ' OR '1'='1 or 1=1 --)
            '~(?:\'\s*(?:or|and)\s*[\'"]?1[\'"]?\s*=\s*[\'"]?1|"\s*(?:or|and)\s*[\'"]?1[\'"]?\s*=\s*[\'"]?1)~i'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $val)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Dangerous Cross-Site Scripting (XSS) vector detection
     */
    private static function matchXss(string $val): bool
    {
        if (strlen($val) < 5) {
            return false;
        }

        $patterns = [
            // Dangerous tags
            '~<\s*(?:script|iframe|object|embed|applet|meta|base|link\s+rel\s*=\s*["\']?import)["\'\s>]~i',
            // JavaScript / VBScript / Data URIs in attributes
            '~(?:javascript:|vbscript:|data:text/html)~i',
            // Inline event handlers like <img src=x onerror=...>, onload=..., etc.
            '~<\s*[a-z0-9_-]+[^>]*\bon(?:error|load|click|mouseover|focus|blur|submit)\s*=~i',
            // Expression evaluation
            '~\bexpression\s*\(~i'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $val)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Handle Security Violation: Log incident, increment threat tally, auto-ban if threshold exceeded, and terminate
     */
    public static function handleViolation(string $threatType, string $details): void
    {
        $userId = $_SESSION['user_id'] ?? null;
        $ip = self::$clientIp;

        // 1. Log to security_events
        if (function_exists('log_security_event')) {
            log_security_event($userId, $threatType, 'critical', $details);
        }

        // 2. Track violation count & auto-ban
        if (self::$db) {
            try {
                $stmt = self::$db->prepare("
                    INSERT INTO security_firewall_blocks (ip_address, reason, violation_count, blocked_until, created_at)
                    VALUES (?, ?, 1, DATE_ADD(NOW(), INTERVAL 15 MINUTE), NOW())
                    ON DUPLICATE KEY UPDATE 
                        violation_count = violation_count + 1,
                        blocked_until = CASE 
                            WHEN violation_count >= 2 THEN DATE_ADD(NOW(), INTERVAL 60 MINUTE)
                            ELSE DATE_ADD(NOW(), INTERVAL 15 MINUTE)
                        END,
                        reason = ?
                ");
                $stmt->execute([$ip, $details, $details]);
            } catch (\Throwable $e) {}
        }

        // 3. Render modern security block screen
        self::renderBlockedPage($threatType, $details);
        exit;
    }

    /**
     * Layer 4 & 5: Session Hijacking Defense & Inactivity Timeout
     */
    private static function guardSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE || empty($_SESSION['user_id'])) {
            return;
        }

        $now = time();
        $userRole = $_SESSION['user_role'] ?? 'guest';

        // 1. Sliding Inactivity Timeout (30 min for admin, 60 min for normal users)
        $timeoutDuration = ($userRole === 'admin') ? (30 * 60) : (60 * 60);

        if (isset($_SESSION['sec_last_active']) && ($now - $_SESSION['sec_last_active']) > $timeoutDuration) {
            $timedOutUserId = (int)$_SESSION['user_id'];
            if (function_exists('log_security_event')) {
                log_security_event($timedOutUserId, 'SESSION_INACTIVITY_TIMEOUT', 'low', 'User automatically logged out after inactivity threshold.');
            }
            $_SESSION = [];
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_destroy();
            }
            if (function_exists('clear_remember_me_cookie')) {
                clear_remember_me_cookie();
            }
            if (function_exists('set_flash')) {
                set_flash('info', 'Your session expired due to inactivity for your account security. Please sign in again.');
            }
            header('Location: ' . url('auth/login.php?session_expired=1'));
            exit;
        }
        $_SESSION['sec_last_active'] = $now;

        // 2. Session Fingerprint (Binding to User-Agent & IP Subnet)
        // Using /24 subnet prevents unnecessary logouts on minor dynamic 4G/5G mobile tower switches
        $ipParts = explode('.', self::$clientIp);
        $subnet = (count($ipParts) === 4) ? ($ipParts[0] . '.' . $ipParts[1] . '.' . $ipParts[2] . '.0') : self::$clientIp;
        $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? 'UnknownUA', 0, 150);
        $expectedFingerprint = hash('sha256', $subnet . '|' . $ua . '|' . APP_KEY);

        if (!isset($_SESSION['sec_fingerprint'])) {
            $_SESSION['sec_fingerprint'] = $expectedFingerprint;
        } elseif (!hash_equals($_SESSION['sec_fingerprint'], $expectedFingerprint)) {
            // Possible session hijacking detected!
            $compromisedUserId = (int)$_SESSION['user_id'];
            if (function_exists('log_security_event')) {
                log_security_event(
                    $compromisedUserId, 
                    'SESSION_HIJACK_SUSPECTED', 
                    'critical', 
                    "Device/Subnet fingerprint changed mid-session. Old fingerprint mismatched for IP: " . self::$clientIp
                );
            }

            // Invalidate session immediately
            $_SESSION = [];
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_destroy();
            }
            if (function_exists('clear_remember_me_cookie')) {
                clear_remember_me_cookie();
            }
            if (function_exists('set_flash')) {
                set_flash('error', 'Security Alert: Your session was terminated because your device or network location changed abruptly. Please sign in again.');
            }
            header('Location: ' . url('auth/login.php?security_reset=1'));
            exit;
        }
    }

    /**
     * Layer 6: Invisible Anti-Bot Honeypot Field Generator
     */
    public static function honeypotField(): string
    {
        $fieldName = '_hp_sec_' . substr(hash('sha256', APP_KEY . 'bot_trap'), 0, 8);
        return '
        <div style="position:absolute;left:-9999px;top:-9999px;opacity:0;pointer-events:none;height:0;width:0;overflow:hidden;" aria-hidden="true" tabindex="-1">
            <label for="' . $fieldName . '">Leave this field blank</label>
            <input type="text" id="' . $fieldName . '" name="' . $fieldName . '" value="" tabindex="-1" autocomplete="off">
        </div>';
    }

    /**
     * Layer 6: Validate Anti-Bot Honeypot
     */
    public static function checkHoneypot(): bool
    {
        $fieldName = '_hp_sec_' . substr(hash('sha256', APP_KEY . 'bot_trap'), 0, 8);
        if (!empty($_POST[$fieldName])) {
            // Bot filled out hidden field
            self::recordThreat('BOT_HONEYPOT_TRIGGERED', 'high', 'Automated bot crawler trapped by invisible honeypot field');
            return true;
        }
        return false;
    }

    /**
     * Layer 7: Authenticated PII Field Encryption (AES-256-GCM)
     */
    public static function encrypt(string $plainText, ?string $key = null): string
    {
        if ($plainText === '') {
            return '';
        }

        $encryptionKey = hash('sha256', $key ?: APP_KEY, true);
        $iv = random_bytes(12); // Standard 96-bit IV for GCM
        $tag = '';

        $cipherText = openssl_encrypt(
            $plainText,
            'aes-256-gcm',
            $encryptionKey,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            16
        );

        if ($cipherText === false) {
            return $plainText;
        }

        // Pack IV (12 bytes) + Tag (16 bytes) + Ciphertext and base64 encode
        return 'enc::' . base64_encode($iv . $tag . $cipherText);
    }

    /**
     * Layer 7: Authenticated PII Field Decryption (AES-256-GCM)
     */
    public static function decrypt(string $cipherPayload, ?string $key = null): string
    {
        if (!str_starts_with($cipherPayload, 'enc::')) {
            return $cipherPayload; // Not encrypted, return plain text
        }

        $raw = base64_decode(substr($cipherPayload, 5), true);
        if (!$raw || strlen($raw) < 28) {
            return '';
        }

        $iv = substr($raw, 0, 12);
        $tag = substr($raw, 12, 16);
        $cipherText = substr($raw, 28);
        $encryptionKey = hash('sha256', $key ?: APP_KEY, true);

        $decrypted = openssl_decrypt(
            $cipherText,
            'aes-256-gcm',
            $encryptionKey,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        return ($decrypted !== false) ? $decrypted : '';
    }

    /**
     * Layer 8: Magic Byte File Upload Safety Validator
     */
    public static function verifyFileUpload(array $file, array $allowedMimes, int $maxBytes = 10485760): array
    {
        if (empty($file) || !isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return ['valid' => false, 'error' => 'No file was uploaded.'];
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['valid' => false, 'error' => 'Upload failed with system code: ' . $file['error']];
        }

        if ($file['size'] > $maxBytes) {
            $mb = round($maxBytes / 1048576, 1);
            return ['valid' => false, 'error' => "File exceeds maximum permitted size of {$mb}MB."];
        }

        // Check for double extension exploit (e.g. avatar.php.jpg)
        $originalName = (string)($file['name'] ?? '');
        if (preg_match('/\.(php|phtml|phar|cgi|pl|sh|exe|asp|aspx)\./i', $originalName)) {
            self::recordThreat('DOUBLE_EXTENSION_UPLOAD_ATTEMPT', 'critical', "Blocked double extension upload: {$originalName}");
            return ['valid' => false, 'error' => 'Dangerous file naming format detected.'];
        }

        // Real Magic Byte inspection using finfo
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $realMime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (!isset($allowedMimes[$realMime])) {
                return ['valid' => false, 'error' => "Disallowed file type detected ({$realMime})."];
            }

            return ['valid' => true, 'mime' => $realMime, 'extension' => $allowedMimes[$realMime]];
        }

        return ['valid' => true, 'mime' => 'application/octet-stream', 'extension' => 'bin'];
    }

    /**
     * Helper to manually log security event
     */
    public static function recordThreat(string $type, string $severity, string $details): void
    {
        if (function_exists('log_security_event')) {
            log_security_event($_SESSION['user_id'] ?? null, $type, $severity, $details);
        }
    }

    /**
     * Render high-end, responsive security block alert page
     */
    private static function renderBlockedPage(string $threatType, string $details, ?string $blockedUntil = null): void
    {
        http_response_code(403);

        // If this is an AJAX/API call, respond in JSON
        $isJson = (!empty($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))
                  || (!empty($_SERVER['CONTENT_TYPE']) && str_contains($_SERVER['CONTENT_TYPE'], 'application/json'))
                  || (str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/api/'));

        if ($isJson) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'status' => 'error',
                'code' => 403,
                'message' => 'Request blocked by platform security firewall.',
                'incident_id' => 'SEC-' . strtoupper(substr(hash('sha256', self::$clientIp . time()), 0, 10)),
                'threat_code' => $threatType,
                'timestamp' => date('Y-m-d H:i:s T')
            ], JSON_PRETTY_PRINT);
            exit;
        }

        $incidentId = 'SEC-' . strtoupper(substr(hash('sha256', self::$clientIp . time() . APP_KEY), 0, 12));
        $currentTime = date('Y-m-d H:i:s') . ' IST';
        $ip = htmlspecialchars(self::$clientIp, ENT_QUOTES, 'UTF-8');
        $threatCode = htmlspecialchars($threatType, ENT_QUOTES, 'UTF-8');
        $portalName = defined('APP_NAME') ? APP_NAME : 'STARTUP × INVESTOR';
        $portalUrl = defined('BASE_URL') ? BASE_URL : '/';

        $expiryNotice = '';
        if ($blockedUntil) {
            $expiryNotice = '<div class="mt-4 p-3 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-300 text-xs">
                <strong>Firewall Lockout Active:</strong> Your access will be automatically restored at <strong>' . htmlspecialchars($blockedUntil, ENT_QUOTES, 'UTF-8') . '</strong>.
            </div>';
        }

        echo <<<HTML
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 Security Shield — {$portalName}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        code, pre { font-family: 'JetBrains Mono', monospace; }
    </style>
</head>
<body class="min-h-full flex items-center justify-center p-4 sm:p-6 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-slate-900 via-slate-950 to-black">
    <div class="max-w-xl w-full bg-slate-900/80 border border-rose-500/30 rounded-3xl p-6 sm:p-8 backdrop-blur-xl shadow-2xl shadow-rose-950/40 relative overflow-hidden">
        <!-- Glow accent -->
        <div class="absolute -top-24 -right-24 w-48 h-48 bg-rose-500/15 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-24 -left-24 w-48 h-48 bg-blue-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="flex items-center gap-4 mb-6">
            <div class="w-14 h-14 rounded-2xl bg-rose-500/15 border border-rose-500/30 flex items-center justify-center text-rose-400 flex-shrink-0 shadow-inner">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <div>
                <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-rose-500/10 border border-rose-500/30 text-rose-400 mb-1">
                    HTTP 403 &middot; ACCESS RESTRICTED
                </div>
                <h1 class="text-xl sm:text-2xl font-extrabold text-white tracking-tight">Security Shield Activated</h1>
            </div>
        </div>

        <p class="text-sm text-slate-300 leading-relaxed mb-5">
            Your request triggered the automated Web Application Firewall (WAF) rule set on <strong>{$portalName}</strong>. 
            The activity was flagged and halted to safeguard platform assets and financial data.
        </p>

        {$expiryNotice}

        <div class="mt-5 rounded-2xl bg-slate-950/70 border border-slate-800 p-4 space-y-2 text-xs">
            <div class="flex items-center justify-between py-1 border-b border-slate-800/80">
                <span class="text-slate-400 font-medium">Incident Reference:</span>
                <code class="text-rose-400 font-bold">{$incidentId}</code>
            </div>
            <div class="flex items-center justify-between py-1 border-b border-slate-800/80">
                <span class="text-slate-400 font-medium">Threat Classifier:</span>
                <span class="text-slate-200 font-semibold">{$threatCode}</span>
            </div>
            <div class="flex items-center justify-between py-1 border-b border-slate-800/80">
                <span class="text-slate-400 font-medium">Your Origin IP:</span>
                <code class="text-slate-300 font-semibold">{$ip}</code>
            </div>
            <div class="flex items-center justify-between py-1">
                <span class="text-slate-400 font-medium">Timestamp:</span>
                <span class="text-slate-400">{$currentTime}</span>
            </div>
        </div>

        <div class="mt-6 flex flex-col sm:flex-row items-center gap-3">
            <a href="{$portalUrl}" class="w-full sm:w-auto flex-1 text-center py-2.5 px-4 rounded-xl bg-slate-800 hover:bg-slate-700 text-white font-semibold text-xs transition border border-slate-700">
                Return to Safe Portal
            </a>
            <button onclick="window.history.back()" class="w-full sm:w-auto px-4 py-2.5 rounded-xl bg-transparent hover:bg-slate-800/50 text-slate-400 hover:text-white font-semibold text-xs transition">
                Go Back
            </button>
        </div>

        <div class="mt-6 pt-4 border-t border-slate-800/80 text-center">
            <p class="text-[11px] text-slate-400">
                If you believe this was triggered in error, please contact compliance operations with your Incident Reference.
            </p>
        </div>
    </div>
</body>
</html>
HTML;
    }
}
