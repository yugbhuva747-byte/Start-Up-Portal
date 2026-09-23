<?php
/**
 * Global Helper Functions
 * Includes Hash ID, Authentication, Audit Logging, Flash Messages, and Sanitization
 */

// 1. Dynamic URL Generator (Clean URLs without .php extension)
function url(string $path = ''): string {
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

// 2. Hash ID Encoding & Decoding (Reversible, URL-safe, secure obfuscation)
function hash_id_encode(int|string|null $id): string {
    if (empty($id)) return '';
    $id = (int)$id;
    $key = APP_KEY;
    $salt = substr(hash('sha256', $key . $id), 0, 6);
    $payload = base64_encode($id . ':' . $salt);
    return str_replace(['+', '/', '='], ['-', '_', ''], $payload);
}

function hash_id_decode(?string $hash): int {
    if (empty($hash)) return 0;
    $b64 = str_replace(['-', '_'], ['+', '/'], $hash);
    $mod4 = strlen($b64) % 4;
    if ($mod4) {
        $b64 .= substr('====', $mod4);
    }
    $decoded = base64_decode($b64, true);
    if (!$decoded || !str_contains($decoded, ':')) return 0;
    
    list($id, $salt) = explode(':', $decoded, 2);
    $id = (int)$id;
    $expectedSalt = substr(hash('sha256', APP_KEY . $id), 0, 6);
    if (hash_equals($expectedSalt, $salt)) {
        return $id;
    }
    return 0;
}

function encode_id(int|string|null $id): string {
    return hash_id_encode($id);
}

function decode_id(?string $hash): int {
    return hash_id_decode($hash);
}

// 3. Authentication & Role Control
function auth_check(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function is_logged_in(): bool {
    return auth_check();
}

function current_user(): ?array {
    if (!auth_check()) return null;
    $db = get_db();
    if (!$db) return null;
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

function require_auth(?string $allowedRole = null): array {
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
    // Enforce 2FA verification for Admin sessions
    if ($user['role'] === 'admin' && empty($_SESSION['2fa_verified']) && is_admin_2fa_enforced((int)$user['id'])) {
        $_SESSION['2fa_pending_user_id'] = (int)$user['id'];
        $_SESSION['2fa_pending_email'] = $user['email'];
        $_SESSION['2fa_pending_role'] = $user['role'];
        $_SESSION['2fa_pending_name'] = $user['name'];
        if (!is_admin_totp_setup((int)$user['id'])) {
            header('Location: ' . url('auth/setup_2fa.php'));
            exit;
        }
        header('Location: ' . url('auth/verify_2fa.php'));
        exit;
    }
    if ($allowedRole !== null && $user['role'] !== $allowedRole && $user['role'] !== 'admin') {
        set_flash('error', 'Unauthorized access to this section.');
        $redirect = match($user['role']) {
            'founder' => 'founder/dashboard.php',
            'investor' => 'investor/dashboard.php',
            'admin' => 'admin/dashboard.php',
            default => 'index.php'
        };
        header('Location: ' . url($redirect));
        exit;
    }
    return $user;
}

// 4. Audit Trail Logger
function log_audit(?int $actorId, string $action, string $entityType, ?int $entityId, ?string $details = null, ?string $prevValue = null, ?string $newValue = null): void {
    $db = get_db();
    if (!$db) return;
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
function send_notification(int $userId, string $title, string $message, string $type = 'info', ?string $actionUrl = null): void {
    $db = get_db();
    if (!$db) return;
    try {
        $stmt = $db->prepare("
            INSERT INTO notifications (user_id, title, message, type, action_url, is_read, created_at)
            VALUES (?, ?, ?, ?, ?, 0, NOW())
        ");
        $stmt->execute([$userId, $title, $message, $type, $actionUrl]);
    } catch (Exception $e) {}
}

// 6. Flash Messages
function set_flash(string $type, string $message): void {
    $_SESSION['flash'] = [
        'type' => $type, // 'success', 'error', 'info', 'warning'
        'message' => $message
    ];
}

function get_flash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// 7. Security: Sanitization & CSRF
function sanitize(mixed $data): mixed {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(?string $token): bool {
    return !empty($token) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// 8. Currency & Number Formatters (Indian Rupee formatting: e.g., ₹25,00,000)
function format_inr(float|int $number, bool $includeSymbol = true): string {
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
function render_status_badge(string $status): string {
    $statusUpper = strtoupper($status);
    $map = [
        'DRAFT' => 'bg-slate-100 text-slate-700 border-slate-200',
        'SUBMITTED' => 'bg-blue-50 text-blue-700 border-blue-200',
        'UNDER_REVIEW' => 'bg-amber-50 text-amber-800 border-amber-200',
        'PENDING' => 'bg-amber-50 text-amber-800 border-amber-200',
        'APPROVED' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'LIVE' => 'bg-emerald-50 text-emerald-700 border-emerald-300 font-bold animate-pulse',
        'VERIFIED' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'PARTIALLY_FUNDED' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
        'FULLY_FUNDED' => 'bg-purple-50 text-purple-700 border-purple-200',
        'CLOSED' => 'bg-slate-100 text-slate-600 border-slate-200',
        'REJECTED' => 'bg-rose-50 text-rose-700 border-rose-200',
        'CONFIRMED' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'ACTIVE' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'COMPLETED' => 'bg-teal-50 text-teal-700 border-teal-200',
    ];
    $classes = $map[$statusUpper] ?? 'bg-slate-100 text-slate-600 border-slate-200';
    $label = str_replace('_', ' ', $statusUpper);
    return "<span class=\"inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold tracking-wide border {$classes}\">{$label}</span>";
}

// 10. Profile Completion Calculator
function get_profile_progress(int $userId, string $role): array {
    $db = get_db();
    if (!$db) return ['percentage' => 0, 'is_complete' => false, 'missing' => []];

    $missing = [];
    $totalSteps = 0;
    $completedSteps = 0;

    $userStmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $userStmt->execute([$userId]);
    $user = $userStmt->fetch();

    if ($role === 'founder') {
        $totalSteps = 6;
        // Step 1: Basic user info
        if (!empty($user['name']) && !empty($user['email']) && !empty($user['phone'])) $completedSteps++;
        else $missing[] = 'Basic Account & Phone Details';

        // Step 2: Founder profile
        $fpStmt = $db->prepare("SELECT * FROM founder_profiles WHERE user_id = ?");
        $fpStmt->execute([$userId]);
        $fp = $fpStmt->fetch();
        if ($fp && !empty($fp['bio']) && !empty($fp['linkedin_url'])) $completedSteps++;
        else $missing[] = 'Founder Bio & LinkedIn Profile';

        // Step 3: Company Master record
        $cStmt = $db->prepare("SELECT c.* FROM companies c JOIN company_founders cf ON c.id = cf.company_id WHERE cf.user_id = ?");
        $cStmt->execute([$userId]);
        $comp = $cStmt->fetch();
        if ($comp && !empty($comp['name']) && !empty($comp['cin_number']) && !empty($comp['industry'])) $completedSteps++;
        else $missing[] = 'Company Details & CIN Registration';

        // Step 4: Company Pitch & Stage
        if ($comp && !empty($comp['pitch']) && !empty($comp['stage'])) $completedSteps++;
        else $missing[] = 'Startup Stage & Pitch Summary';

        // Step 5: KYC / Verification uploaded
        $vStmt = $db->prepare("SELECT * FROM verification_requests WHERE user_id = ?");
        $vStmt->execute([$userId]);
        $ver = $vStmt->fetch();
        if ($ver && $ver['status'] !== 'rejected') $completedSteps++;
        else $missing[] = 'Identity KYC / DigiLocker Verification';

        // Step 6: Bank & Declaration
        if ($fp && !empty($fp['pan_number'])) $completedSteps++;
        else $missing[] = 'PAN & Founder Tax Declaration';

    } else if ($role === 'investor') {
        $totalSteps = 5;
        // Step 1: Basic account
        if (!empty($user['name']) && !empty($user['email']) && !empty($user['phone'])) $completedSteps++;
        else $missing[] = 'Account Details & Phone';

        // Step 2: Investor profile
        $ipStmt = $db->prepare("SELECT * FROM investor_profiles WHERE user_id = ?");
        $ipStmt->execute([$userId]);
        $ip = $ipStmt->fetch();
        if ($ip && !empty($ip['investor_type']) && !empty($ip['experience_years'])) $completedSteps++;
        else $missing[] = 'Investor Type & Experience';

        // Step 3: Investment preferences & ticket size
        $prefStmt = $db->prepare("SELECT * FROM investor_preferences WHERE user_id = ?");
        $prefStmt->execute([$userId]);
        $pref = $prefStmt->fetch();
        if ($pref && !empty($pref['preferred_industries']) && !empty($pref['min_ticket'])) $completedSteps++;
        else $missing[] = 'Investment Preferences & Ticket Size';

        // Step 4: Verification status
        $vStmt = $db->prepare("SELECT * FROM verification_requests WHERE user_id = ?");
        $vStmt->execute([$userId]);
        $ver = $vStmt->fetch();
        if ($ver && $ver['status'] !== 'rejected') $completedSteps++;
        else $missing[] = 'Investor KYC / Accreditation';

        // Step 5: PAN & Risk acceptance
        if ($ip && !empty($ip['pan_number']) && $ip['risk_disclosure_accepted']) $completedSteps++;
        else $missing[] = 'PAN & Risk Disclosure Acceptance';
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
 * 12. Handle Avatar File Upload
 * Validates, saves to /uploads/avatars/, and returns array ['success', 'url', 'error']
 */
function handle_avatar_upload(array $file, int $userId): array {
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
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif'
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

// 13. Security Event Logger
function log_security_event(?int $userId, string $eventType, string $severity = 'medium', ?string $details = null): void {
    $db = get_db();
    if (!$db) return;
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $userAgent = substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 255);
        $stmt = $db->prepare("
            INSERT INTO security_events (user_id, event_type, severity, ip_address, user_agent, details, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$userId, $eventType, $severity, $ip, $userAgent, $details]);
    } catch (Exception $e) {}
}

// 14. Two-Factor Authentication (2FA) Helpers: Base32 & RFC 6238 TOTP Engine
function base32_decode(string $b32): string {
    $b32 = strtoupper(trim($b32));
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $binary = '';
    $buffer = 0;
    $bufferSize = 0;

    for ($i = 0; $i < strlen($b32); $i++) {
        $char = $b32[$i];
        if ($char === '=') break;
        $val = strpos($alphabet, $char);
        if ($val === false) continue;

        $buffer = ($buffer << 5) | $val;
        $bufferSize += 5;

        if ($bufferSize >= 8) {
            $bufferSize -= 8;
            $binary .= chr(($buffer >> $bufferSize) & 0xFF);
        }
    }
    return $binary;
}

function base32_encode(string $data): string {
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

function generate_totp_secret(int $length = 16): string {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $secret = '';
    for ($i = 0; $i < $length; $i++) {
        $secret .= $alphabet[random_int(0, strlen($alphabet) - 1)];
    }
    return $secret;
}

function get_totp_code(string $secret, ?int $timeSlice = null): string {
    if ($timeSlice === null) {
        $timeSlice = (int)floor(time() / 30);
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

function verify_totp_code(string $secret, string $code, int $discrepancy = 1): bool {
    $cleanCode = trim(preg_replace('/[^0-9]/', '', $code));
    if (strlen($cleanCode) !== 6) return false;
    $currentTimeSlice = (int)floor(time() / 30);
    for ($i = -$discrepancy; $i <= $discrepancy; $i++) {
        $calcCode = get_totp_code($secret, $currentTimeSlice + $i);
        if (hash_equals($calcCode, $cleanCode)) {
            return true;
        }
    }
    return false;
}

function get_totp_auth_url(string $email, string $secret): string {
    $issuer = APP_NAME;
    $label = rawurlencode($issuer) . ':' . rawurlencode($email);
    return "otpauth://totp/{$label}?secret={$secret}&issuer=" . rawurlencode($issuer) . "&algorithm=SHA1&digits=6&period=30";
}

function get_2fa_record(int $userId): ?array {
    $db = get_db();
    if (!$db) return null;
    $stmt = $db->prepare("SELECT * FROM two_factor_auth WHERE user_id = ? LIMIT 1");
    $stmt->execute([$userId]);
    $rec = $stmt->fetch();
    if (!$rec) {
        // Check if user is admin
        $uStmt = $db->prepare("SELECT role FROM users WHERE id = ?");
        $uStmt->execute([$userId]);
        $user = $uStmt->fetch();
        if ($user && $user['role'] === 'admin') {
            $secret = generate_totp_secret();
            $ins = $db->prepare("INSERT INTO two_factor_auth (user_id, auth_type, secret_code, is_enabled, is_totp_setup, created_at) VALUES (?, 'authenticator', ?, 1, 0, NOW())");
            $ins->execute([$userId, $secret]);
            $stmt->execute([$userId]);
            $rec = $stmt->fetch();
        }
    }
    return $rec ?: null;
}

function is_admin_2fa_enforced(int $userId): bool {
    $rec = get_2fa_record($userId);
    if (!$rec) return true; // Default enforced for admin
    return (bool)$rec['is_enabled'];
}

function is_admin_totp_setup(int $userId): bool {
    $rec = get_2fa_record($userId);
    if (!$rec) return false;
    return !empty($rec['is_totp_setup']) && !empty($rec['secret_code']);
}

function get_or_create_totp_secret(int $userId): string {
    $db = get_db();
    if (!$db) return '';
    $rec = get_2fa_record($userId);
    if (!empty($rec['secret_code'])) {
        return $rec['secret_code'];
    }
    $secret = generate_totp_secret();
    $upd = $db->prepare("UPDATE two_factor_auth SET secret_code = ?, updated_at = NOW() WHERE user_id = ?");
    $upd->execute([$secret, $userId]);
    return $secret;
}

function confirm_admin_totp_setup(int $userId, string $code): array {
    $db = get_db();
    if (!$db) return ['success' => false, 'message' => 'Database error.'];
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

function verify_admin_totp_login(int $userId, string $code): array {
    $db = get_db();
    if (!$db) return ['success' => false, 'message' => 'Database error.'];

    $cleanCode = trim(preg_replace('/[^0-9]/', '', $code));
    if (strlen($cleanCode) !== 6) {
        return ['success' => false, 'message' => 'Please enter the 6-digit code from your authenticator app.'];
    }

    $rec = get_2fa_record($userId);
    if (!$rec || empty($rec['secret_code']) || empty($rec['is_totp_setup'])) {
        return ['success' => false, 'message' => 'Authenticator app is not configured. Please complete setup.'];
    }

    if ((int)$rec['attempts'] >= 5) {
        log_security_event($userId, '2FA_MAX_ATTEMPTS_EXCEEDED', 'high', 'Max 2FA failed attempts reached.');
        return ['success' => false, 'message' => 'Too many failed attempts. Please wait 1 minute before trying again or use an Emergency Backup Code.'];
    }

    if (verify_totp_code($rec['secret_code'], $cleanCode, 1)) {
        $db->prepare("UPDATE two_factor_auth SET attempts = 0, last_verified_at = NOW() WHERE user_id = ?")->execute([$userId]);
        log_security_event($userId, '2FA_VERIFICATION_SUCCESS', 'low', 'Authenticated via Mobile Authenticator TOTP.');
        log_audit($userId, '2FA_VERIFICATION_SUCCESS', 'two_factor_auth', $userId, 'Admin authenticated via Authenticator TOTP');
        return ['success' => true, 'message' => 'Verification successful!'];
    } else {
        $newAttempts = (int)$rec['attempts'] + 1;
        $db->prepare("UPDATE two_factor_auth SET attempts = ? WHERE user_id = ?")->execute([$newAttempts, $userId]);
        $remaining = max(0, 5 - $newAttempts);
        log_security_event($userId, '2FA_VERIFICATION_FAILED', 'medium', "Invalid TOTP entered. Attempt {$newAttempts} of 5.");
        return ['success' => false, 'message' => "Invalid code. {$remaining} attempt" . ($remaining === 1 ? '' : 's') . " remaining."];
    }
}

function reset_admin_totp(int $userId): void {
    $db = get_db();
    if (!$db) return;
    $newSecret = generate_totp_secret();
    $db->prepare("UPDATE two_factor_auth SET secret_code = ?, is_totp_setup = 0, attempts = 0 WHERE user_id = ?")->execute([$newSecret, $userId]);
    log_security_event($userId, '2FA_TOTP_RESET', 'high', 'Admin mobile authenticator was reset for re-pairing.');
    log_audit($userId, '2FA_TOTP_RESET', 'two_factor_auth', $userId, 'Mobile Authenticator reset for QR re-scan');
}

function generate_2fa_otp(int $userId): string {
    $db = get_db();
    if (!$db) return '';

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

function verify_2fa_otp(int $userId, string $code): array {
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
    if ((int)$rec['attempts'] >= 5) {
        log_security_event($userId, '2FA_MAX_ATTEMPTS_EXCEEDED', 'high', 'Max 2FA failed attempts reached. Code invalidated.');
        return ['success' => false, 'message' => 'Security limit exceeded: Too many incorrect attempts. Please click "Resend Code" to obtain a new code.'];
    }

    // Check expiration
    if (empty($rec['otp_expires_at']) || strtotime($rec['otp_expires_at']) < time()) {
        log_security_event($userId, '2FA_CODE_EXPIRED', 'medium', 'Expired OTP code was entered.');
        return ['success' => false, 'message' => 'Verification code has expired. Please request a new code.'];
    }

    // Compare
    if (hash_equals((string)$rec['current_otp'], $cleanCode)) {
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
        $newAttempts = (int)$rec['attempts'] + 1;
        $db->prepare("UPDATE two_factor_auth SET attempts = ? WHERE user_id = ?")->execute([$newAttempts, $userId]);
        $remaining = max(0, 5 - $newAttempts);

        log_security_event($userId, '2FA_VERIFICATION_FAILED', 'medium', "Invalid OTP entered. Attempt {$newAttempts} of 5.");
        return ['success' => false, 'message' => "Incorrect verification code. {$remaining} attempt" . ($remaining === 1 ? '' : 's') . " remaining."];
    }
}

function generate_2fa_backup_codes(int $userId, int $count = 5): array {
    $db = get_db();
    if (!$db) return [];

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

function verify_2fa_backup_code(int $userId, string $enteredCode): array {
    $db = get_db();
    if (!$db) return ['success' => false, 'message' => 'Database error.'];

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

function get_remaining_backup_codes_count(int $userId): int {
    $rec = get_2fa_record($userId);
    if (!$rec || empty($rec['backup_codes'])) return 0;
    $codes = json_decode($rec['backup_codes'], true);
    if (!is_array($codes)) return 0;
    $unused = 0;
    foreach ($codes as $c) {
        if (empty($c['used'])) $unused++;
    }
    return $unused;
}
