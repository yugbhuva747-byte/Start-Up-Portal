<?php
/**
 * Global Helper Functions
 * Includes Hash ID, Authentication, Audit Logging, Flash Messages, and Sanitization
 */

// 1. Dynamic URL Generator
function url(string $path = ''): string {
    $cleanPath = ltrim($path, '/');
    return BASE_URL . ($cleanPath ? '/' . $cleanPath : '');
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
