<?php
/**
 * Authentication: First-Time Two-Factor Setup (Mobile QR Code Scan)
 * Scans with Google Authenticator, Microsoft Authenticator, Authy, etc.
 */
require_once __DIR__ . '/../config.php';

// Check if user is in pending 2FA state or logged-in admin needing setup
$userId = $_SESSION['2fa_pending_user_id'] ?? null;
$userEmail = $_SESSION['2fa_pending_email'] ?? '';
$userName = $_SESSION['2fa_pending_name'] ?? 'Administrator';
$userRole = $_SESSION['2fa_pending_role'] ?? 'admin';

if (!$userId) {
    if (auth_check()) {
        $u = current_user();
        if ($u && $u['role'] === 'admin') {
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

// If already configured and active, forward to verification
if (is_admin_totp_setup($userId)) {
    header('Location: ' . url('auth/verify_2fa.php'));
    exit;
}

$error = '';
$flash = get_flash();

// Generate / retrieve Base32 secret
$secret = get_or_create_totp_secret($userId);
$totpUri = get_totp_auth_url($userEmail, $secret);
$currentTotpCode = get_totp_code($secret); // For demo auto-fill convenience

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $action = $_POST['action'] ?? 'confirm_setup';

        if ($action === 'confirm_setup') {
            $d1 = $_POST['d1'] ?? '';
            $d2 = $_POST['d2'] ?? '';
            $d3 = $_POST['d3'] ?? '';
            $d4 = $_POST['d4'] ?? '';
            $d5 = $_POST['d5'] ?? '';
            $d6 = $_POST['d6'] ?? '';
            $code = $d1 . $d2 . $d3 . $d4 . $d5 . $d6;
            if (empty($code)) {
                $code = trim($_POST['otp_code'] ?? '');
            }

            $res = confirm_admin_totp_setup($userId, $code);

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
                    $ins = $db->prepare("INSERT INTO login_sessions (user_id, session_token, ip_address, user_agent, created_at, last_active_at) VALUES (?, ?, ?, ?, NOW(), NOW())");
                    $ins->execute([$userId, $sessionToken, $ip, $ua]);
                }

                unset($_SESSION['2fa_pending_user_id'], $_SESSION['2fa_pending_email'], $_SESSION['2fa_pending_role'], $_SESSION['2fa_pending_name']);

                set_flash('success', 'Mobile Authenticator successfully linked! Two-Factor Authentication is now active.');
                header('Location: ' . url('admin/dashboard.php'));
                exit;
            } else {
                $error = $res['message'];
            }
        } elseif ($action === 'cancel_setup') {
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
    <title>Set Up Authenticator App • <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <!-- Lightweight Client-Side QR Generator -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
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
        #qrcode canvas, #qrcode img {
            margin: 0 auto;
            border-radius: 12px;
        }
    </style>
</head>
<body class="bg-[#FAFAFB] text-slate-900 min-h-screen flex items-center justify-center p-4 selection:bg-indigo-100 selection:text-indigo-900">

    <div class="max-w-lg w-full my-8" id="auth-container">
        
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
                    <span class="block text-[8px] tracking-widest text-slate-400 uppercase font-bold">2FA App Setup</span>
                </div>
            </a>
        </div>

        <!-- Main Card -->
        <div class="bg-white border border-slate-200 rounded-2xl p-6 md:p-8 shadow-sm relative overflow-hidden">
            
            <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-indigo-500 via-purple-500 to-emerald-500"></div>

            <div class="flex items-center justify-between mb-4">
                <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center border border-indigo-100">
                    <i data-lucide="qr-code" class="w-5 h-5"></i>
                </div>
                <span class="inline-flex items-center space-x-1 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider bg-indigo-50 text-indigo-700 border border-indigo-200">
                    <i data-lucide="sparkles" class="w-3 h-3 text-indigo-600 mr-1"></i>
                    First-Time Setup
                </span>
            </div>

            <div class="mb-6">
                <h1 class="text-xl font-bold tracking-tight text-slate-900">Set Up Mobile Authenticator</h1>
                <p class="text-xs text-slate-500 mt-1">
                    Scan the QR code below using <strong class="text-slate-800 font-semibold">Google Authenticator</strong>, <strong class="text-slate-800 font-semibold">Microsoft Authenticator</strong>, or any TOTP app on your mobile phone.
                </p>
            </div>

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

            <!-- QR Code Box -->
            <div class="p-5 rounded-2xl bg-slate-50 border border-slate-200/80 text-center mb-6">
                
                <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-3 flex items-center justify-center space-x-1.5">
                    <i data-lucide="camera" class="w-3.5 h-3.5 text-indigo-600"></i>
                    <span>Scan with your phone camera</span>
                </div>

                <!-- QR Code Container -->
                <div class="p-3 bg-white rounded-xl inline-block shadow-sm border border-slate-200">
                    <div id="qrcode" class="w-[180px] h-[180px] flex items-center justify-center"></div>
                </div>

                <!-- Manual Key Section -->
                <div class="mt-4 pt-3 border-t border-slate-200/70">
                    <div class="text-[10px] text-slate-400 font-medium mb-1">Cannot scan the code? Enter this secret key manually in your app:</div>
                    <div class="inline-flex items-center space-x-2 bg-white px-3 py-1.5 rounded-lg border border-slate-200">
                        <span class="font-mono text-xs font-bold tracking-widest text-slate-800" id="secretKeyText"><?= htmlspecialchars($secret) ?></span>
                        <button type="button" onclick="copySecretKey()" class="text-indigo-600 hover:text-indigo-700 text-[10px] font-bold transition flex items-center space-x-0.5" title="Copy Key">
                            <i data-lucide="copy" class="w-3 h-3"></i>
                            <span id="copySecretLabel">Copy</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Demo Quick-Fill Helper -->
            <div class="mb-6 p-3 rounded-xl bg-indigo-50/70 border border-indigo-100 text-xs">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-1.5 text-[10.5px] font-bold text-indigo-800">
                        <i data-lucide="smartphone" class="w-3.5 h-3.5 text-indigo-600"></i>
                        <span>Testing on PC without phone right now?</span>
                    </div>
                    <button type="button" onclick="autoFillCurrentCode('<?= htmlspecialchars($currentTotpCode) ?>')" class="px-2 py-1 rounded bg-indigo-600 hover:bg-indigo-700 text-white text-[10px] font-bold transition flex items-center space-x-1">
                        <i data-lucide="zap" class="w-3 h-3"></i>
                        <span>Auto-Fill Current Code (<?= htmlspecialchars($currentTotpCode) ?>)</span>
                    </button>
                </div>
            </div>

            <!-- Confirmation 6-Digit Form -->
            <form action="<?= url('auth/setup_2fa.php') ?>" method="POST" id="confirmForm" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="confirm_setup">
                <input type="hidden" name="otp_code" id="otp_code" value="">

                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 mb-1.5 text-center">
                        Enter 6-digit code shown on your phone
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

                <button type="submit" id="btnSubmit" class="w-full py-2.5 px-4 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow-sm transition flex items-center justify-center space-x-1.5 mt-4">
                    <i data-lucide="check-circle" class="w-3.5 h-3.5"></i>
                    <span>Verify Code & Activate Authenticator</span>
                </button>
            </form>

            <!-- Cancel Button -->
            <div class="mt-4 pt-3 border-t border-slate-100 text-center">
                <form action="<?= url('auth/setup_2fa.php') ?>" method="POST" class="inline">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="action" value="cancel_setup">
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
                <span>RFC 6238 Standard Protocol</span>
            </span>
            <span>•</span>
            <span class="flex items-center space-x-1">
                <i data-lucide="smartphone" class="w-3 h-3 text-indigo-500"></i>
                <span>Google & Microsoft Authenticator Compatible</span>
            </span>
        </div>

    </div>

    <script>
        lucide.createIcons();
        gsap.from("#auth-container", { duration: 0.45, y: 15, opacity: 0, ease: "power2.out" });

        // Generate QR Code Client-Side
        const totpUri = <?= json_encode($totpUri) ?>;
        const qrcodeContainer = document.getElementById('qrcode');
        
        try {
            new QRCode(qrcodeContainer, {
                text: totpUri,
                width: 180,
                height: 180,
                colorDark : "#0F172A",
                colorLight : "#FFFFFF",
                correctLevel : QRCode.CorrectLevel.M
            });
        } catch (e) {
            // Fallback to QR server API image if offline library fails
            qrcodeContainer.innerHTML = '<img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=' + encodeURIComponent(totpUri) + '" alt="TOTP QR Code" class="w-[180px] h-[180px] rounded-lg" />';
        }

        // Copy Secret Key
        function copySecretKey() {
            const key = document.getElementById('secretKeyText').textContent.trim();
            navigator.clipboard.writeText(key).then(() => {
                const label = document.getElementById('copySecretLabel');
                label.textContent = 'Copied!';
                setTimeout(() => { label.textContent = 'Copy'; }, 2000);
            });
        }

        // Digit inputs navigation
        const digitInputs = [
            document.getElementById('digit_1'),
            document.getElementById('digit_2'),
            document.getElementById('digit_3'),
            document.getElementById('digit_4'),
            document.getElementById('digit_5'),
            document.getElementById('digit_6')
        ];

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
                    document.getElementById('confirmForm').submit();
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
                    autoFillCurrentCode(pasteData.substring(0, 6));
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

        function autoFillCurrentCode(code) {
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
    </script>
</body>
</html>
