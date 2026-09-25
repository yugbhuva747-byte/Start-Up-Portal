<?php
/**
 * Authentication: Two-Factor Verification (TOTP Authenticator App)
 * Prompts for 6-digit code from Google Authenticator, Microsoft Authenticator, etc.
 */
require_once __DIR__ . '/../config.php';

// Check if user is in pending 2FA state or admin requiring verification
$userId = $_SESSION['2fa_pending_user_id'] ?? null;
$userEmail = $_SESSION['2fa_pending_email'] ?? '';
$userName = $_SESSION['2fa_pending_name'] ?? 'Administrator';
$userRole = $_SESSION['2fa_pending_role'] ?? 'admin';

if (!$userId) {
    if (auth_check()) {
        $u = current_user();
        if ($u && $u['role'] === 'admin') {
            if (!empty($_SESSION['2fa_verified'])) {
                header('Location: ' . url('admin/dashboard.php'));
                exit;
            }
            $userId = (int)$u['id'];
            $userEmail = $u['email'];
            $userName = $u['name'];
            $userRole = $u['role'];
            $_SESSION['2fa_pending_user_id'] = $userId;
            $_SESSION['2fa_pending_email'] = $userEmail;
            $_SESSION['2fa_pending_name'] = $userName;
            $_SESSION['2fa_pending_role'] = $userRole;
        } else {
            header('Location: ' . url('index.php'));
            exit;
        }
    } else {
        header('Location: ' . url('auth/login.php'));
        exit;
    }
}

// If TOTP is NOT set up yet, redirect to QR scan setup
if (!is_admin_totp_setup($userId)) {
    header('Location: ' . url('auth/setup_2fa.php'));
    exit;
}

$error = '';
$flash = get_flash();
$rec = get_2fa_record($userId);
$currentAppCode = !empty($rec['secret_code']) ? get_totp_code($rec['secret_code']) : '';

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $action = $_POST['action'] ?? 'verify_totp';

        if ($action === 'verify_totp') {
            $d1 = $_POST['d1'] ?? '';
            $d2 = $_POST['d2'] ?? '';
            $d3 = $_POST['d3'] ?? '';
            $d4 = $_POST['d4'] ?? '';
            $d5 = $_POST['d5'] ?? '';
            $d6 = $_POST['d6'] ?? '';
            
            $combined = $d1 . $d2 . $d3 . $d4 . $d5 . $d6;
            if (empty($combined)) {
                $combined = $_POST['otp_code'] ?? '';
            }

            $res = verify_admin_totp_login($userId, $combined);

            if ($res['success']) {
                // Finalize authenticated session
                $_SESSION['user_id'] = $userId;
                $_SESSION['user_role'] = $userRole;
                $_SESSION['user_name'] = $userName;
                $_SESSION['2fa_verified'] = true;

                // Log session in DB
                $db = get_db();
                if ($db) {
                    $sessionToken = bin2hex(random_bytes(32));
                    $_SESSION['session_token'] = $sessionToken;
                    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
                    $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 255);
                    $insSession = $db->prepare("
                        INSERT INTO login_sessions (user_id, session_token, ip_address, user_agent, created_at, last_active_at)
                        VALUES (?, ?, ?, ?, NOW(), NOW())
                    ");
                    $insSession->execute([$userId, $sessionToken, $ip, $ua]);
                }

                unset($_SESSION['2fa_pending_user_id'], $_SESSION['2fa_pending_email'], $_SESSION['2fa_pending_role'], $_SESSION['2fa_pending_name']);

                log_audit($userId, 'USER_LOGIN_2FA', 'users', $userId, 'Admin authenticated successfully via Authenticator App');
                set_flash('success', 'Two-Factor Authentication verified. Welcome back!');
                header('Location: ' . url('admin/dashboard.php'));
                exit;
            } else {
                $error = $res['message'];
            }
        } elseif ($action === 'verify_backup') {
            $backupCode = trim($_POST['backup_code'] ?? '');
            $res = verify_2fa_backup_code($userId, $backupCode);

            if ($res['success']) {
                $_SESSION['user_id'] = $userId;
                $_SESSION['user_role'] = $userRole;
                $_SESSION['user_name'] = $userName;
                $_SESSION['2fa_verified'] = true;

                unset($_SESSION['2fa_pending_user_id'], $_SESSION['2fa_pending_email'], $_SESSION['2fa_pending_role'], $_SESSION['2fa_pending_name']);

                log_audit($userId, 'USER_LOGIN_2FA_BACKUP', 'users', $userId, 'Admin authenticated using emergency backup code');
                set_flash('success', 'Emergency recovery code accepted! Remember to generate new backup codes in Settings.');
                header('Location: ' . url('admin/dashboard.php'));
                exit;
            } else {
                $error = $res['message'];
            }
        } elseif ($action === 'cancel_login') {
            unset($_SESSION['2fa_pending_user_id'], $_SESSION['2fa_pending_email'], $_SESSION['2fa_pending_role'], $_SESSION['2fa_pending_name'], $_SESSION['2fa_verified']);
            session_destroy();
            header('Location: ' . url('auth/login.php'));
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Authenticator Verification • <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <style>
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background-color: #FAFAFB;
            color: #0F172A;
        }
        .code-input {
            font-family: 'JetBrains Mono', monospace;
        }
    </style>
</head>
<body class="bg-[#FAFAFB] text-slate-900 min-h-screen flex items-center justify-center p-4 selection:bg-indigo-100 selection:text-indigo-900">

    <div class="max-w-md w-full my-8" id="auth-container">
        
        <!-- Header Logo -->
        <div class="text-center mb-6">
            <a href="<?= url('index.php') ?>" class="inline-flex items-center space-x-2 group">
                <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center text-white shadow-sm shadow-indigo-600/20 group-hover:scale-105 transition">
                    <i data-lucide="shield-check" class="w-4 h-4"></i>
                </div>
                <div class="text-left">
                    <span class="text-sm font-black tracking-tight text-slate-900 flex items-center gap-1">
                        STARTUP <span class="text-indigo-600">×</span> ADMIN
                    </span>
                    <span class="block text-[8px] tracking-widest text-slate-400 uppercase font-bold">2FA Authentication</span>
                </div>
            </a>
        </div>

        <!-- Main Card -->
        <div class="bg-white border border-slate-200 rounded-2xl p-4 sm:p-6 md:p-8 shadow-sm relative overflow-hidden">
            
            <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-indigo-500 via-purple-500 to-emerald-500"></div>

            <div class="flex items-center justify-between mb-4">
                <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center border border-indigo-100">
                    <i data-lucide="smartphone" class="w-5 h-5"></i>
                </div>
                <span class="inline-flex items-center space-x-1 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-200">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse mr-1"></span>
                    App Protected
                </span>
            </div>

            <div class="mb-5">
                <h1 class="text-lg font-bold tracking-tight text-slate-900">Mobile Authenticator Code</h1>
                <p class="text-xs text-slate-500 mt-1">
                    Open <strong class="text-slate-800 font-semibold">Google Authenticator</strong> or <strong class="text-slate-800 font-semibold">Microsoft Authenticator</strong> on your phone and enter the current 6-digit code.
                </p>
            </div>

            <!-- Demo / Testing Helper -->
            <?php if (!empty($currentAppCode)): ?>
            <div class="mb-5 p-3.5 rounded-xl bg-indigo-50/70 border border-indigo-100 text-xs">
                <div class="flex items-center justify-between mb-1.5">
                    <div class="flex items-center space-x-1.5 text-[10px] font-bold uppercase tracking-wider text-indigo-700">
                        <i data-lucide="sparkles" class="w-3.5 h-3.5 text-indigo-600"></i>
                        <span>Testing Helper (Current App Code)</span>
                    </div>
                    <span class="text-[10px] text-indigo-500 font-mono">Changes every 30s</span>
                </div>
                <div class="flex items-center justify-between bg-white px-3 py-2 rounded-lg border border-indigo-200/60 shadow-xs">
                    <div>
                        <div class="text-[9px] text-slate-400 uppercase font-semibold">Matching Mobile Code</div>
                        <div class="text-base font-bold font-mono tracking-widest text-indigo-600" id="currentCodeDisplay"><?= htmlspecialchars($currentAppCode) ?></div>
                    </div>
                    <button type="button" onclick="autoFillOtp('<?= htmlspecialchars($currentAppCode) ?>')" class="px-2.5 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-[11px] font-bold shadow-sm transition flex items-center space-x-1">
                        <i data-lucide="zap" class="w-3.5 h-3.5"></i>
                        <span>Auto-Fill</span>
                    </button>
                </div>
            </div>
            <?php endif; ?>

            <!-- Alerts -->
            <?php if ($flash): ?>
                <div class="mb-4 p-3 rounded-lg text-xs font-semibold border <?= $flash['type'] === 'success' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200' ?> flex items-center space-x-2">
                    <i data-lucide="<?= $flash['type'] === 'success' ? 'check-circle' : 'alert-circle' ?>" class="w-3.5 h-3.5 flex-shrink-0"></i>
                    <span><?= htmlspecialchars($flash['message']) ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="mb-4 p-3 rounded-lg text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200 flex items-center space-x-2">
                    <i data-lucide="alert-circle" class="w-3.5 h-3.5 flex-shrink-0"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <!-- TOTP Form -->
            <div id="totpSection">
                <form action="<?= url('auth/verify_2fa.php') ?>" method="POST" id="totpForm" class="space-y-4">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="action" value="verify_totp">
                    <input type="hidden" name="otp_code" id="otp_code" value="">

                    <div>
                        <label class="block text-[10.5px] font-bold uppercase tracking-wider text-slate-600 mb-2 text-center">
                            6-Digit Security Code
                        </label>
                        <div class="grid grid-cols-6 gap-2 sm:gap-2.5 max-w-xs mx-auto">
                            <?php for ($i = 1; $i <= 6; $i++): ?>
                                <input type="text" 
                                       name="d<?= $i ?>" 
                                       id="digit_<?= $i ?>" 
                                       maxlength="1" 
                                       inputmode="numeric" 
                                       pattern="[0-9]*" 
                                       autocomplete="one-time-code"
                                       class="code-input w-full h-12 text-center text-lg font-bold bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-indigo-600 focus:ring-2 focus:ring-indigo-600/10 outline-none text-slate-900 transition">
                            <?php endfor; ?>
                        </div>
                    </div>

                    <button type="submit" id="btnSubmitOtp" class="w-full py-2.5 px-4 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow-sm transition flex items-center justify-center space-x-1.5 mt-4">
                        <i data-lucide="shield-check" class="w-3.5 h-3.5"></i>
                        <span>Verify & Enter Admin Dashboard</span>
                    </button>
                </form>
            </div>

            <!-- Emergency Backup Code Form (Initially Hidden) -->
            <div id="backupSection" class="hidden">
                <form action="<?= url('auth/verify_2fa.php') ?>" method="POST" class="space-y-3.5">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="action" value="verify_backup">

                    <div>
                        <label class="block text-[10.5px] font-bold uppercase tracking-wider text-slate-600 mb-1">
                            Emergency Backup Recovery Code
                        </label>
                        <div class="relative">
                            <i data-lucide="key" class="w-3.5 h-3.5 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                            <input type="text" name="backup_code" required placeholder="ABCD-1234"
                                   class="code-input w-full pl-9 pr-3 py-2.5 bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-600 focus:ring-2 focus:ring-indigo-600/10 rounded-xl text-xs font-bold uppercase tracking-wider text-slate-900 placeholder-slate-400 outline-none transition">
                        </div>
                        <p class="text-[10px] text-slate-400 mt-1">Use one of your 8-character single-use emergency backup recovery codes if you cannot access your mobile phone.</p>
                    </div>

                    <button type="submit" class="w-full py-2.5 px-4 bg-slate-900 hover:bg-black text-white text-xs font-bold rounded-xl shadow-sm transition flex items-center justify-center space-x-1.5">
                        <i data-lucide="check" class="w-3.5 h-3.5"></i>
                        <span>Authenticate with Recovery Code</span>
                    </button>
                </form>
            </div>

            <!-- Action Toggles -->
            <div class="mt-5 pt-4 border-t border-slate-100 flex items-center justify-center text-xs">
                <?php /* Re-scan QR Option (Commented for now)
                <a href="<?= url('auth/setup_2fa.php') ?>" class="text-indigo-600 hover:text-indigo-700 font-semibold text-[11px] flex items-center space-x-1 transition">
                    <i data-lucide="qr-code" class="w-3 h-3"></i>
                    <span>Re-scan QR Code</span>
                </a>
                */ ?>

                <!-- Toggle Recovery Option -->
                <button type="button" onclick="toggleBackupMode()" id="backupToggleBtn" class="text-slate-500 hover:text-slate-700 font-semibold text-[11px] flex items-center space-x-1 transition">
                    <i data-lucide="key" class="w-3 h-3"></i>
                    <span id="backupToggleText">Use Emergency Backup Code</span>
                </button>
            </div>

            <!-- Cancel Button -->
            <div class="mt-3 text-center">
                <form action="<?= url('auth/verify_2fa.php') ?>" method="POST" class="inline">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="action" value="cancel_login">
                    <button type="submit" class="text-rose-500 hover:text-rose-600 text-[10.5px] font-semibold transition flex items-center justify-center space-x-1 mx-auto">
                        <i data-lucide="arrow-left" class="w-3 h-3"></i>
                        <span>Cancel and return to sign in</span>
                    </button>
                </form>
            </div>

        </div>

        <!-- Security Footer -->
        <div class="mt-4 text-center text-[10px] text-slate-400 flex items-center justify-center space-x-2">
            <span class="flex items-center space-x-1">
                <i data-lucide="shield-check" class="w-3 h-3 text-emerald-600"></i>
                <span>RFC 6238 Protocol</span>
            </span>
            <span>•</span>
            <span class="flex items-center space-x-1">
                <i data-lucide="smartphone" class="w-3 h-3 text-indigo-500"></i>
                <span>Google Authenticator Protected</span>
            </span>
        </div>

    </div>

    <script>
        lucide.createIcons();
        gsap.from("#auth-container", { duration: 0.45, y: 15, opacity: 0, ease: "power2.out" });

        const digitInputs = [
            document.getElementById('digit_1'),
            document.getElementById('digit_2'),
            document.getElementById('digit_3'),
            document.getElementById('digit_4'),
            document.getElementById('digit_5'),
            document.getElementById('digit_6')
        ];

        if (digitInputs[0]) {
            setTimeout(() => digitInputs[0].focus(), 150);
        }

        digitInputs.forEach((input, index) => {
            if (!input) return;

            input.addEventListener('input', (e) => {
                const val = e.target.value.replace(/[^0-9]/g, '');
                e.target.value = val;

                if (val && index < digitInputs.length - 1) {
                    digitInputs[index + 1].focus();
                    digitInputs[index + 1].select();
                }

                syncCombinedOtp();

                if (isAllFilled()) {
                    document.getElementById('totpForm').submit();
                }
            });

            input.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && !input.value && index > 0) {
                    digitInputs[index - 1].focus();
                    digitInputs[index - 1].value = '';
                    syncCombinedOtp();
                }
            });

            input.addEventListener('paste', (e) => {
                e.preventDefault();
                const pasteData = (e.clipboardData || window.clipboardData).getData('text').trim().replace(/[^0-9]/g, '');
                if (pasteData) {
                    autoFillOtp(pasteData.substring(0, 6));
                }
            });
        });

        function syncCombinedOtp() {
            const combined = digitInputs.map(i => i.value).join('');
            document.getElementById('otp_code').value = combined;
        }

        function isAllFilled() {
            return digitInputs.every(i => i.value.length === 1);
        }

        function autoFillOtp(code) {
            const clean = String(code).trim().replace(/[^0-9]/g, '');
            for (let i = 0; i < 6; i++) {
                if (digitInputs[i]) {
                    digitInputs[i].value = clean[i] || '';
                }
            }
            syncCombinedOtp();
            if (clean.length >= 6) {
                digitInputs[5].focus();
            }
        }

        let isBackupMode = false;
        function toggleBackupMode() {
            isBackupMode = !isBackupMode;
            const totpSec = document.getElementById('totpSection');
            const backupSec = document.getElementById('backupSection');
            const toggleText = document.getElementById('backupToggleText');

            if (isBackupMode) {
                totpSec.classList.add('hidden');
                backupSec.classList.remove('hidden');
                toggleText.textContent = 'Back to Authenticator PIN';
            } else {
                backupSec.classList.add('hidden');
                totpSec.classList.remove('hidden');
                toggleText.textContent = 'Use Emergency Backup Code';
                if (digitInputs[0]) digitInputs[0].focus();
            }
        }
    </script>
</body>
</html>
