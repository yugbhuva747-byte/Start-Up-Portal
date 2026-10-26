<?php
/**
 * Global Helper Functions
 * Includes Hash ID, Authentication, Audit Logging, Flash Messages, and Sanitization
 */

// 1. Dynamic URL Generator (Clean URLs without .php extension)
function url(string $path = ''): string
{
    $cleanPath = ltrim($path, '/');
    if ($cleanPath === '') {
        return BASE_URL;
    }

    // Extract fragment (#) if present
    $fragment = '';
    if (str_contains($cleanPath, '#')) {
        list($cleanPath, $fragment) = explode('#', $cleanPath, 2);
        $fragment = '#' . $fragment;
    }

    // Extract query string (?) if present
    $query = '';
    if (str_contains($cleanPath, '?')) {
        list($cleanPath, $query) = explode('?', $cleanPath, 2);
        $query = '?' . $query;
    }

    // Strip .php extension if present (preserving non-php files like .css, .js, .pdf, images)
    if (str_ends_with(strtolower($cleanPath), '.php')) {
        $cleanPath = substr($cleanPath, 0, -4);
        if ($cleanPath === 'index') {
            $cleanPath = '';
        }
    }

    if ($cleanPath === '') {
        return BASE_URL . ($query || $fragment ? '/' : '') . $query . $fragment;
    }

    return BASE_URL . '/' . $cleanPath . $query . $fragment;
}

// 1.1 Asset URL Generator
function asset(string $path = ''): string {
    return BASE_URL . '/assets/' . ltrim($path, '/');
}

// 1.2 Global Safe Redirection Helper
function redirect(string $path, int $status = 302): void {
    if (!preg_match('#^https?://#i', $path)) {
        $path = url($path);
    }
    header('Location: ' . $path, true, $status);
    exit;
}

// 2. Hash ID Encoding & Decoding (Reversible, URL-safe, secure obfuscation)
function hash_id_encode(int|string|null $id): string
{
    if (empty($id))
        return '';
    $id = (int) $id;
    $key = APP_KEY;
    $salt = substr(hash('sha256', $key . $id), 0, 6);
    $payload = base64_encode($id . ':' . $salt);
    return str_replace(['+', '/', '='], ['-', '_', ''], $payload);
}

function hash_id_decode(?string $hash): int
{
    if (empty($hash))
        return 0;
    $b64 = str_replace(['-', '_'], ['+', '/'], $hash);
    $mod4 = strlen($b64) % 4;
    if ($mod4) {
        $b64 .= substr('====', $mod4);
    }
    $decoded = base64_decode($b64, true);
    if (!$decoded || !str_contains($decoded, ':'))
        return 0;

    list($id, $salt) = explode(':', $decoded, 2);
    $id = (int) $id;
    $expectedSalt = substr(hash('sha256', APP_KEY . $id), 0, 6);
    if (hash_equals($expectedSalt, $salt)) {
        return $id;
    }
    return 0;
}

function encode_id(int|string|null $id): string
{
    return hash_id_encode($id);
}

function decode_id(?string $hash): int
{
    if (empty($hash)) {
        return 0;
    }
    if (is_numeric($hash) && (int)$hash > 0) {
        return (int)$hash;
    }
    return hash_id_decode($hash);
}

// 3. Authentication & Persistent Cookie Sessions
function set_remember_me_cookie(int $userId, int $days = 30): bool {
    $db = get_db();
    if (!$db || $userId <= 0) return false;

    try {
        // Generate secure 128-bit selector and 256-bit validator
        $selector = bin2hex(random_bytes(16));
        $validator = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $validator);
        $expiresAt = date('Y-m-d H:i:s', time() + ($days * 86400));
        $clientIp = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $userAgent = substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 255);

        // Delete any existing tokens for this user on this IP to avoid stale duplicate rows
        $db->prepare("DELETE FROM remember_tokens WHERE user_id = ? AND ip_address = ?")->execute([$userId, $clientIp]);

        $stmt = $db->prepare("
            INSERT INTO remember_tokens (user_id, selector, token_hash, ip_address, user_agent, expires_at, created_at, last_used_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([$userId, $selector, $tokenHash, $clientIp, $userAgent, $expiresAt]);

        $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
        $cookieValue = $selector . ':' . $validator;

        return setcookie('remember_token', $cookieValue, [
            'expires'  => time() + ($days * 86400),
            'path'     => '/',
            'domain'   => '',
            'secure'   => $isSecure,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    } catch (Exception $e) {
        return false;
    }
}

function check_remember_me_cookie(): ?array {
    // If already active in PHP session, return current user
    if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
        return current_user();
    }

    if (empty($_COOKIE['remember_token'])) {
        return null;
    }

    $cookieVal = (string)$_COOKIE['remember_token'];
    $parts = explode(':', $cookieVal, 2);
    if (count($parts) !== 2 || strlen($parts[0]) !== 32 || strlen($parts[1]) !== 64) {
        clear_remember_me_cookie();
        return null;
    }

    $selector = $parts[0];
    $validator = $parts[1];

    $db = get_db();
    if (!$db) return null;

    try {
        $stmt = $db->prepare("SELECT * FROM remember_tokens WHERE selector = ? AND expires_at > NOW() LIMIT 1");
        $stmt->execute([$selector]);
        $tokenRow = $stmt->fetch();

        if (!$tokenRow) {
            clear_remember_me_cookie();
            return null;
        }

        // Constant-time hash verification
        $calculatedHash = hash('sha256', $validator);
        if (!hash_equals($tokenRow['token_hash'], $calculatedHash)) {
            // Compromised token detected! Invalidate this selector and clear cookie
            $db->prepare("DELETE FROM remember_tokens WHERE selector = ?")->execute([$selector]);
            clear_remember_me_cookie();
            log_security_event($tokenRow['user_id'] ?? null, 'REMEMBER_TOKEN_TAMPERED', 'high', 'Invalid remember token validator detected');
            return null;
        }

        // Fetch user record
        $uStmt = $db->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
        $uStmt->execute([$tokenRow['user_id']]);
        $user = $uStmt->fetch();

        if (!$user || $user['status'] === 'suspended') {
            $db->prepare("DELETE FROM remember_tokens WHERE selector = ?")->execute([$selector]);
            clear_remember_me_cookie();
            return null;
        }

        // Token is valid! Rotate validator to prevent replay attacks
        $newValidator = bin2hex(random_bytes(32));
        $newTokenHash = hash('sha256', $newValidator);
        $newExpiry = date('Y-m-d H:i:s', time() + (30 * 86400));

        $db->prepare("UPDATE remember_tokens SET token_hash = ?, expires_at = ?, last_used_at = NOW() WHERE id = ?")
           ->execute([$newTokenHash, $newExpiry, $tokenRow['id']]);

        $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
        setcookie('remember_token', $selector . ':' . $newValidator, [
            'expires'  => time() + (30 * 86400),
            'path'     => '/',
            'domain'   => '',
            'secure'   => $isSecure,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);

        // Restore authenticated PHP session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['is_remembered'] = true;

        if ($user['role'] === 'admin') {
            $_SESSION['2fa_verified'] = true;
        }

        // Track active login session
        $sessionToken = bin2hex(random_bytes(32));
        $_SESSION['session_token'] = $sessionToken;
        $clientIp = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 255);
        $db->prepare("INSERT INTO login_sessions (user_id, session_token, ip_address, user_agent, created_at, last_active_at) VALUES (?, ?, ?, ?, NOW(), NOW())")->execute([$user['id'], $sessionToken, $clientIp, $ua]);

        log_audit($user['id'], 'SESSION_RESTORED_COOKIE', 'users', $user['id'], 'Restored session via persistent remember-me cookie');
        return $user;
    } catch (Exception $e) {
        return null;
    }
}

function clear_remember_me_cookie(): void {
    if (!empty($_COOKIE['remember_token'])) {
        $parts = explode(':', (string)$_COOKIE['remember_token'], 2);
        if (!empty($parts[0])) {
            $db = get_db();
            if ($db) {
                try {
                    $db->prepare("DELETE FROM remember_tokens WHERE selector = ?")->execute([$parts[0]]);
                } catch (Exception $e) {}
            }
        }
    }

    if (isset($_SESSION['user_id'])) {
        $db = get_db();
        if ($db) {
            try {
                $db->prepare("DELETE FROM remember_tokens WHERE user_id = ? AND expires_at < NOW()")->execute([$_SESSION['user_id']]);
            } catch (Exception $e) {}
        }
    }

    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
    setcookie('remember_token', '', [
        'expires'  => time() - 86400,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    unset($_COOKIE['remember_token']);
}

function auth_check(): bool {
    if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
        return true;
    }
    if (!empty($_COOKIE['remember_token'])) {
        $user = check_remember_me_cookie();
        return !empty($user);
    }
    return false;
}

function is_logged_in(): bool
{
    return auth_check();
}

function current_user(): ?array
{
    if (!auth_check())
        return null;
    $db = get_db();
    if (!$db)
        return null;
    try {
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch() ?: null;
    } catch (\Throwable $e) {
        error_log("current_user() DB Error: " . $e->getMessage());
        return null;
    }
}

function require_auth(?string $allowedRole = null): array
{
    if (!auth_check()) {
        set_flash('error', 'Please log in to continue.');
        header('Location: ' . url('auth/login.php'));
        exit;
    }
    $user = current_user();
    if (!$user || $user['status'] === 'suspended') {
        session_destroy();
        header('Location: ' . url('auth/login.php?error=account_suspended'));
        exit;
    }
    // Enforce Maintenance Mode: Block non-admins (Founders, Investors) from accessing any authenticated area
    if ($user['role'] !== 'admin' && function_exists('is_maintenance_mode') && is_maintenance_mode()) {
        header('Location: ' . url('maintenance.php'));
        exit;
    }
    // Enforce 2FA verification for Admin sessions
    if ($user['role'] === 'admin' && empty($_SESSION['2fa_verified']) && is_admin_2fa_enforced((int) $user['id'])) {
        $_SESSION['2fa_pending_user_id'] = (int) $user['id'];
        $_SESSION['2fa_pending_email'] = $user['email'];
        $_SESSION['2fa_pending_role'] = $user['role'];
        $_SESSION['2fa_pending_name'] = $user['name'];
        if (!is_admin_totp_setup((int) $user['id'])) {
            header('Location: ' . url('auth/setup_2fa.php'));
            exit;
        }
        header('Location: ' . url('auth/verify_2fa.php'));
        exit;
    }
    if ($allowedRole !== null && $user['role'] !== $allowedRole && $user['role'] !== 'admin') {
        set_flash('error', 'Unauthorized access to this section.');
        $redirect = match ($user['role']) {
            'founder' => 'founder/dashboard.php',
            'investor' => 'investor/discover.php',
            'admin' => 'admin/dashboard.php',
            default => 'index.php'
        };
        header('Location: ' . url($redirect));
        exit;
    }
    return $user;
}

// 4. Audit Trail Logger
function log_audit(?int $actorId, string $action, string $entityType, ?int $entityId, ?string $details = null, ?string $prevValue = null, ?string $newValue = null): void
{
    $db = get_db();
    if (!$db)
        return;
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $userAgent = substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 255);
        $stmt = $db->prepare("
            INSERT INTO audit_logs (actor_user_id, action, entity_type, entity_id, details, previous_value, new_value, ip_address, user_agent, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$actorId, $action, $entityType, $entityId, $details, $prevValue, $newValue, $ip, $userAgent]);
    } catch (Exception $e) {
        // Fail silently for audit logs to not break flow
    }
}

// 5. In-App Notification Sender
function send_notification(int $userId, string $title, string $message, string $type = 'info', ?string $actionUrl = null): void
{
    $db = get_db();
    if (!$db)
        return;
    try {
        $stmt = $db->prepare("
            INSERT INTO notifications (user_id, title, message, type, action_url, is_read, created_at)
            VALUES (?, ?, ?, ?, ?, 0, NOW())
        ");
        $stmt->execute([$userId, $title, $message, $type, $actionUrl]);
    } catch (Exception $e) {
    }
}

// 6. Flash Messages
function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type' => $type, // 'success', 'error', 'info', 'warning'
        'message' => $message
    ];
}

function get_flash(): ?array
{
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// 7. Security: Sanitization & CSRF
function sanitize(mixed $data): mixed
{
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim((string) $data), ENT_QUOTES, 'UTF-8');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(?string $token = null): bool
{
    $token = $token ?? ($_POST['csrf_token'] ?? '');
    return !empty($token) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

// 7.1 Security Honeypot Anti-Bot Field & Validator
function security_honeypot_field(): string {
    return class_exists('SecurityGuard') ? SecurityGuard::honeypotField() : '';
}

function security_check_honeypot(): bool {
    return class_exists('SecurityGuard') ? SecurityGuard::checkHoneypot() : false;
}

// 7.2 Field-Level PII Encryption at Rest (AES-256-GCM)
function encrypt_pii(string $data, ?string $key = null): string {
    return class_exists('SecurityGuard') ? SecurityGuard::encrypt($data, $key) : $data;
}

function decrypt_pii(string $cipher, ?string $key = null): string {
    return class_exists('SecurityGuard') ? SecurityGuard::decrypt($cipher, $key) : $cipher;
}

// 7.3 Secure File Upload Magic Byte Validator
function validate_secure_upload(array $file, array $allowedMimes, int $maxBytes = 10485760): array {
    return class_exists('SecurityGuard') ? SecurityGuard::verifyFileUpload($file, $allowedMimes, $maxBytes) : ['valid' => true];
}

// 8. Currency & Number Formatters (Indian Rupee formatting: e.g., ₹25,00,000)
function format_inr(float|int $number, bool $includeSymbol = true): string
{
    $sym = $includeSymbol ? '₹' : '';
    $number = round($number);
    if ($number >= 10000000) {
        return $sym . rtrim(rtrim(number_format($number / 10000000, 2), '0'), '.') . ' Cr';
    } elseif ($number >= 100000) {
        return $sym . rtrim(rtrim(number_format($number / 100000, 2), '0'), '.') . ' L';
    }
    return $sym . number_format($number);
}

// 9. Status Badges HTML Helper (Crisp, Clean Light Theme)
function render_status_badge(string $status): string
{
    $statusUpper = strtoupper($status);
    $dotColors = [
        'DRAFT' => 'bg-slate-400',
        'SUBMITTED' => 'bg-blue-500',
        'UNDER_REVIEW' => 'bg-amber-500',
        'PENDING' => 'bg-amber-500',
        'APPROVED' => 'bg-emerald-500',
        'LIVE' => 'bg-emerald-500',
        'VERIFIED' => 'bg-emerald-500',
        'PARTIALLY_FUNDED' => 'bg-indigo-500',
        'FULLY_FUNDED' => 'bg-purple-500',
        'CLOSED' => 'bg-slate-400',
        'REJECTED' => 'bg-rose-500',
        'CONFIRMED' => 'bg-emerald-500',
        'ACTIVE' => 'bg-emerald-500',
        'COMPLETED' => 'bg-teal-500',
    ];
    $map = [
        'DRAFT' => 'bg-slate-100 text-slate-700 border-slate-200/80',
        'SUBMITTED' => 'bg-blue-50 text-blue-700 border-blue-200/80',
        'UNDER_REVIEW' => 'bg-amber-50 text-amber-800 border-amber-200/80',
        'PENDING' => 'bg-amber-50 text-amber-800 border-amber-200/80',
        'APPROVED' => 'bg-emerald-50 text-emerald-800 border-emerald-200/80',
        'LIVE' => 'bg-emerald-50 text-emerald-800 border-emerald-200/80',
        'VERIFIED' => 'bg-emerald-50 text-emerald-800 border-emerald-200/80',
        'PARTIALLY_FUNDED' => 'bg-indigo-50 text-indigo-700 border-indigo-200/80',
        'FULLY_FUNDED' => 'bg-purple-50 text-purple-700 border-purple-200/80',
        'CLOSED' => 'bg-slate-100 text-slate-600 border-slate-200/80',
        'REJECTED' => 'bg-rose-50 text-rose-700 border-rose-200/80',
        'CONFIRMED' => 'bg-emerald-50 text-emerald-800 border-emerald-200/80',
        'ACTIVE' => 'bg-emerald-50 text-emerald-800 border-emerald-200/80',
        'COMPLETED' => 'bg-teal-50 text-teal-800 border-teal-200/80',
    ];
    $classes = $map[$statusUpper] ?? 'bg-slate-100 text-slate-600 border-slate-200/80';
    $dot = $dotColors[$statusUpper] ?? 'bg-slate-400';
    $label = str_replace('_', ' ', $statusUpper);
    return "<span class=\"inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold tracking-wide border {$classes}\"><span class=\"w-1.5 h-1.5 rounded-full {$dot}\"></span><span>{$label}</span></span>";
}

// 10. Profile Completion Calculator
function get_profile_progress(int $userId, string $role): array
{
    $db = get_db();
    if (!$db)
        return ['percentage' => 0, 'is_complete' => false, 'missing' => []];

    $missing = [];
    $totalSteps = 0;
    $completedSteps = 0;

    $userStmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $userStmt->execute([$userId]);
    $user = $userStmt->fetch();

    if ($role === 'founder') {
        $totalSteps = 6;
        // Step 1: Basic user info
        if (!empty($user['name']) && !empty($user['email']) && !empty($user['phone']))
            $completedSteps++;
        else
            $missing[] = 'Basic Account & Phone Details';

        // Step 2: Founder profile
        $fpStmt = $db->prepare("SELECT * FROM founder_profiles WHERE user_id = ?");
        $fpStmt->execute([$userId]);
        $fp = $fpStmt->fetch();
        if ($fp && !empty($fp['bio']) && !empty($fp['linkedin_url']))
            $completedSteps++;
        else
            $missing[] = 'Founder Bio & LinkedIn Profile';

        // Step 3: Company Master record
        $cStmt = $db->prepare("SELECT c.* FROM companies c JOIN company_founders cf ON c.id = cf.company_id WHERE cf.user_id = ?");
        $cStmt->execute([$userId]);
        $comp = $cStmt->fetch();
        if ($comp && !empty($comp['name']) && !empty($comp['cin_number']) && !empty($comp['industry']))
            $completedSteps++;
        else
            $missing[] = 'Company Details & CIN Registration';

        // Step 4: Company Pitch & Stage
        if ($comp && !empty($comp['pitch']) && !empty($comp['stage']))
            $completedSteps++;
        else
            $missing[] = 'Startup Stage & Pitch Summary';

        // Step 5: KYC / Verification uploaded
        $vStmt = $db->prepare("SELECT * FROM verification_requests WHERE user_id = ?");
        $vStmt->execute([$userId]);
        $ver = $vStmt->fetch();
        if ($ver && $ver['status'] !== 'rejected')
            $completedSteps++;
        else
            $missing[] = 'Identity KYC / DigiLocker Verification';

        // Step 6: Bank & Declaration
        if ($fp && !empty($fp['pan_number']))
            $completedSteps++;
        else
            $missing[] = 'PAN & Founder Tax Declaration';

    } else if ($role === 'investor') {
        $totalSteps = 5;
        // Step 1: Basic account
        if (!empty($user['name']) && !empty($user['email']) && !empty($user['phone']))
            $completedSteps++;
        else
            $missing[] = 'Account Details & Phone';

        // Step 2: Investor profile
        $ipStmt = $db->prepare("SELECT * FROM investor_profiles WHERE user_id = ?");
        $ipStmt->execute([$userId]);
        $ip = $ipStmt->fetch();
        if ($ip && !empty($ip['investor_type']) && !empty($ip['experience_years']))
            $completedSteps++;
        else
            $missing[] = 'Investor Type & Experience';

        // Step 3: Investment preferences & ticket size
        $prefStmt = $db->prepare("SELECT * FROM investor_preferences WHERE user_id = ?");
        $prefStmt->execute([$userId]);
        $pref = $prefStmt->fetch();
        if ($pref && !empty($pref['preferred_industries']) && !empty($pref['min_ticket']))
            $completedSteps++;
        else
            $missing[] = 'Investment Preferences & Ticket Size';

        // Step 4: Verification status
        $vStmt = $db->prepare("SELECT * FROM verification_requests WHERE user_id = ?");
        $vStmt->execute([$userId]);
        $ver = $vStmt->fetch();
        if ($ver && $ver['status'] !== 'rejected')
            $completedSteps++;
        else
            $missing[] = 'Investor KYC / Accreditation';

        // Step 5: PAN & Risk acceptance
        if ($ip && !empty($ip['pan_number']) && $ip['risk_disclosure_accepted'])
            $completedSteps++;
        else
            $missing[] = 'PAN & Risk Disclosure Acceptance';
    } else {
        return ['percentage' => 100, 'is_complete' => true, 'missing' => []];
    }

    $pct = round(($completedSteps / max(1, $totalSteps)) * 100);
    return [
        'percentage' => $pct,
        'is_complete' => $pct >= 100,
        'missing' => $missing,
        'completed_steps' => $completedSteps,
        'total_steps' => $totalSteps
    ];
}

/**
 * 11. Fetch Founder's Associated Company
 */
function get_founder_company(int $userId): ?array
{
    $db = get_db();
    if (!$db) return null;
    try {
        $stmt = $db->prepare("
            SELECT c.*, cf.designation as founder_designation, cf.is_signatory, cf.equity_percent
            FROM companies c
            JOIN company_founders cf ON c.id = cf.company_id
            WHERE cf.user_id = ?
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        $comp = $stmt->fetch();
        return $comp ?: null;
    } catch (\Throwable $e) {
        return null;
    }
}

/**
 * Check if a founder user has an associated company profile
 */
function founder_has_company(int $userId): bool
{
    return get_founder_company($userId) !== null;
}

/**
 * Resolve Avatar URL cleanly (supports external URLs, local uploads, and auto-adapts base paths)
 */
function get_avatar_url(?string $avatarUrl, string $default = ''): string
{
    $fallback = $default ?: 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=150';
    $avatar = trim($avatarUrl ?? '');
    if ($avatar === '') {
        return $fallback;
    }

    // Auto-heal local avatar file paths or mismatched base urls pointing to uploads/avatars/
    if (preg_match('~(?:^|/)uploads/avatars/([^/?#]+)~i', $avatar, $m)) {
        $filename = $m[1];
        $diskPath = ROOT_PATH . '/uploads/avatars/' . $filename;
        if (file_exists($diskPath)) {
            return url('uploads/avatars/' . $filename) . '?v=' . filemtime($diskPath);
        }
        return $fallback;
    }

    if (str_starts_with($avatar, 'uploads/')) {
        $diskPath = ROOT_PATH . '/' . $avatar;
        if (file_exists($diskPath)) {
            return url($avatar) . '?v=' . filemtime($diskPath);
        }
        return $fallback;
    }

    if (str_starts_with($avatar, 'http://') || str_starts_with($avatar, 'https://')) {
        return $avatar;
    }

    return url($avatar);
}


/**
 * 12. Handle Avatar File Upload
 * Validates, saves to /uploads/avatars/, and returns array ['success', 'url', 'error']
 */
function handle_avatar_upload(array $file, int $userId): array
{
    if (empty($file) || !isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['success' => false, 'url' => null, 'error' => null];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'url' => null, 'error' => 'Photo upload failed (code ' . $file['error'] . ').'];
    }

    // Validate size (max 8MB)
    if ($file['size'] > 8 * 1024 * 1024) {
        return ['success' => false, 'url' => null, 'error' => 'Photo file size must be less than 8MB.'];
    }

    // Validate image format
    $imageInfo = @getimagesize($file['tmp_name']);
    if (!$imageInfo) {
        return ['success' => false, 'url' => null, 'error' => 'The selected file is not a valid image.'];
    }

    $allowedMimes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif'
    ];

    $mime = $imageInfo['mime'];
    if (!isset($allowedMimes[$mime])) {
        return ['success' => false, 'url' => null, 'error' => 'Only JPG, PNG, WEBP, and GIF photos are supported.'];
    }

    $ext = $allowedMimes[$mime];
    $uploadDir = ROOT_PATH . '/uploads/avatars';
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0777, true);
    }

    $filename = 'avatar_' . $userId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $destination = $uploadDir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => false, 'url' => null, 'error' => 'Failed to save uploaded photo to disk.'];
    }

    $publicUrl = url('uploads/avatars/' . $filename);
    return ['success' => true, 'url' => $publicUrl, 'error' => null];
}

/**
 * 12b. Resolve Company Logo URL cleanly (supports external URLs, local uploads, and initials fallback)
 */
function get_company_logo_url(?string $logoUrl, string $default = ''): string
{
    $fallback = $default ?: 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=150&auto=format&fit=crop&q=80';
    $logo = trim($logoUrl ?? '');
    if ($logo === '') {
        return $fallback;
    }

    if (preg_match('~(?:^|/)uploads/company_logos/([^/?#]+)~i', $logo, $m)) {
        $filename = $m[1];
        $diskPath = ROOT_PATH . '/uploads/company_logos/' . $filename;
        if (file_exists($diskPath)) {
            return url('uploads/company_logos/' . $filename) . '?v=' . filemtime($diskPath);
        }
        return $fallback;
    }

    if (str_starts_with($logo, 'uploads/')) {
        $diskPath = ROOT_PATH . '/' . $logo;
        if (file_exists($diskPath)) {
            return url($logo) . '?v=' . filemtime($diskPath);
        }
        return $fallback;
    }

    if (str_starts_with($logo, 'http://') || str_starts_with($logo, 'https://')) {
        return $logo;
    }

    return url($logo);
}

/**
 * 12c. Handle Company Logo File Upload
 * Validates, saves to /uploads/company_logos/, and returns array ['success', 'url', 'error']
 */
function handle_company_logo_upload(array $file, int $companyId): array
{
    if (empty($file) || !isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['success' => false, 'url' => null, 'error' => null];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'url' => null, 'error' => 'Company logo upload failed (code ' . $file['error'] . ').'];
    }

    if ($file['size'] > 10 * 1024 * 1024) {
        return ['success' => false, 'url' => null, 'error' => 'Logo file size must be less than 10MB.'];
    }

    $imageInfo = @getimagesize($file['tmp_name']);
    if (!$imageInfo) {
        return ['success' => false, 'url' => null, 'error' => 'The selected file is not a valid image.'];
    }

    $allowedMimes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif'
    ];

    $mime = $imageInfo['mime'];
    if (!isset($allowedMimes[$mime])) {
        return ['success' => false, 'url' => null, 'error' => 'Only JPG, PNG, WEBP, and GIF images are supported.'];
    }

    $ext = $allowedMimes[$mime];
    $uploadDir = ROOT_PATH . '/uploads/company_logos';
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0777, true);
    }

    $filename = 'logo_' . $companyId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $destination = $uploadDir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => false, 'url' => null, 'error' => 'Failed to save uploaded logo to disk.'];
    }

    $publicUrl = url('uploads/company_logos/' . $filename);
    return ['success' => true, 'url' => $publicUrl, 'error' => null];
}

/**
 * 12c-2. Resolve Company Cover Banner URL cleanly
 */
function get_company_banner_url(?string $bannerUrl, string $default = ''): string
{
    $fallback = $default ?: '';
    $banner = trim($bannerUrl ?? '');
    if ($banner === '') {
        return $fallback;
    }

    if (preg_match('~(?:^|/)uploads/company_banners/([^/?#]+)~i', $banner, $m)) {
        $filename = $m[1];
        $diskPath = ROOT_PATH . '/uploads/company_banners/' . $filename;
        if (file_exists($diskPath)) {
            return url('uploads/company_banners/' . $filename) . '?v=' . filemtime($diskPath);
        }
        return $fallback;
    }

    if (str_starts_with($banner, 'uploads/')) {
        $diskPath = ROOT_PATH . '/' . $banner;
        if (file_exists($diskPath)) {
            return url($banner) . '?v=' . filemtime($diskPath);
        }
        return $fallback;
    }

    if (str_starts_with($banner, 'http://') || str_starts_with($banner, 'https://')) {
        return $banner;
    }

    return url($banner);
}

/**
 * 12d. Handle Company Cover Banner File Upload
 */
function handle_company_banner_upload(array $file, int $companyId): array
{
    if (empty($file) || !isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['success' => false, 'url' => null, 'error' => null];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'url' => null, 'error' => 'Cover banner upload failed (code ' . $file['error'] . ').'];
    }

    if ($file['size'] > 12 * 1024 * 1024) {
        return ['success' => false, 'url' => null, 'error' => 'Cover banner file size must be less than 12MB.'];
    }

    $imageInfo = @getimagesize($file['tmp_name']);
    if (!$imageInfo) {
        return ['success' => false, 'url' => null, 'error' => 'The selected file is not a valid image.'];
    }

    $allowedMimes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif'
    ];

    $mime = $imageInfo['mime'];
    if (!isset($allowedMimes[$mime])) {
        return ['success' => false, 'url' => null, 'error' => 'Only JPG, PNG, and WEBP banners are supported.'];
    }

    $ext = $allowedMimes[$mime];
    $uploadDir = ROOT_PATH . '/uploads/company_banners';
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0777, true);
    }

    $filename = 'banner_' . $companyId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $destination = $uploadDir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => false, 'url' => null, 'error' => 'Failed to save uploaded banner to disk.'];
    }

    $publicUrl = url('uploads/company_banners/' . $filename);
    return ['success' => true, 'url' => $publicUrl, 'error' => null];
}

/**
 * Handle Cover / Profile Banner Photo Upload (User profile)
 */
function handle_cover_upload(array $file, int $userId): array
{
    if (empty($file) || !isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['success' => false, 'url' => null, 'error' => null];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'url' => null, 'error' => 'Banner upload failed (code ' . $file['error'] . ').'];
    }

    if ($file['size'] > 12 * 1024 * 1024) {
        return ['success' => false, 'url' => null, 'error' => 'Banner file size must be less than 12MB.'];
    }

    $imageInfo = @getimagesize($file['tmp_name']);
    if (!$imageInfo) {
        return ['success' => false, 'url' => null, 'error' => 'The selected banner file is not a valid image.'];
    }

    $allowedMimes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif'
    ];

    $mime = $imageInfo['mime'];
    if (!isset($allowedMimes[$mime])) {
        return ['success' => false, 'url' => null, 'error' => 'Only JPG, PNG, WEBP, and GIF banner images are supported.'];
    }

    $ext = $allowedMimes[$mime];
    $uploadDir = ROOT_PATH . '/uploads/covers';
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0777, true);
    }

    $filename = 'cover_' . $userId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $destination = $uploadDir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => false, 'url' => null, 'error' => 'Failed to save uploaded banner image to disk.'];
    }

    $publicUrl = url('uploads/covers/' . $filename);
    return ['success' => true, 'url' => $publicUrl, 'error' => null];
}

/**
 * 12e. Human-readable relative time string (e.g., 'just now', '5m ago', '2h ago', 'yesterday', '3d ago', 'Oct 24, 2026')
 */
function time_ago(string|int|null $datetime): string
{
    if (empty($datetime)) {
        return 'recently';
    }

    $timestamp = is_numeric($datetime) ? (int)$datetime : strtotime((string)$datetime);
    if (!$timestamp) {
        return 'recently';
    }

    $diff = time() - $timestamp;

    if ($diff < 0 || $diff < 45) {
        return 'just now';
    }
    if ($diff < 3600) {
        $mins = max(1, (int)floor($diff / 60));
        return $mins . 'm ago';
    }
    if ($diff < 86400) {
        $hours = (int)floor($diff / 3600);
        return $hours . 'h ago';
    }
    if ($diff < 172800) {
        return 'yesterday';
    }
    if ($diff < 604800) {
        $days = (int)floor($diff / 86400);
        return $days . 'd ago';
    }
    if ($diff < 2592000) {
        $weeks = (int)floor($diff / 604800);
        return $weeks . 'w ago';
    }

    return date('M j, Y', $timestamp);
}

/**
 * Handle Company Award / Trust Photo & Certificate Upload
 */
function handle_award_upload(array $file, int $companyId): array
{
    if (empty($file) || !isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['success' => false, 'url' => null, 'error' => 'Please choose an award photo or certificate file to upload.'];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'url' => null, 'error' => 'Upload failed with error code ' . $file['error'] . '.'];
    }

    if ($file['size'] > 15 * 1024 * 1024) {
        return ['success' => false, 'url' => null, 'error' => 'Award photo/document must be less than 15MB.'];
    }

    $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
    $allowedImages = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    $allowedDocs = ['pdf'];

    $isImage = in_array($ext, $allowedImages, true);
    $isPdf = in_array($ext, $allowedDocs, true);

    if (!$isImage && !$isPdf) {
        return ['success' => false, 'url' => null, 'error' => 'Unsupported format. Allowed: JPG, PNG, WEBP, GIF photos or PDF certificates.'];
    }

    if ($isImage) {
        $imgCheck = @getimagesize($file['tmp_name']);
        if (!$imgCheck) {
            return ['success' => false, 'url' => null, 'error' => 'Uploaded file is not a valid image.'];
        }
    }

    $uploadDir = ROOT_PATH . '/uploads/awards';
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0777, true);
    }

    $safeExt = $isPdf ? 'pdf' : ($ext === 'jpeg' ? 'jpg' : $ext);
    $filename = 'award_' . $companyId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $safeExt;
    $destination = $uploadDir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => false, 'url' => null, 'error' => 'Failed to save award photo to server storage.'];
    }

    $publicUrl = url('uploads/awards/' . $filename);
    return ['success' => true, 'url' => $publicUrl, 'error' => null, 'is_pdf' => $isPdf, 'filename' => $filename];
}

/**
 * Handle Generic File/Media Upload (e.g. Feed updates, post graphics, announcements)
 * Validates file size, allowed extensions, ensures clean storage under /uploads/{subfolder}/,
 * and returns ['success' => bool, 'url' => ?string, 'error' => ?string, 'filename' => ?string]
 */
function handle_file_upload(array $file, string $subfolder = 'posts', array $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'], int $maxSizeBytes = 15728640): array
{
    if (empty($file) || !isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['success' => false, 'url' => null, 'error' => null, 'filename' => null];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'url' => null, 'error' => 'Upload failed with error code ' . $file['error'] . '.', 'filename' => null];
    }

    if ($file['size'] > $maxSizeBytes) {
        $maxMB = round($maxSizeBytes / (1024 * 1024));
        return ['success' => false, 'url' => null, 'error' => "File size exceeds the allowed limit of {$maxMB}MB.", 'filename' => null];
    }

    $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExtensions, true)) {
        return ['success' => false, 'url' => null, 'error' => 'Unsupported format (' . htmlspecialchars($ext) . '). Allowed: ' . implode(', ', $allowedExtensions), 'filename' => null];
    }

    // If image format, verify valid image stream
    $imageExts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    if (in_array($ext, $imageExts, true)) {
        $imgCheck = @getimagesize($file['tmp_name']);
        if (!$imgCheck) {
            return ['success' => false, 'url' => null, 'error' => 'The selected file is not a valid image.', 'filename' => null];
        }
    }

    $safeFolder = preg_replace('/[^a-zA-Z0-9_\-]/', '', $subfolder) ?: 'posts';
    $uploadDir = ROOT_PATH . '/uploads/' . $safeFolder;
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0777, true);
    }

    $safeExt = ($ext === 'jpeg') ? 'jpg' : $ext;
    $filename = $safeFolder . '_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $safeExt;
    $destination = $uploadDir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => false, 'url' => null, 'error' => 'Failed to save uploaded file to server storage.', 'filename' => null];
    }

    $publicUrl = url('uploads/' . $safeFolder . '/' . $filename);
    return ['success' => true, 'url' => $publicUrl, 'error' => null, 'filename' => $filename];
}

// 13. Security Event Logger
function log_security_event(?int $userId, string $eventType, string $severity = 'medium', ?string $details = null): void
{
    $db = get_db();
    if (!$db)
        return;
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $userAgent = substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 255);
        $stmt = $db->prepare("
            INSERT INTO security_events (user_id, event_type, severity, ip_address, user_agent, details, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$userId, $eventType, $severity, $ip, $userAgent, $details]);
    } catch (Exception $e) {
    }
}

/**
 * Brute-Force Rate Limiting Engine:
 * Blocks IP/account if more than 5 failed login attempts in last 15 minutes
 */
function is_login_rate_limited(string $ip, string $email): bool {
    $db = get_db();
    if (!$db) return false;
    try {
        $stmt = $db->prepare("
            SELECT COUNT(*) 
            FROM security_events 
            WHERE event_type = 'LOGIN_FAILED' 
              AND (ip_address = ? OR details LIKE ?)
              AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
        ");
        $emailPattern = '%' . $email . '%';
        $stmt->execute([$ip, $emailPattern]);
        $failures = (int)$stmt->fetchColumn();
        return $failures >= 5;
    } catch (Exception $e) {
        return false;
    }
}

function record_failed_login(string $ip, string $email): void {
    log_security_event(null, 'LOGIN_FAILED', 'medium', "Failed credentials attempt for email: {$email}");
}

function clear_failed_logins(string $ip, string $email): void {
    $db = get_db();
    if (!$db) return;
    try {
        $emailPattern = '%' . $email . '%';
        $stmt = $db->prepare("
            DELETE FROM security_events 
            WHERE event_type = 'LOGIN_FAILED' 
              AND (ip_address = ? OR details LIKE ?)
        ");
        $stmt->execute([$ip, $emailPattern]);
    } catch (Exception $e) {}
}

// 14. Two-Factor Authentication (2FA) Helpers: Base32 & RFC 6238 TOTP Engine
function base32_decode(string $b32): string
{
    $b32 = strtoupper(trim($b32));
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $binary = '';
    $buffer = 0;
    $bufferSize = 0;

    for ($i = 0; $i < strlen($b32); $i++) {
        $char = $b32[$i];
        if ($char === '=')
            break;
        $val = strpos($alphabet, $char);
        if ($val === false)
            continue;

        $buffer = ($buffer << 5) | $val;
        $bufferSize += 5;

        if ($bufferSize >= 8) {
            $bufferSize -= 8;
            $binary .= chr(($buffer >> $bufferSize) & 0xFF);
        }
    }
    return $binary;
}

function base32_encode(string $data): string
{
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $b32 = '';
    $buffer = 0;
    $bufferSize = 0;

    for ($i = 0; $i < strlen($data); $i++) {
        $buffer = ($buffer << 8) | ord($data[$i]);
        $bufferSize += 8;

        while ($bufferSize >= 5) {
            $bufferSize -= 5;
            $b32 .= $alphabet[($buffer >> $bufferSize) & 0x1F];
        }
    }

    if ($bufferSize > 0) {
        $buffer = $buffer << (5 - $bufferSize);
        $b32 .= $alphabet[$buffer & 0x1F];
    }

    return $b32;
}

function generate_totp_secret(int $length = 16): string
{
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $secret = '';
    for ($i = 0; $i < $length; $i++) {
        $secret .= $alphabet[random_int(0, strlen($alphabet) - 1)];
    }
    return $secret;
}

function get_totp_code(string $secret, ?int $timeSlice = null): string
{
    if ($timeSlice === null) {
        $timeSlice = (int) floor(time() / 30);
    }
    $secretKey = base32_decode($secret);
    $time = chr(0) . chr(0) . chr(0) . chr(0) . pack('N*', $timeSlice);
    $hmac = hash_hmac('sha1', $time, $secretKey, true);
    $offset = ord(substr($hmac, -1)) & 0x0F;
    $hashPart = substr($hmac, $offset, 4);
    $value = unpack('N', $hashPart)[1] & 0x7FFFFFFF;
    $modulo = $value % 1000000;
    return sprintf('%06d', $modulo);
}

function verify_totp_code(string $secret, string $code, int $discrepancy = 1): bool
{
    $cleanCode = trim(preg_replace('/[^0-9]/', '', $code));
    if (strlen($cleanCode) !== 6)
        return false;
    $currentTimeSlice = (int) floor(time() / 30);
    for ($i = -$discrepancy; $i <= $discrepancy; $i++) {
        $calcCode = get_totp_code($secret, $currentTimeSlice + $i);
        if (hash_equals($calcCode, $cleanCode)) {
            return true;
        }
    }
    return false;
}

function get_totp_auth_url(string $email, string $secret): string
{
    $issuer = APP_NAME;
    $label = rawurlencode($issuer) . ':' . rawurlencode($email);
    return "otpauth://totp/{$label}?secret={$secret}&issuer=" . rawurlencode($issuer) . "&algorithm=SHA1&digits=6&period=30";
}

function get_2fa_record(int $userId): ?array
{
    $db = get_db();
    if (!$db)
        return null;
    $stmt = $db->prepare("SELECT * FROM two_factor_auth WHERE user_id = ? LIMIT 1");
    $stmt->execute([$userId]);
    $rec = $stmt->fetch();
    if (!$rec) {
        $uStmt = $db->prepare("SELECT role FROM users WHERE id = ?");
        $uStmt->execute([$userId]);
        $user = $uStmt->fetch();
        if ($user) {
            $authType = ($user['role'] === 'admin') ? 'authenticator' : 'email_otp';
            $secret = ($user['role'] === 'admin') ? generate_totp_secret() : null;
            $ins = $db->prepare("INSERT INTO two_factor_auth (user_id, auth_type, secret_code, is_enabled, is_totp_setup, created_at) VALUES (?, ?, ?, 1, 0, NOW())");
            $ins->execute([$userId, $authType, $secret]);
            $stmt->execute([$userId]);
            $rec = $stmt->fetch();
        }
    }
    return $rec ?: null;
}

function is_admin_2fa_enforced(int $userId): bool
{
    $rec = get_2fa_record($userId);
    if (!$rec)
        return true; // Default enforced for admin
    return (bool) $rec['is_enabled'];
}

function is_admin_totp_setup(int $userId): bool
{
    $rec = get_2fa_record($userId);
    if (!$rec)
        return false;
    return !empty($rec['is_totp_setup']) && !empty($rec['secret_code']);
}

function get_or_create_totp_secret(int $userId): string
{
    $db = get_db();
    if (!$db)
        return '';
    $rec = get_2fa_record($userId);
    if (!empty($rec['secret_code'])) {
        return $rec['secret_code'];
    }
    $secret = generate_totp_secret();
    $upd = $db->prepare("UPDATE two_factor_auth SET secret_code = ?, updated_at = NOW() WHERE user_id = ?");
    $upd->execute([$secret, $userId]);
    return $secret;
}

function confirm_admin_totp_setup(int $userId, string $code): array
{
    $db = get_db();
    if (!$db)
        return ['success' => false, 'message' => 'Database error.'];
    $rec = get_2fa_record($userId);
    if (!$rec || empty($rec['secret_code'])) {
        return ['success' => false, 'message' => 'No active setup secret found. Please refresh and try again.'];
    }

    if (verify_totp_code($rec['secret_code'], $code, 2)) {
        // Mark as verified & active
        $upd = $db->prepare("
            UPDATE two_factor_auth 
            SET is_totp_setup = 1, is_enabled = 1, attempts = 0, last_verified_at = NOW() 
            WHERE user_id = ?
        ");
        $upd->execute([$userId]);

        // Generate emergency backup codes if not present
        if (empty($rec['backup_codes'])) {
            generate_2fa_backup_codes($userId, 5);
        }

        log_security_event($userId, '2FA_TOTP_SETUP_CONFIRMED', 'low', 'Mobile Authenticator (TOTP) successfully paired via QR code.');
        log_audit($userId, '2FA_TOTP_SETUP_CONFIRMED', 'two_factor_auth', $userId, 'Paired mobile authenticator app via QR code');

        return ['success' => true, 'message' => 'Mobile Authenticator verified and linked successfully!'];
    }

    log_security_event($userId, '2FA_TOTP_SETUP_FAILED', 'medium', 'Invalid confirmation code entered during QR setup.');
    return ['success' => false, 'message' => 'Invalid 6-digit code. Please verify the code displayed in Google/Microsoft Authenticator and ensure device clock is accurate.'];
}

function verify_admin_totp_login(int $userId, string $code): array
{
    $db = get_db();
    if (!$db)
        return ['success' => false, 'message' => 'Database error.'];

    $cleanCode = trim(preg_replace('/[^0-9]/', '', $code));
    if (strlen($cleanCode) !== 6) {
        return ['success' => false, 'message' => 'Please enter the 6-digit code from your authenticator app.'];
    }

    $rec = get_2fa_record($userId);
    if (!$rec || empty($rec['secret_code']) || empty($rec['is_totp_setup'])) {
        return ['success' => false, 'message' => 'Authenticator app is not configured. Please complete setup.'];
    }

    if ((int) $rec['attempts'] >= 5) {
        log_security_event($userId, '2FA_MAX_ATTEMPTS_EXCEEDED', 'high', 'Max 2FA failed attempts reached.');
        return ['success' => false, 'message' => 'Too many failed attempts. Please wait 1 minute before trying again or use an Emergency Backup Code.'];
    }

    if (verify_totp_code($rec['secret_code'], $cleanCode, 1)) {
        $db->prepare("UPDATE two_factor_auth SET attempts = 0, last_verified_at = NOW() WHERE user_id = ?")->execute([$userId]);
        log_security_event($userId, '2FA_VERIFICATION_SUCCESS', 'low', 'Authenticated via Mobile Authenticator TOTP.');
        log_audit($userId, '2FA_VERIFICATION_SUCCESS', 'two_factor_auth', $userId, 'Admin authenticated via Authenticator TOTP');
        return ['success' => true, 'message' => 'Verification successful!'];
    } else {
        $newAttempts = (int) $rec['attempts'] + 1;
        $db->prepare("UPDATE two_factor_auth SET attempts = ? WHERE user_id = ?")->execute([$newAttempts, $userId]);
        $remaining = max(0, 5 - $newAttempts);
        log_security_event($userId, '2FA_VERIFICATION_FAILED', 'medium', "Invalid TOTP entered. Attempt {$newAttempts} of 5.");
        return ['success' => false, 'message' => "Invalid code. {$remaining} attempt" . ($remaining === 1 ? '' : 's') . " remaining."];
    }
}

function reset_admin_totp(int $userId): void
{
    $db = get_db();
    if (!$db)
        return;
    $newSecret = generate_totp_secret();
    $db->prepare("UPDATE two_factor_auth SET secret_code = ?, is_totp_setup = 0, attempts = 0 WHERE user_id = ?")->execute([$newSecret, $userId]);
    log_security_event($userId, '2FA_TOTP_RESET', 'high', 'Admin mobile authenticator was reset for re-pairing.');
    log_audit($userId, '2FA_TOTP_RESET', 'two_factor_auth', $userId, 'Mobile Authenticator reset for QR re-scan');
}

function generate_2fa_otp(int $userId): string
{
    $db = get_db();
    if (!$db)
        return '';

    // Ensure 2FA record exists
    get_2fa_record($userId);

    // Cryptographically secure 6-digit PIN
    $otp = sprintf('%06d', random_int(100000, 999999));
    $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));

    $stmt = $db->prepare("
        UPDATE two_factor_auth 
        SET current_otp = ?, otp_expires_at = ?, attempts = 0, updated_at = NOW()
        WHERE user_id = ?
    ");
    $stmt->execute([$otp, $expiresAt, $userId]);

    log_security_event($userId, '2FA_CHALLENGE_ISSUED', 'low', "New 6-digit OTP code generated, valid until {$expiresAt}");
    log_audit($userId, '2FA_CHALLENGE_ISSUED', 'two_factor_auth', $userId, 'Two-factor OTP code generated');

    return $otp;
}

function verify_2fa_otp(int $userId, string $code): array
{
    $db = get_db();
    if (!$db) {
        return ['success' => false, 'message' => 'Database connection unavailable.'];
    }

    $cleanCode = trim(preg_replace('/[^0-9]/', '', $code));
    if (strlen($cleanCode) !== 6) {
        return ['success' => false, 'message' => 'Please enter a valid 6-digit verification code.'];
    }

    $rec = get_2fa_record($userId);
    if (!$rec || empty($rec['current_otp'])) {
        return ['success' => false, 'message' => 'No active verification code found. Please request a new code.'];
    }

    // Rate limiting: 5 attempts
    if ((int) $rec['attempts'] >= 5) {
        log_security_event($userId, '2FA_MAX_ATTEMPTS_EXCEEDED', 'high', 'Max 2FA failed attempts reached. Code invalidated.');
        return ['success' => false, 'message' => 'Security limit exceeded: Too many incorrect attempts. Please click "Resend Code" to obtain a new code.'];
    }

    // Check expiration
    if (empty($rec['otp_expires_at']) || strtotime($rec['otp_expires_at']) < time()) {
        log_security_event($userId, '2FA_CODE_EXPIRED', 'medium', 'Expired OTP code was entered.');
        return ['success' => false, 'message' => 'Verification code has expired. Please request a new code.'];
    }

    // Compare
    if (hash_equals((string) $rec['current_otp'], $cleanCode)) {
        // Clear active code upon successful verification
        $upd = $db->prepare("
            UPDATE two_factor_auth 
            SET current_otp = NULL, otp_expires_at = NULL, attempts = 0, last_verified_at = NOW() 
            WHERE user_id = ?
        ");
        $upd->execute([$userId]);

        log_security_event($userId, '2FA_VERIFICATION_SUCCESS', 'low', 'Two-factor verification successfully validated.');
        log_audit($userId, '2FA_VERIFICATION_SUCCESS', 'two_factor_auth', $userId, 'Admin successfully passed 2FA verification');

        return ['success' => true, 'message' => 'Verification successful!'];
    } else {
        $newAttempts = (int) $rec['attempts'] + 1;
        $db->prepare("UPDATE two_factor_auth SET attempts = ? WHERE user_id = ?")->execute([$newAttempts, $userId]);
        $remaining = max(0, 5 - $newAttempts);

        log_security_event($userId, '2FA_VERIFICATION_FAILED', 'medium', "Invalid OTP entered. Attempt {$newAttempts} of 5.");
        return ['success' => false, 'message' => "Incorrect verification code. {$remaining} attempt" . ($remaining === 1 ? '' : 's') . " remaining."];
    }
}

function generate_2fa_backup_codes(int $userId, int $count = 5): array
{
    $db = get_db();
    if (!$db)
        return [];

    get_2fa_record($userId);

    $plainCodes = [];
    $storageCodes = [];

    for ($i = 0; $i < $count; $i++) {
        // Format: ABCD-1234
        $part1 = strtoupper(bin2hex(random_bytes(2)));
        $part2 = strtoupper(bin2hex(random_bytes(2)));
        $plain = $part1 . '-' . $part2;
        $plainCodes[] = $plain;
        $storageCodes[] = [
            'code_hash' => password_hash(str_replace('-', '', $plain), PASSWORD_DEFAULT),
            'used' => false,
            'used_at' => null
        ];
    }

    $json = json_encode($storageCodes);
    $stmt = $db->prepare("UPDATE two_factor_auth SET backup_codes = ?, updated_at = NOW() WHERE user_id = ?");
    $stmt->execute([$json, $userId]);

    log_security_event($userId, '2FA_BACKUP_CODES_GENERATED', 'medium', "Generated {$count} new emergency backup recovery codes.");
    log_audit($userId, '2FA_BACKUP_CODES_GENERATED', 'two_factor_auth', $userId, "Generated {$count} emergency recovery codes");

    return $plainCodes;
}

function verify_2fa_backup_code(int $userId, string $enteredCode): array
{
    $db = get_db();
    if (!$db)
        return ['success' => false, 'message' => 'Database error.'];

    $clean = strtoupper(trim(preg_replace('/[^A-Za-z0-9]/', '', $enteredCode)));
    if (strlen($clean) < 6) {
        return ['success' => false, 'message' => 'Please enter a valid backup recovery code.'];
    }

    $rec = get_2fa_record($userId);
    if (!$rec || empty($rec['backup_codes'])) {
        return ['success' => false, 'message' => 'No backup recovery codes configured.'];
    }

    $codes = json_decode($rec['backup_codes'], true);
    if (!is_array($codes)) {
        return ['success' => false, 'message' => 'No backup recovery codes configured.'];
    }

    $matchedIndex = -1;
    foreach ($codes as $idx => $item) {
        if (empty($item['used']) && password_verify($clean, $item['code_hash'])) {
            $matchedIndex = $idx;
            break;
        }
    }

    if ($matchedIndex !== -1) {
        $codes[$matchedIndex]['used'] = true;
        $codes[$matchedIndex]['used_at'] = date('Y-m-d H:i:s');
        $json = json_encode($codes);

        $upd = $db->prepare("
            UPDATE two_factor_auth 
            SET backup_codes = ?, current_otp = NULL, otp_expires_at = NULL, attempts = 0, last_verified_at = NOW() 
            WHERE user_id = ?
        ");
        $upd->execute([$json, $userId]);

        log_security_event($userId, '2FA_BACKUP_CODE_USED', 'high', 'Emergency backup recovery code used to authenticate Admin session.');
        log_audit($userId, '2FA_BACKUP_CODE_USED', 'two_factor_auth', $userId, 'Emergency backup recovery code used for 2FA');

        return ['success' => true, 'message' => 'Emergency backup code accepted!'];
    }

    log_security_event($userId, '2FA_BACKUP_CODE_FAILED', 'medium', 'Invalid emergency backup code attempt.');
    return ['success' => false, 'message' => 'Invalid or already used emergency backup code.'];
}

function get_remaining_backup_codes_count(int $userId): int
{
    $rec = get_2fa_record($userId);
    if (!$rec || empty($rec['backup_codes']))
        return 0;
    $codes = json_decode($rec['backup_codes'], true);
    if (!is_array($codes))
        return 0;
    $unused = 0;
    foreach ($codes as $c) {
        if (empty($c['used']))
            $unused++;
    }
    return $unused;
}

/**
 * Ensure broadcasts table exists
 */
function init_broadcasts_table(PDO $db): void {
    static $initialized = false;
    if ($initialized) return;
    try {
        $db->exec("
            CREATE TABLE IF NOT EXISTS `broadcasts` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `admin_user_id` INT NOT NULL,
                `title` VARCHAR(255) NOT NULL,
                `message` TEXT NOT NULL,
                `priority` ENUM('urgent','compliance','update','opportunity') DEFAULT 'update',
                `target_audience` ENUM('all','founder','investor','pending_kyc') DEFAULT 'all',
                `show_banner` TINYINT(1) DEFAULT 1,
                `cta_label` VARCHAR(100) NULL,
                `cta_url` VARCHAR(255) NULL,
                `image_url` VARCHAR(255) NULL,
                `recipients_count` INT DEFAULT 0,
                `is_active` TINYINT(1) DEFAULT 1,
                `expires_at` DATETIME NULL,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX (`admin_user_id`),
                INDEX (`is_active`),
                INDEX (`priority`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        $initialized = true;
    } catch (Exception $e) {
        // Table already exists or migration handled
    }
}

/**
 * Return human-readable relative time string (e.g. '5 mins ago', '2 hours ago', 'yesterday')
 */
function time_elapsed_string($datetime, $full = false) {
    if (empty($datetime)) return '';
    try {
        $timestamp = is_numeric($datetime) ? (int)$datetime : strtotime($datetime);
        if (!$timestamp) return '';
        $diff = time() - $timestamp;

        if ($diff < 5) return 'just now';
        if ($diff < 60) return $diff . ' secs ago';
        if ($diff < 3600) {
            $mins = max(1, floor($diff / 60));
            return $mins . ' min' . ($mins > 1 ? 's' : '') . ' ago';
        }
        if ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . ' hr' . ($hours > 1 ? 's' : '') . ' ago';
        }
        if ($diff < 604800) {
            $days = floor($diff / 86400);
            return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
        }
        if ($diff < 2592000) {
            $weeks = floor($diff / 604800);
            return $weeks . ' wk' . ($weeks > 1 ? 's' : '') . ' ago';
        }
        return date('M d, Y', $timestamp);
    } catch (Exception $e) {
        return date('M d, Y', strtotime($datetime));
    }
}

/**
 * ============================================================================
 * Subscription & Priority Level Management Helpers
 * ============================================================================
 */

/**
 * Get comprehensive metadata for a plan code and role
 */
function get_plan_details(string $planCode, string $role = 'founder'): array {
    $role = strtolower($role) === 'investor' ? 'investor' : 'founder';
    
    $plans = [
        'founder' => [
            'free_trial' => [
                'code' => 'free_trial',
                'name' => '14-Day Free Trial',
                'priority_level' => 1,
                'priority_name' => 'Standard Access',
                'badge_class' => 'bg-slate-100 text-slate-700 border-slate-300',
                'price_monthly' => 0,
                'price_annual' => 0,
                'duration_days' => 14,
                'deal_rooms' => 1,
                'is_featured' => false,
                'perks' => [
                    '1 Live Pitch Deck Listing',
                    'Basic Cap Table Viewer',
                    'Public Syndicate Directory',
                    'Standard Community Support'
                ]
            ],
            '1_month' => [
                'code' => '1_month',
                'name' => '1 Month Sprint',
                'priority_level' => 2,
                'priority_name' => 'Verified Growth',
                'badge_class' => 'bg-purple-100 text-purple-800 border-purple-300',
                'price_monthly' => 2499,
                'price_annual' => 1999,
                'duration_days' => 30,
                'deal_rooms' => 3,
                'is_featured' => false,
                'perks' => [
                    '3 Active Deal Rooms',
                    'Direct Founder-Investor DMs',
                    'DigiLocker KYC Verified Badge',
                    'Real-Time Pitch Analytics'
                ]
            ],
            '6_months' => [
                'code' => '6_months',
                'name' => '6 Months Dealmaker',
                'priority_level' => 3,
                'priority_name' => 'Featured Priority',
                'badge_class' => 'bg-gradient-to-r from-purple-500 to-pink-500 text-white font-extrabold',
                'price_monthly' => 1666,
                'price_annual' => 1499,
                'duration_days' => 180,
                'deal_rooms' => 9999, // unlimited
                'is_featured' => true,
                'perks' => [
                    'Top Featured Dealflow Placement',
                    'Unlimited Active Deal Rooms',
                    'Direct WhatsApp Warm Intros',
                    'SEBI & MCA SAFE Legal Templates',
                    'Dedicated Venture Scout Manager'
                ]
            ],
            '1_year' => [
                'code' => '1_year',
                'name' => '1 Year Scale Pro',
                'priority_level' => 4,
                'priority_name' => 'VIP Spotlight Pro',
                'badge_class' => 'bg-gradient-to-r from-amber-500 via-pink-600 to-purple-600 text-white font-extrabold',
                'price_monthly' => 1499,
                'price_annual' => 1249,
                'duration_days' => 365,
                'deal_rooms' => 9999, // unlimited
                'is_featured' => true,
                'perks' => [
                    '#1 Top Spotlight Placement & Gold Badge',
                    'SPV Pooling & Syndicate Lead Tools',
                    'White-Label LP Data Room',
                    'Full Platform REST API & Webhooks',
                    'Dedicated Partner Success Director'
                ]
            ]
        ],
        'investor' => [
            'free_trial' => [
                'code' => 'free_trial',
                'name' => '14-Day Explorer Pass',
                'priority_level' => 1,
                'priority_name' => 'Explorer Access',
                'badge_class' => 'bg-slate-100 text-slate-700 border-slate-300',
                'price_monthly' => 0,
                'price_annual' => 0,
                'duration_days' => 14,
                'deal_rooms' => 5,
                'is_featured' => false,
                'perks' => [
                    'Browse 300+ Curated Deal Summaries',
                    'Sector & Stage Filters',
                    'Weekly Dealflow Newsletter'
                ]
            ],
            '1_month' => [
                'code' => '1_month',
                'name' => '1 Month Active Angel',
                'priority_level' => 2,
                'priority_name' => 'Verified Angel',
                'badge_class' => 'bg-purple-100 text-purple-800 border-purple-300',
                'price_monthly' => 3499,
                'price_annual' => 2799,
                'duration_days' => 30,
                'deal_rooms' => 25,
                'is_featured' => false,
                'perks' => [
                    'Audited MRR & Diligence Cap Tables',
                    'Direct Founder 1-on-1 DMs',
                    'Full Pitch Deck Downloads',
                    'Co-Invest From ₹2 Lakhs'
                ]
            ],
            '6_months' => [
                'code' => '6_months',
                'name' => '6 Months Syndicate Lead',
                'priority_level' => 3,
                'priority_name' => 'Syndicate Priority',
                'badge_class' => 'bg-gradient-to-r from-purple-500 to-pink-500 text-white font-extrabold',
                'price_monthly' => 2499,
                'price_annual' => 1999,
                'duration_days' => 180,
                'deal_rooms' => 9999,
                'is_featured' => true,
                'perks' => [
                    'Lead Syndicates & SPV Pooling',
                    'Priority Allocation in Hot Rounds',
                    'Direct WhatsApp Founder Connect',
                    'Automated Carry & Distribution CRM',
                    'Dedicated Venture Scout'
                ]
            ],
            '1_year' => [
                'code' => '1_year',
                'name' => '1 Year Institutional Suite',
                'priority_level' => 4,
                'priority_name' => 'Institutional VIP',
                'badge_class' => 'bg-gradient-to-r from-amber-500 via-pink-600 to-purple-600 text-white font-extrabold',
                'price_monthly' => 1999,
                'price_annual' => 1699,
                'duration_days' => 365,
                'deal_rooms' => 9999,
                'is_featured' => true,
                'perks' => [
                    'Custom Institutional Research Reports',
                    'White-Label LP Deal Portal',
                    'Full Dealflow REST API & Webhooks',
                    'Dedicated Partner Relationship Manager'
                ]
            ]
        ]
    ];

    return $plans[$role][$planCode] ?? $plans[$role]['free_trial'];
}

/**
 * Update user subscription, calculate expiry, and update priority rankings across user and company records
 */
function activate_user_subscription(int $userId, string $planCode, string $billingCycle = 'monthly', ?float $customAmount = null, ?string $paymentRef = null): array {
    $db = get_db();
    if (!$db) {
        return ['success' => false, 'error' => 'Database connection unavailable'];
    }

    $uStmt = $db->prepare("SELECT id, name, email, role FROM users WHERE id = ?");
    $uStmt->execute([$userId]);
    $user = $uStmt->fetch();
    if (!$user) {
        return ['success' => false, 'error' => 'User not found'];
    }

    $role = $user['role'] ?? 'founder';
    $planInfo = get_plan_details($planCode, $role);
    $priorityLevel = (int)$planInfo['priority_level'];
    $durationDays = (int)$planInfo['duration_days'];

    // If annual, multiply duration if not already 365
    if ($billingCycle === 'annually' && $planCode !== '1_year' && $planCode !== 'free_trial') {
        $durationDays = 365;
    }

    $expiresAt = date('Y-m-d H:i:s', strtotime("+{$durationDays} days"));
    $amount = $customAmount !== null ? $customAmount : ($billingCycle === 'annually' ? (float)$planInfo['price_annual'] * ($planCode === '6_months' ? 6 : 12) : (float)$planInfo['price_monthly']);
    if ($planCode === 'free_trial') {
        $amount = 0.00;
    }

    $txRef = $paymentRef ?: ('NEX-' . strtoupper(substr($role, 0, 3)) . '-' . strtoupper(bin2hex(random_bytes(4))));

    // 1. Mark existing active subscriptions as expired/replaced
    $db->prepare("UPDATE subscriptions SET status = 'cancelled' WHERE user_id = ? AND status IN ('active', 'trial')")->execute([$userId]);

    // 2. Insert new subscription record
    $insStmt = $db->prepare("
        INSERT INTO subscriptions 
        (user_id, plan_code, plan_name, billing_cycle, role, amount, priority_level, status, payment_method, payment_status, transaction_ref, starts_at, expires_at, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'active', 'Razorpay / Instant Card Gateway', 'completed', ?, NOW(), ?, NOW())
    ");
    $insStmt->execute([
        $userId,
        $planCode,
        $planInfo['name'],
        $billingCycle,
        $role,
        $amount,
        $priorityLevel,
        $txRef,
        $expiresAt
    ]);

    // 3. Update User table
    $updUser = $db->prepare("UPDATE users SET current_plan = ?, priority_level = ?, plan_expires_at = ? WHERE id = ?");
    $updUser->execute([$planCode, $priorityLevel, $expiresAt, $userId]);

    // 4. If Founder, update associated Company priority level as well for dealflow ranking
    if ($role === 'founder') {
        $updComp = $db->prepare("
            UPDATE companies c 
            JOIN company_founders cf ON cf.company_id = c.id 
            SET c.priority_level = ? 
            WHERE cf.user_id = ?
        ");
        $updComp->execute([$priorityLevel, $userId]);
    }

    // 5. Audit log
    if (function_exists('log_audit')) {
        log_audit($userId, 'PLAN_PURCHASED', 'subscriptions', (int)$db->lastInsertId(), "Subscribed to {$planInfo['name']} with Priority Level {$priorityLevel}");
    }

    return [
        'success' => true,
        'plan_code' => $planCode,
        'plan_name' => $planInfo['name'],
        'priority_level' => $priorityLevel,
        'priority_name' => $planInfo['priority_name'],
        'expires_at' => $expiresAt,
        'amount' => $amount,
        'transaction_ref' => $txRef
    ];
}

/**
 * ====================================================================
 * Platform Settings & Maintenance Mode Engine
 * ====================================================================
 */

function init_platform_settings_table(PDO $db): void
{
    static $initialized = false;
    if ($initialized) return;
    try {
        $db->exec("
            CREATE TABLE IF NOT EXISTS `platform_settings` (
                `setting_key` VARCHAR(100) PRIMARY KEY,
                `setting_value` LONGTEXT NULL,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                `updated_by` INT NULL,
                INDEX `idx_setting_key` (`setting_key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        $initialized = true;
    } catch (\Throwable $e) {
        // Table may already exist
    }
}

function get_platform_setting(string $key, mixed $default = null): mixed
{
    global $PLATFORM_SETTINGS_CACHE;
    if (!is_array($PLATFORM_SETTINGS_CACHE)) {
        $PLATFORM_SETTINGS_CACHE = [];
    }
    if (array_key_exists($key, $PLATFORM_SETTINGS_CACHE)) {
        return $PLATFORM_SETTINGS_CACHE[$key];
    }

    $db = get_db();
    if (!$db) return $default;

    try {
        init_platform_settings_table($db);
        $stmt = $db->prepare("SELECT setting_value FROM platform_settings WHERE setting_key = ? LIMIT 1");
        $stmt->execute([$key]);
        $val = $stmt->fetchColumn();

        if ($val === false) {
            $PLATFORM_SETTINGS_CACHE[$key] = $default;
            return $default;
        }

        $PLATFORM_SETTINGS_CACHE[$key] = $val;
        return $val;
    } catch (\Throwable $e) {
        return $default;
    }
}

function set_platform_setting(string $key, mixed $value, ?int $updatedBy = null): bool
{
    global $PLATFORM_SETTINGS_CACHE;
    $db = get_db();
    if (!$db) return false;

    try {
        init_platform_settings_table($db);
        $valStr = (string)$value;
        $stmt = $db->prepare("
            INSERT INTO platform_settings (setting_key, setting_value, updated_at, updated_by)
            VALUES (?, ?, NOW(), ?)
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW(), updated_by = VALUES(updated_by)
        ");
        $ok = $stmt->execute([$key, $valStr, $updatedBy]);

        if (!is_array($PLATFORM_SETTINGS_CACHE)) {
            $PLATFORM_SETTINGS_CACHE = [];
        }
        $PLATFORM_SETTINGS_CACHE[$key] = $valStr;
        return $ok;
    } catch (\Throwable $e) {
        error_log("set_platform_setting error: " . $e->getMessage());
        return false;
    }
}

function is_maintenance_mode(): bool
{
    $mode = (string) get_platform_setting('maintenance_mode', '0');
    return in_array(strtolower(trim($mode)), ['1', 'true', 'on', 'yes'], true);
}

function get_maintenance_info(): array
{
    return [
        'is_active' => is_maintenance_mode(),
        'title' => get_platform_setting('maintenance_title', 'Scheduled Platform Maintenance'),
        'message' => get_platform_setting('maintenance_message', 'Our platform is currently undergoing scheduled infrastructure upgrades. We will be back online shortly.'),
        'estimated_end' => get_platform_setting('maintenance_estimated_end', ''),
        'allowed_ips' => get_platform_setting('maintenance_allowed_ips', ''),
        'updated_at' => get_platform_setting('maintenance_updated_at', '')
    ];
}

function enforce_maintenance_mode(): void
{
    if (php_sapi_name() === 'cli') {
        return;
    }

    if (!is_maintenance_mode()) {
        return;
    }

    // 1. If user is logged in as admin, permit full platform bypass
    if (!empty($_SESSION['user_id'])) {
        $u = current_user();
        if ($u && $u['role'] === 'admin') {
            return;
        }
    }

    // 2. IP Whitelist check
    $clientIp = $_SERVER['REMOTE_ADDR'] ?? '';
    $allowedIps = get_platform_setting('maintenance_allowed_ips', '');
    if (!empty($allowedIps)) {
        $ipList = array_filter(array_map('trim', explode(',', $allowedIps)));
        if (in_array($clientIp, $ipList, true)) {
            return;
        }
    }

    // 3. Inspect requested script & URI
    $scriptName = strtolower(str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? ''));
    $requestUri = strtolower(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '');

    // Allow static asset requests
    if (preg_match('/\.(css|js|png|jpg|jpeg|gif|svg|ico|webp|woff|woff2|ttf|eot|map)$/i', $requestUri)) {
        return;
    }

    // Allow maintenance page itself to prevent redirect loop
    if (str_ends_with($scriptName, 'maintenance.php') || str_ends_with(rtrim($requestUri, '/'), '/maintenance')) {
        return;
    }

    // Allow essential authentication scripts so administrators can sign in
    $allowedAuthScripts = [
        'login.php',
        'logout.php',
        'verify_2fa.php',
        'setup_2fa.php'
    ];
    foreach ($allowedAuthScripts as $allowed) {
        if (str_ends_with($scriptName, $allowed) || str_ends_with(rtrim($requestUri, '/'), '/' . pathinfo($allowed, PATHINFO_FILENAME))) {
            return;
        }
    }

    // 4. API Endpoints: Return 503 JSON
    $isJson = (str_starts_with($requestUri, '/api/') || str_contains($requestUri, '/api/')) 
           || (!empty($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))
           || (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

    if ($isJson) {
        http_response_code(503);
        header('Content-Type: application/json; charset=utf-8');
        header('Retry-After: 3600');
        $info = get_maintenance_info();
        echo json_encode([
            'success' => false,
            'error' => 'Service Unavailable: Platform is in maintenance mode.',
            'maintenance' => true,
            'title' => $info['title'],
            'message' => $info['message'],
            'estimated_end' => $info['estimated_end']
        ]);
        exit;
    }

    // 5. Standard Web Pages: Redirect to maintenance.php
    header('Location: ' . url('maintenance.php'), true, 307);
    exit;
}

// =========================================================================
// 19. LINKEDIN-STYLE USER CONNECTIONS & NETWORKING (FOUNDERS & INVESTORS)
// =========================================================================

/**
 * Get connection status between two users
 * Statuses: 'self', 'connected', 'pending_sent', 'pending_received', 'declined', 'none'
 */
function get_connection_status(int $userAId, int $userBId): array {
    if ($userAId <= 0 || $userBId <= 0) {
        return ['status' => 'none', 'is_connected' => false, 'connection' => null];
    }
    if ($userAId === $userBId) {
        return ['status' => 'self', 'is_connected' => false, 'connection' => null];
    }

    $db = get_db();
    if (!$db) {
        return ['status' => 'none', 'is_connected' => false, 'connection' => null];
    }

    try {
        $stmt = $db->prepare("
            SELECT * FROM user_connections 
            WHERE (requester_id = ? AND receiver_id = ?) 
               OR (requester_id = ? AND receiver_id = ?)
            ORDER BY updated_at DESC LIMIT 1
        ");
        $stmt->execute([$userAId, $userBId, $userBId, $userAId]);
        $conn = $stmt->fetch();

        if (!$conn) {
            return ['status' => 'none', 'is_connected' => false, 'connection' => null];
        }

        if ($conn['status'] === 'accepted') {
            return ['status' => 'connected', 'is_connected' => true, 'connection' => $conn];
        }

        if ($conn['status'] === 'pending') {
            if ((int)$conn['requester_id'] === $userAId) {
                return ['status' => 'pending_sent', 'is_connected' => false, 'connection' => $conn];
            } else {
                return ['status' => 'pending_received', 'is_connected' => false, 'connection' => $conn];
            }
        }

        if ($conn['status'] === 'declined' || $conn['status'] === 'withdrawn') {
            return ['status' => 'none', 'is_connected' => false, 'connection' => $conn];
        }

        return ['status' => 'none', 'is_connected' => false, 'connection' => $conn];
    } catch (\Throwable $e) {
        error_log("Connection status error: " . $e->getMessage());
        return ['status' => 'none', 'is_connected' => false, 'connection' => null];
    }
}

/**
 * Send a connection request from one user to another
 */
function send_connection_request(int $fromUserId, int $toUserId, ?string $message = null, int $companyId = 0): array {
    if ($fromUserId <= 0 || $toUserId <= 0 || $fromUserId === $toUserId) {
        return ['success' => false, 'error' => 'Invalid user selected.'];
    }

    $db = get_db();
    if (!$db) {
        return ['success' => false, 'error' => 'Database connection failed.'];
    }

    try {
        // Verify target user exists and is active
        $uStmt = $db->prepare("SELECT id, name, role, email FROM users WHERE id = ? AND status != 'suspended'");
        $uStmt->execute([$toUserId]);
        $targetUser = $uStmt->fetch();

        if (!$targetUser) {
            return ['success' => false, 'error' => 'Target user does not exist or account is inactive.'];
        }

        // Fetch sender user info
        $sStmt = $db->prepare("SELECT id, name, role FROM users WHERE id = ?");
        $sStmt->execute([$fromUserId]);
        $sender = $sStmt->fetch();

        // Check company info if companyId provided
        $companyName = '';
        if ($companyId > 0) {
            $compStmt = $db->prepare("SELECT id, name FROM companies WHERE id = ?");
            $compStmt->execute([$companyId]);
            $compRow = $compStmt->fetch();
            if ($compRow) {
                $companyName = $compRow['name'];
            }
        }

        // Check existing connection
        $chk = $db->prepare("
            SELECT * FROM user_connections 
            WHERE (requester_id = ? AND receiver_id = ?) 
               OR (requester_id = ? AND receiver_id = ?)
            LIMIT 1
        ");
        $chk->execute([$fromUserId, $toUserId, $toUserId, $fromUserId]);
        $existing = $chk->fetch();

        $cleanMsg = $message !== null ? trim(substr($message, 0, 500)) : null;

        if ($existing) {
            if ($existing['status'] === 'accepted') {
                return ['success' => false, 'error' => 'You are already connected with ' . htmlspecialchars($targetUser['name']) . ($companyName ? " ({$companyName})" : '') . '.'];
            }
            if ($existing['status'] === 'pending') {
                if ((int)$existing['requester_id'] === $fromUserId) {
                    return ['success' => false, 'error' => 'Connection request is already pending.'];
                } else {
                    // Other user already invited this user -> Auto-accept!
                    return respond_connection_request((int)$existing['id'], $fromUserId, 'accept');
                }
            }

            // If declined or withdrawn, re-activate as pending
            $upd = $db->prepare("
                UPDATE user_connections 
                SET requester_id = ?, receiver_id = ?, status = 'pending', message = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            $upd->execute([$fromUserId, $toUserId, $cleanMsg, $existing['id']]);
            $connId = (int)$existing['id'];
        } else {
            $ins = $db->prepare("
                INSERT INTO user_connections (requester_id, receiver_id, status, message, created_at, updated_at) 
                VALUES (?, ?, 'pending', ?, NOW(), NOW())
            ");
            $ins->execute([$fromUserId, $toUserId, $cleanMsg]);
            $connId = (int)$db->lastInsertId();
        }

        // Send in-app notification to the handling founder
        $targetRoleRedirect = $targetUser['role'] === 'founder' ? 'founder/network.php?tab=invitations' : 'investor/network.php?tab=invitations';
        
        if ($companyName) {
            $notifTitle = "New Connection Request • {$companyName}";
            $notifBody = "{$sender['name']} (" . ucfirst($sender['role']) . ") sent a connection request to your startup {$companyName}" . ($cleanMsg ? ": \"" . htmlspecialchars($cleanMsg) . "\"" : ".");
            $respMsg = "Connection invitation sent to {$companyName}'s founder ({$targetUser['name']})!";
        } else {
            $notifTitle = "New Connection Request";
            $notifBody = "{$sender['name']} (" . ucfirst($sender['role']) . ") sent you a connection request" . ($cleanMsg ? ": \"" . htmlspecialchars($cleanMsg) . "\"" : ".");
            $respMsg = "Connection invitation sent to " . htmlspecialchars($targetUser['name']) . "!";
        }

        send_notification(
            $toUserId,
            $notifTitle,
            $notifBody,
            "info",
            $targetRoleRedirect
        );

        // If companyId provided and there are other co-founders, notify them as well
        if ($companyId > 0) {
            $coStmt = $db->prepare("SELECT user_id FROM company_founders WHERE company_id = ? AND user_id != ? AND user_id != ?");
            $coStmt->execute([$companyId, $toUserId, $fromUserId]);
            $coFounders = $coStmt->fetchAll();
            foreach ($coFounders as $co) {
                send_notification(
                    (int)$co['user_id'],
                    "Startup Network • {$companyName}",
                    "{$sender['name']} sent a connection invitation to {$companyName}.",
                    "info",
                    "founder/network.php"
                );
            }
        }

        log_audit($fromUserId, 'CONNECT_REQUEST_SENT', 'users', $toUserId, "Sent connect request to {$targetUser['name']}" . ($companyName ? " for company {$companyName}" : ''));

        return [
            'success' => true,
            'connection_id' => $connId,
            'target_id' => encode_id($toUserId),
            'company_id' => $companyId ? encode_id($companyId) : '',
            'status' => 'pending_sent',
            'message' => $respMsg
        ];
    } catch (\Throwable $e) {
        error_log("send_connection_request error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Failed to send request: ' . $e->getMessage()];
    }
}

/**
 * Respond to an invitation: 'accept', 'decline', 'withdraw'
 */
function respond_connection_request(int $connectionId, int $currentUserId, string $action): array {
    if ($connectionId <= 0 || $currentUserId <= 0) {
        return ['success' => false, 'error' => 'Invalid request parameters.'];
    }

    $db = get_db();
    if (!$db) {
        return ['success' => false, 'error' => 'Database connection failed.'];
    }

    try {
        $stmt = $db->prepare("SELECT * FROM user_connections WHERE id = ?");
        $stmt->execute([$connectionId]);
        $conn = $stmt->fetch();

        if (!$conn) {
            return ['success' => false, 'error' => 'Connection request not found.'];
        }

        $requesterId = (int)$conn['requester_id'];
        $receiverId = (int)$conn['receiver_id'];

        if ($action === 'accept') {
            if ($receiverId !== $currentUserId) {
                return ['success' => false, 'error' => 'Only the invited user can accept this connection.'];
            }

            $upd = $db->prepare("UPDATE user_connections SET status = 'accepted', updated_at = NOW() WHERE id = ?");
            $upd->execute([$connectionId]);

            // Fetch names for notification
            $uStmt = $db->prepare("SELECT name, role FROM users WHERE id = ?");
            $uStmt->execute([$currentUserId]);
            $currentUserRow = $uStmt->fetch();

            $rStmt = $db->prepare("SELECT role FROM users WHERE id = ?");
            $rStmt->execute([$requesterId]);
            $requesterRole = $rStmt->fetchColumn() ?: 'founder';

            $reqRedirect = $requesterRole === 'founder' ? 'founder/network.php' : 'investor/network.php';
            send_notification(
                $requesterId,
                "Connection Accepted!",
                "{$currentUserRow['name']} accepted your connection request. You are now connected!",
                "success",
                $reqRedirect
            );

            log_audit($currentUserId, 'CONNECT_ACCEPTED', 'users', $requesterId, "Accepted connection with user #{$requesterId}");

            return [
                'success' => true,
                'status' => 'connected',
                'message' => 'Connected successfully! You can now message each other directly.'
            ];
        } elseif ($action === 'decline') {
            if ($receiverId !== $currentUserId) {
                return ['success' => false, 'error' => 'Only the invited user can decline this connection.'];
            }

            $upd = $db->prepare("UPDATE user_connections SET status = 'declined', updated_at = NOW() WHERE id = ?");
            $upd->execute([$connectionId]);

            return [
                'success' => true,
                'status' => 'none',
                'message' => 'Connection invitation ignored.'
            ];
        } elseif ($action === 'withdraw') {
            if ($requesterId !== $currentUserId) {
                return ['success' => false, 'error' => 'Only the sender can withdraw this request.'];
            }

            $upd = $db->prepare("UPDATE user_connections SET status = 'withdrawn', updated_at = NOW() WHERE id = ?");
            $upd->execute([$connectionId]);

            return [
                'success' => true,
                'status' => 'none',
                'message' => 'Connection request withdrawn.'
            ];
        }

        return ['success' => false, 'error' => 'Invalid action specified.'];
    } catch (\Throwable $e) {
        error_log("respond_connection_request error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Action failed: ' . $e->getMessage()];
    }
}

/**
 * Remove an existing connection
 */
function remove_user_connection(int $currentUserId, int $targetUserId): array {
    if ($currentUserId <= 0 || $targetUserId <= 0) {
        return ['success' => false, 'error' => 'Invalid user.'];
    }

    $db = get_db();
    if (!$db) return ['success' => false, 'error' => 'Database connection unavailable.'];

    try {
        $del = $db->prepare("
            DELETE FROM user_connections 
            WHERE (requester_id = ? AND receiver_id = ?) 
               OR (requester_id = ? AND receiver_id = ?)
        ");
        $del->execute([$currentUserId, $targetUserId, $targetUserId, $currentUserId]);

        return ['success' => true, 'status' => 'none', 'message' => 'Connection removed.'];
    } catch (\Throwable $e) {
        return ['success' => false, 'error' => 'Failed to remove connection: ' . $e->getMessage()];
    }
}

/**
 * Count of pending connection requests for a user
 */
function get_pending_connections_count(int $userId): int {
    if ($userId <= 0) return 0;
    $db = get_db();
    if (!$db) return 0;

    try {
        $stmt = $db->prepare("SELECT COUNT(*) FROM user_connections WHERE receiver_id = ? AND status = 'pending'");
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    } catch (\Throwable $e) {
        return 0;
    }
}

/**
 * Get all 1st degree connections for a user
 */
function get_user_connections(int $userId): array {
    if ($userId <= 0) return [];
    $db = get_db();
    if (!$db) return [];

    try {
        $stmt = $db->prepare("
            SELECT uc.id as connection_id, uc.created_at as connected_at,
                   u.id as user_id, u.name, u.email, u.phone, u.avatar_url, u.role, u.city, u.country, u.is_verified,
                   fp.designation as founder_designation, fp.bio as founder_bio, fp.linkedin_url as founder_linkedin,
                   c.id as company_id, c.name as company_name, c.logo_url as company_logo,
                   ip.investor_type, NULL as investor_linkedin
            FROM user_connections uc
            JOIN users u ON (CASE WHEN uc.requester_id = ? THEN uc.receiver_id ELSE uc.requester_id END) = u.id
            LEFT JOIN founder_profiles fp ON u.id = fp.user_id AND u.role = 'founder'
            LEFT JOIN company_founders cf ON u.id = cf.user_id
            LEFT JOIN companies c ON cf.company_id = c.id
            LEFT JOIN investor_profiles ip ON u.id = ip.user_id AND u.role = 'investor'
            WHERE (uc.requester_id = ? OR uc.receiver_id = ?) AND uc.status = 'accepted'
            GROUP BY u.id
            ORDER BY uc.updated_at DESC
        ");
        $stmt->execute([$userId, $userId, $userId]);
        return $stmt->fetchAll();
    } catch (\Throwable $e) {
        error_log("get_user_connections error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get pending connection invitations received by this user
 */
function get_pending_connection_requests(int $userId): array {
    if ($userId <= 0) return [];
    $db = get_db();
    if (!$db) return [];

    try {
        $stmt = $db->prepare("
            SELECT uc.id as connection_id, uc.message as invite_note, uc.created_at as requested_at,
                   u.id as user_id, u.name, u.email, u.avatar_url, u.role, u.city, u.country, u.is_verified,
                   fp.designation as founder_designation, fp.bio as founder_bio,
                   c.name as company_name, c.logo_url as company_logo,
                   ip.investor_type
            FROM user_connections uc
            JOIN users u ON uc.requester_id = u.id
            LEFT JOIN founder_profiles fp ON u.id = fp.user_id AND u.role = 'founder'
            LEFT JOIN company_founders cf ON u.id = cf.user_id
            LEFT JOIN companies c ON cf.company_id = c.id
            LEFT JOIN investor_profiles ip ON u.id = ip.user_id AND u.role = 'investor'
            WHERE uc.receiver_id = ? AND uc.status = 'pending'
            GROUP BY u.id
            ORDER BY uc.created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    } catch (\Throwable $e) {
        error_log("get_pending_connection_requests error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get pending connection invitations sent by this user
 */
function get_sent_connection_requests(int $userId): array {
    if ($userId <= 0) return [];
    $db = get_db();
    if (!$db) return [];

    try {
        $stmt = $db->prepare("
            SELECT uc.id as connection_id, uc.message as invite_note, uc.created_at as requested_at,
                   u.id as user_id, u.name, u.email, u.avatar_url, u.role, u.city, u.country, u.is_verified,
                   fp.designation as founder_designation,
                   c.name as company_name, c.logo_url as company_logo,
                   ip.investor_type
            FROM user_connections uc
            JOIN users u ON uc.receiver_id = u.id
            LEFT JOIN founder_profiles fp ON u.id = fp.user_id AND u.role = 'founder'
            LEFT JOIN company_founders cf ON u.id = cf.user_id
            LEFT JOIN companies c ON cf.company_id = c.id
            LEFT JOIN investor_profiles ip ON u.id = ip.user_id AND u.role = 'investor'
            WHERE uc.requester_id = ? AND uc.status = 'pending'
            GROUP BY u.id
            ORDER BY uc.created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    } catch (\Throwable $e) {
        error_log("get_sent_connection_requests error: " . $e->getMessage());
        return [];
    }
}

/**
 * Render LinkedIn-style Connect Button Component
 */
function render_connect_button(int $targetUserId, array $options = []): string {
    $currentUser = current_user();
    if (!$currentUser || $currentUser['id'] == $targetUserId) {
        return '';
    }

    $currentUserId = (int)$currentUser['id'];
    $statusData = get_connection_status($currentUserId, $targetUserId);
    $status = $statusData['status'] ?? 'none';
    $conn = $statusData['connection'] ?? null;

    $targetHash = encode_id($targetUserId);
    $connHash = !empty($conn['id']) ? encode_id($conn['id']) : '';

    $name = $options['name'] ?? 'Member';
    $headline = $options['headline'] ?? 'Portal Member';
    $avatar = $options['avatar'] ?? 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=100';
    $customClass = $options['class'] ?? '';

    $msgUrl = ($currentUser['role'] === 'founder') 
        ? url('founder/messages.php') 
        : url('investor/messages.php');

    if ($status === 'connected') {
        return '
        <div class="inline-flex items-center gap-1.5 ' . htmlspecialchars($customClass) . '">
            <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 font-bold text-xs border border-emerald-200 dark:border-emerald-800 shadow-2xs">
                <i data-lucide="check" class="w-3.5 h-3.5 text-emerald-600"></i>
                <span>Connected</span>
            </span>
            <a href="' . $msgUrl . '" class="p-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 transition" title="Direct Message">
                <i data-lucide="message-square" class="w-3.5 h-3.5"></i>
            </a>
        </div>';
    }

    if ($status === 'pending_sent') {
        return '
        <button type="button" 
                data-connect-user="' . $targetHash . '" 
                data-connection-id="' . $connHash . '"
                data-connect-status="pending_sent"
                onclick="window.NexoraConnect ? window.NexoraConnect.withdraw(\'' . $targetHash . '\', \'' . $connHash . '\') : null"
                title="Click to withdraw invitation"
                class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-bold text-xs border border-slate-200 dark:border-slate-700 hover:bg-rose-50 hover:text-rose-600 hover:border-rose-200 transition shadow-2xs cursor-pointer ' . htmlspecialchars($customClass) . '">
            <i data-lucide="clock" class="w-3.5 h-3.5 text-slate-400"></i>
            <span>Pending</span>
        </button>';
    }

    if ($status === 'pending_received') {
        return '
        <div class="inline-flex items-center gap-1.5 ' . htmlspecialchars($customClass) . '">
            <button type="button" 
                    data-connection-id="' . $connHash . '"
                    onclick="window.NexoraConnect ? window.NexoraConnect.respond(\'' . $connHash . '\', \'accept\', \'' . $targetHash . '\') : null"
                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-xs transition active:scale-95 cursor-pointer">
                <i data-lucide="check" class="w-3.5 h-3.5"></i>
                <span>Accept</span>
            </button>
            <button type="button" 
                    data-connection-id="' . $connHash . '"
                    onclick="window.NexoraConnect ? window.NexoraConnect.respond(\'' . $connHash . '\', \'decline\', \'' . $targetHash . '\') : null"
                    class="px-2.5 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-600 dark:text-slate-300 text-xs font-bold transition cursor-pointer">
                Ignore
            </button>
        </div>';
    }

    // Default: None -> Connect
    return '
    <button type="button"
            data-connect-user="' . $targetHash . '"
            data-connection-id="' . $connHash . '"
            data-connect-name="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '"
            data-connect-headline="' . htmlspecialchars($headline, ENT_QUOTES, 'UTF-8') . '"
            data-connect-avatar="' . htmlspecialchars($avatar, ENT_QUOTES, 'UTF-8') . '"
            data-connect-status="none"
            onclick="window.NexoraConnect ? window.NexoraConnect.openModal(\'' . $targetHash . '\', \'' . addslashes($name) . '\', \'' . addslashes($headline) . '\', \'' . addslashes($avatar) . '\') : null"
            class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-xs transition active:scale-95 cursor-pointer ' . htmlspecialchars($customClass) . '">
        <i data-lucide="user-plus" class="w-3.5 h-3.5"></i>
        <span>Connect</span>
    </button>';
}

/**
 * Connect with a Company: Automatically resolves the founder handling the company
 * and sends the connection request and notification specifically targeting the startup.
 */
function connect_with_company(int $fromUserId, int $companyId, ?string $message = null): array {
    if ($fromUserId <= 0 || $companyId <= 0) {
        return ['success' => false, 'error' => 'Invalid parameters.'];
    }

    $db = get_db();
    if (!$db) {
        return ['success' => false, 'error' => 'Database connection unavailable.'];
    }

    try {
        // 1. Verify company exists
        $cStmt = $db->prepare("SELECT id, name, logo_url FROM companies WHERE id = ?");
        $cStmt->execute([$companyId]);
        $company = $cStmt->fetch();
        if (!$company) {
            return ['success' => false, 'error' => 'Company not found.'];
        }

        // 2. Find primary founder handling this company
        $fStmt = $db->prepare("
            SELECT cf.user_id, u.name, u.role
            FROM company_founders cf
            JOIN users u ON cf.user_id = u.id
            WHERE cf.company_id = ? AND u.status != 'suspended'
            ORDER BY cf.is_signatory DESC, cf.created_at ASC
            LIMIT 1
        ");
        $fStmt->execute([$companyId]);
        $founder = $fStmt->fetch();

        if (!$founder) {
            return ['success' => false, 'error' => 'No active founder account found managing ' . htmlspecialchars($company['name']) . '.'];
        }

        $targetFounderId = (int)$founder['user_id'];
        if ($targetFounderId === $fromUserId) {
            return ['success' => false, 'error' => 'You cannot connect with your own company.'];
        }

        // 3. Delegate to send_connection_request with companyId
        return send_connection_request($fromUserId, $targetFounderId, $message, $companyId);
    } catch (\Throwable $e) {
        error_log("connect_with_company error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Failed to connect with company: ' . $e->getMessage()];
    }
}

/**
 * Render LinkedIn-Style Connect with Company Button
 * Target company resolves to the managing founder so they receive notification
 */
function render_company_connect_button(int $companyId, array $options = []): string {
    $currentUser = current_user();
    if (!$currentUser || $companyId <= 0) {
        return '';
    }

    $currentUserId = (int)$currentUser['id'];
    $db = get_db();
    if (!$db) return '';

    try {
        // Check if current user is one of the founders of this company
        $myCheck = $db->prepare("SELECT COUNT(*) FROM company_founders WHERE company_id = ? AND user_id = ?");
        $myCheck->execute([$companyId, $currentUserId]);
        if ((int)$myCheck->fetchColumn() > 0) {
            return ''; // Don't show connect button for own company
        }

        // Fetch company and primary founder
        $stmt = $db->prepare("
            SELECT c.id as company_id, c.name as company_name, c.logo_url as company_logo, c.industry as company_industry,
                   cf.user_id as founder_user_id, cf.designation as founder_designation,
                   u.name as founder_name, u.avatar_url as founder_avatar
            FROM companies c
            JOIN company_founders cf ON c.id = cf.company_id
            JOIN users u ON cf.user_id = u.id
            WHERE c.id = ? AND u.status != 'suspended'
            ORDER BY cf.is_signatory DESC, cf.created_at ASC
            LIMIT 1
        ");
        $stmt->execute([$companyId]);
        $row = $stmt->fetch();

        if (!$row) return '';

        $targetFounderId = (int)$row['founder_user_id'];
        $statusData = get_connection_status($currentUserId, $targetFounderId);
        $status = $statusData['status'] ?? 'none';
        $conn = $statusData['connection'] ?? null;

        $targetHash = encode_id($targetFounderId);
        $companyHash = encode_id($companyId);
        $connHash = !empty($conn['id']) ? encode_id($conn['id']) : '';

        $companyName = $options['name'] ?? $row['company_name'];
        $headline = $options['headline'] ?? ($row['company_name'] . ' • Founded by ' . $row['founder_name']);
        $logo = $options['logo'] ?? get_company_logo_url($row['company_logo'] ?? '');
        $customClass = $options['class'] ?? '';
        $buttonText = $options['button_text'] ?? 'Connect';

        $msgUrl = ($currentUser['role'] === 'founder') 
            ? url('founder/messages.php') 
            : url('investor/messages.php');

        if ($status === 'connected') {
            return '
            <div class="inline-flex items-center gap-1.5 ' . htmlspecialchars($customClass) . '">
                <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 font-bold text-xs border border-emerald-200 dark:border-emerald-800 shadow-2xs">
                    <i data-lucide="check" class="w-3.5 h-3.5 text-emerald-600"></i>
                    <span>Connected</span>
                </span>
                <a href="' . $msgUrl . '" class="p-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 transition" title="Direct Message Founder">
                    <i data-lucide="message-square" class="w-3.5 h-3.5"></i>
                </a>
            </div>';
        }

        if ($status === 'pending_sent') {
            return '
            <button type="button" 
                    data-connect-company="' . $companyHash . '"
                    data-connect-user="' . $targetHash . '" 
                    data-connect-status="pending_sent"
                    onclick="window.NexoraConnect ? window.NexoraConnect.withdraw(\'' . $targetHash . '\', \'' . $connHash . '\') : null"
                    title="Click to withdraw invitation to ' . htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8') . '"
                    class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-bold text-xs border border-slate-200 dark:border-slate-700 hover:bg-rose-50 hover:text-rose-600 hover:border-rose-200 transition shadow-2xs cursor-pointer ' . htmlspecialchars($customClass) . '">
                <i data-lucide="clock" class="w-3.5 h-3.5 text-slate-400"></i>
                <span>Pending</span>
            </button>';
        }

        if ($status === 'pending_received') {
            return '
            <div class="inline-flex items-center gap-1.5 ' . htmlspecialchars($customClass) . '">
                <button type="button" 
                        onclick="window.NexoraConnect ? window.NexoraConnect.respond(\'' . $connHash . '\', \'accept\', \'' . $targetHash . '\') : null"
                        class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-xs transition active:scale-95 cursor-pointer">
                    <i data-lucide="check" class="w-3.5 h-3.5"></i>
                    <span>Accept</span>
                </button>
            </div>';
        }

        // Default: None -> Connect with Company / Founder
        return '
        <button type="button"
                data-connect-company="' . $companyHash . '"
                data-connect-user="' . $targetHash . '"
                data-connect-name="' . htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8') . '"
                data-connect-headline="' . htmlspecialchars($headline, ENT_QUOTES, 'UTF-8') . '"
                data-connect-avatar="' . htmlspecialchars($logo, ENT_QUOTES, 'UTF-8') . '"
                data-connect-status="none"
                onclick="window.NexoraConnect ? window.NexoraConnect.openModal(\'' . $targetHash . '\', \'' . addslashes($companyName) . '\', \'' . addslashes($headline) . '\', \'' . addslashes($logo) . '\', \'' . $companyHash . '\') : null"
                class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-xs transition active:scale-95 cursor-pointer ' . htmlspecialchars($customClass) . '">
            <i data-lucide="building-2" class="w-3.5 h-3.5"></i>
            <span>' . htmlspecialchars($buttonText) . '</span>
        </button>';
    } catch (\Throwable $e) {
        error_log("render_company_connect_button error: " . $e->getMessage());
        return '';
    }
}



