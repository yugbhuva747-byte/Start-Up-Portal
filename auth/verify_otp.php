<?php
/**
 * Authentication: 6-Digit One-Time Password (OTP) Verification
 * Handles One-Time Password verification for New Account Registrations via SMS & Email
 * NEXORA Institutional Venture Ecosystem
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/mailer.php';

// Detect whether this is a New Registration Verification or General OTP challenge
$isRegistration = !empty($_SESSION['reg_verify_user_id']);

$userId = $_SESSION['reg_verify_user_id'] ?? $_SESSION['otp_pending_user_id'] ?? null;
$userEmail = $_SESSION['reg_verify_email'] ?? $_SESSION['otp_pending_email'] ?? '';
$userPhone = $_SESSION['reg_verify_phone'] ?? $_SESSION['otp_pending_phone'] ?? '';
$userName = $_SESSION['reg_verify_name'] ?? $_SESSION['otp_pending_name'] ?? 'Partner';
$userRole = $_SESSION['reg_verify_role'] ?? $_SESSION['otp_pending_role'] ?? 'founder';
$devOtp = $_SESSION['reg_verify_code'] ?? $_SESSION['otp_pending_code'] ?? '';
$remember = !empty($_SESSION['otp_pending_remember']);

// Redirect if already authenticated or if no pending user to verify
if (!$userId) {
    if (auth_check()) {
        $u = current_user();
        $redirect = match($u['role'] ?? '') {
            'founder' => 'founder/dashboard.php',
            'investor' => 'investor/discover.php',
            'admin' => 'admin/dashboard.php',
            default => 'index.php'
        };
        header('Location: ' . url($redirect));
        exit;
    }
    header('Location: ' . url('auth/login.php'));
    exit;
}

$error = '';
$success = '';
$flash = get_flash();

// Fallback display if user phone was empty in database
if (empty($userPhone)) {
    $userPhone = '+91 98765 43210';
}

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $action = $_POST['action'] ?? 'verify_otp';

        if ($action === 'verify_otp') {
            $d1 = trim($_POST['d1'] ?? '');
            $d2 = trim($_POST['d2'] ?? '');
            $d3 = trim($_POST['d3'] ?? '');
            $d4 = trim($_POST['d4'] ?? '');
            $d5 = trim($_POST['d5'] ?? '');
            $d6 = trim($_POST['d6'] ?? '');

            $code = $d1 . $d2 . $d3 . $d4 . $d5 . $d6;
            if (empty($code)) {
                $code = trim($_POST['otp_code'] ?? '');
            }

            $res = verify_2fa_otp((int)$userId, $code);

            if ($res['success']) {
                $db = get_db();
                if ($db) {
                    // Mark user as verified in database
                    try {
                        $db->prepare("
                            UPDATE users 
                            SET is_verified = 1, 
                                email_verified_at = NOW(), 
                                status = 'active',
                                updated_at = NOW() 
                            WHERE id = ?
                        ")->execute([$userId]);
                    } catch (\Throwable $e) {}
                }

                // Finalize authenticated user session
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int)$userId;
                $_SESSION['user_role'] = $userRole;
                $_SESSION['user_name'] = $userName;
                $_SESSION['2fa_verified'] = true;

                // Set persistent remember-me cookie if requested
                if ($remember) {
                    set_remember_me_cookie((int)$userId, 30);
                }

                // Register active session in login_sessions table
                if ($db) {
                    $sessionToken = bin2hex(random_bytes(32));
                    $_SESSION['session_token'] = $sessionToken;
                    $clientIp = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
                    $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 255);
                    try {
                        $db->prepare("
                            INSERT INTO login_sessions (user_id, session_token, ip_address, user_agent, created_at, last_active_at)
                            VALUES (?, ?, ?, ?, NOW(), NOW())
                        ")->execute([$userId, $sessionToken, $clientIp, $ua]);
                    } catch (\Throwable $e) {}
                }

                // Clear pending session variables
                unset(
                    $_SESSION['reg_verify_user_id'],
                    $_SESSION['reg_verify_email'],
                    $_SESSION['reg_verify_phone'],
                    $_SESSION['reg_verify_role'],
                    $_SESSION['reg_verify_name'],
                    $_SESSION['reg_verify_code'],
                    $_SESSION['reg_verify_sent_at'],
                    $_SESSION['otp_pending_user_id'],
                    $_SESSION['otp_pending_email'],
                    $_SESSION['otp_pending_phone'],
                    $_SESSION['otp_pending_role'],
                    $_SESSION['otp_pending_name'],
                    $_SESSION['otp_pending_remember'],
                    $_SESSION['otp_pending_code'],
                    $_SESSION['otp_sent_at']
                );

                log_audit($userId, 'USER_REGISTER_VERIFIED', 'users', $userId, ucfirst($userRole) . ' verified registration via 6-digit OTP');
                set_flash('success', 'Account verified successfully! Welcome to ' . APP_NAME . ', ' . htmlspecialchars($userName) . '.');

                $redirect = match($userRole) {
                    'investor' => 'investor/dashboard.php',
                    'founder' => 'founder/dashboard.php',
                    'admin' => 'admin/dashboard.php',
                    default => 'index.php'
                };
                header('Location: ' . url($redirect));
                exit;
            } else {
                $error = $res['message'];
            }
        } elseif ($action === 'resend_otp') {
            // Check rate limiting (at least 15 seconds between resends)
            $lastSent = $_SESSION['reg_verify_sent_at'] ?? $_SESSION['otp_sent_at'] ?? 0;
            if (time() - $lastSent < 15) {
                $wait = 15 - (time() - $lastSent);
                $error = "Please wait {$wait} seconds before requesting a new verification code.";
            } else {
                // Generate fresh 6-digit OTP
                $newOtp = generate_2fa_otp((int)$userId);

                // Dispatch Email & SMS
                if ($isRegistration) {
                    send_registration_otp_email($userEmail, $userName, $newOtp, $userRole);
                    send_registration_otp_sms($userPhone, $userName, $newOtp, $userRole);
                    $_SESSION['reg_verify_code'] = $newOtp;
                    $_SESSION['reg_verify_sent_at'] = time();
                } else {
                    send_login_otp_email($userEmail, $userName, $newOtp, $userRole);
                    send_login_otp_sms($userPhone, $userName, $newOtp, $userRole);
                    $_SESSION['otp_pending_code'] = $newOtp;
                    $_SESSION['otp_sent_at'] = time();
                }

                $devOtp = $newOtp;
                $success = 'A fresh 6-digit verification code has been dispatched to your email and mobile number.';
            }
        } elseif ($action === 'cancel_login') {
            unset(
                $_SESSION['reg_verify_user_id'],
                $_SESSION['reg_verify_email'],
                $_SESSION['reg_verify_phone'],
                $_SESSION['reg_verify_role'],
                $_SESSION['reg_verify_name'],
                $_SESSION['reg_verify_code'],
                $_SESSION['reg_verify_sent_at'],
                $_SESSION['otp_pending_user_id'],
                $_SESSION['otp_pending_email'],
                $_SESSION['otp_pending_phone'],
                $_SESSION['otp_pending_role'],
                $_SESSION['otp_pending_name'],
                $_SESSION['otp_pending_remember'],
                $_SESSION['otp_pending_code'],
                $_SESSION['otp_sent_at']
            );
            session_destroy();
            header('Location: ' . url('auth/login.php'));
            exit;
        }
    }
}

// Visual Theme Configuration based on User Role
$isInvestor = ($userRole === 'investor');
$themeGradient = $isInvestor 
    ? 'from-emerald-500 via-teal-500 to-blue-600' 
    : 'from-blue-600 via-indigo-600 to-violet-600';

$badgeBg = $isInvestor 
    ? 'bg-emerald-50 text-emerald-700 border-emerald-200' 
    : 'bg-indigo-50 text-indigo-700 border-indigo-200';

$badgeIcon = $isRegistration ? 'sparkles' : ($isInvestor ? 'briefcase' : 'rocket');
$badgeLabel = $isRegistration 
    ? ($isInvestor ? 'NEW INVESTOR VERIFICATION' : 'NEW FOUNDER VERIFICATION')
    : ($isInvestor ? 'INVESTOR SECURITY GATEWAY' : 'FOUNDER SECURITY GATEWAY');

$btnClass = $isInvestor
    ? 'bg-gradient-to-r from-emerald-600 to-teal-700 hover:from-emerald-700 hover:to-teal-800 shadow-emerald-600/25'
    : 'bg-gradient-to-r from-indigo-600 to-blue-700 hover:from-indigo-700 hover:to-blue-800 shadow-indigo-600/25';

$maskedEmail = mask_email($userEmail);
$maskedPhone = mask_phone($userPhone);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Registration (OTP) • <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <!-- EmailJS Official Browser SDK -->
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/@emailjs/browser@4/dist/email.min.js"></script>
    <link rel="stylesheet" href="../assets/css/fonts.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { 
            font-family: 'Satoshi', 'Plus Jakarta Sans', sans-serif; 
            background-color: #F8FAFC;
            color: #0F172A;
        }
        .font-mono-otp {
            font-family: 'JetBrains Mono', Consolas, Monaco, monospace;
        }
        .otp-digit:focus {
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.18);
            border-color: #4F46E5;
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 min-h-screen flex items-center justify-center p-4 selection:bg-indigo-100 selection:text-indigo-900">

    <div class="max-w-md w-full my-8" id="otp-container">
        
        <!-- Header Logo -->
        <div class="text-center mb-6">
            <a href="<?= url('index.php') ?>" class="inline-flex items-center space-x-2 group">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-slate-900 to-indigo-950 flex items-center justify-center text-white shadow-md shadow-indigo-950/20 group-hover:scale-105 transition">
                    <i data-lucide="shield-check" class="w-5 h-5 text-indigo-400"></i>
                </div>
                <div class="text-left">
                    <span class="text-base font-black tracking-tight text-slate-900 flex items-center gap-1">
                        STARTUP <span class="text-indigo-600">×</span> INVESTOR
                    </span>
                    <span class="block text-[9px] tracking-widest text-slate-400 uppercase font-bold">One-Time Registration Verification</span>
                </div>
            </a>
        </div>

        <!-- Main Card -->
        <div class="bg-white border border-slate-200/90 rounded-2xl p-6 sm:p-8 shadow-xl shadow-slate-200/50 relative overflow-hidden">
            
            <!-- Top Gradient Bar -->
            <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r <?= $themeGradient ?>"></div>

            <!-- Role Badge & Status -->
            <div class="flex items-center justify-between mb-4">
                <div class="w-11 h-11 rounded-xl <?= $isInvestor ? 'bg-emerald-50 text-emerald-600 border-emerald-100' : 'bg-indigo-50 text-indigo-600 border-indigo-100' ?> flex items-center justify-center border shadow-xs">
                    <i data-lucide="<?= $badgeIcon ?>" class="w-5 h-5"></i>
                </div>
                <span class="inline-flex items-center space-x-1 px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider <?= $badgeBg ?> border">
                    <span class="w-1.5 h-1.5 rounded-full <?= $isInvestor ? 'bg-emerald-500' : 'bg-indigo-500' ?> animate-pulse mr-1"></span>
                    <?= $badgeLabel ?>
                </span>
            </div>

            <!-- Title & Delivery Information -->
            <div class="mb-5">
                <h1 class="text-xl font-extrabold tracking-tight text-slate-900">
                    <?= $isRegistration ? 'Verify Your Registration' : 'Enter Verification Code' ?>
                </h1>
                <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                    <?= $isRegistration 
                        ? 'Welcome to NEXORA! A 6-digit One-Time Password (OTP) has been dispatched to your mobile number and email to activate your account.' 
                        : 'We sent a 6-digit One-Time Password (OTP) to your registered mobile number and email.' 
                    ?>
                </p>
            </div>

            <!-- Destination Channels (Masked Details) -->
            <div class="bg-slate-50 border border-slate-200/80 rounded-xl p-3 mb-5 space-y-1.5 text-xs">
                <div class="flex items-center justify-between text-slate-700">
                    <div class="flex items-center space-x-2">
                        <i data-lucide="smartphone" class="w-4 h-4 text-emerald-600"></i>
                        <span class="text-slate-500">Text Message (SMS):</span>
                    </div>
                    <span class="font-mono-otp font-bold text-slate-900"><?= htmlspecialchars($maskedPhone) ?></span>
                </div>
                <div class="flex items-center justify-between text-slate-700 pt-1 border-t border-slate-200/60">
                    <div class="flex items-center space-x-2">
                        <i data-lucide="mail" class="w-4 h-4 text-indigo-600"></i>
                        <span class="text-slate-500">Email Address:</span>
                    </div>
                    <span class="font-mono-otp font-bold text-slate-900"><?= htmlspecialchars($maskedEmail) ?></span>
                </div>
            </div>

            <!-- Delivery Alert -->
            <div class="mb-5 p-3.5 rounded-xl bg-blue-50/80 border border-blue-200 text-xs text-blue-900 flex items-start space-x-2.5">
                <i data-lucide="mail-check" class="w-4 h-4 text-blue-600 flex-shrink-0 mt-0.5"></i>
                <div class="leading-relaxed">
                    We have dispatched your 6-digit verification code to <strong><?= htmlspecialchars($userEmail) ?></strong> and <strong><?= htmlspecialchars($userPhone) ?></strong>. Please check your inbox (and Spam/Junk folder) and enter the code below.
                </div>
            </div>

            <!-- Feedback Alerts -->
            <?php if (!empty($error)): ?>
            <div class="mb-5 p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-xs text-rose-700 flex items-start space-x-2.5 animate-shake" id="errorBox">
                <i data-lucide="alert-circle" class="w-4 h-4 text-rose-600 flex-shrink-0 mt-0.5"></i>
                <div class="font-medium"><?= htmlspecialchars($error) ?></div>
            </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
            <div class="mb-5 p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-xs text-emerald-800 flex items-start space-x-2.5">
                <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-600 flex-shrink-0 mt-0.5"></i>
                <div class="font-medium"><?= htmlspecialchars($success) ?></div>
            </div>
            <?php endif; ?>

            <!-- OTP Input Form -->
            <form action="<?= url('auth/verify_otp.php') ?>" method="POST" id="otpForm" class="space-y-5">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="verify_otp">
                <input type="hidden" name="otp_code" id="otp_code" value="">

                <!-- 6 Digits Boxes -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-2 uppercase tracking-wider text-center">
                        Enter 6-Digit Code
                    </label>
                    <div class="grid grid-cols-6 gap-2 sm:gap-2.5" id="digitInputsGroup">
                        <?php for ($i = 1; $i <= 6; $i++): ?>
                        <input type="text" 
                               name="d<?= $i ?>" 
                               id="d<?= $i ?>" 
                               maxlength="1" 
                               inputmode="numeric" 
                               pattern="[0-9]*" 
                               autocomplete="one-time-code"
                               class="otp-digit w-full h-13 sm:h-14 text-center font-mono-otp text-2xl font-black bg-slate-50 border-2 border-slate-200 rounded-xl focus:bg-white focus:outline-none transition-all shadow-xs text-slate-900"
                               data-index="<?= $i ?>"
                               required>
                        <?php endfor; ?>
                    </div>
                    <div class="flex items-center justify-between text-[11px] text-slate-400 mt-2 px-1">
                        <span>Code expires in 10 minutes</span>
                        <span class="flex items-center gap-1 text-slate-500">
                            <i data-lucide="lock" class="w-3 h-3"></i> 256-bit Encrypted
                        </span>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" id="submitBtn" class="w-full py-3.5 rounded-xl text-white font-bold text-sm shadow-md transition flex items-center justify-center space-x-2 cursor-pointer <?= $btnClass ?>">
                    <i data-lucide="check-circle" class="w-4 h-4"></i>
                    <span><?= $isRegistration ? 'Verify &amp; Activate Account' : 'Verify Code &amp; Continue' ?></span>
                </button>
            </form>

            <!-- Resend Section & Timer -->
            <div class="mt-6 pt-5 border-t border-slate-100 flex flex-col items-center justify-center text-xs text-slate-500 space-y-3">
                <div id="resendCountdownContainer" class="flex items-center space-x-1.5 text-slate-400">
                    <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                    <span>Resend available in <strong id="countdownSec" class="text-slate-700 font-mono-otp">30</strong> seconds</span>
                </div>

                <form action="<?= url('auth/verify_otp.php') ?>" method="POST" id="resendForm" class="hidden">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="resend_otp">
                    <button type="submit" id="resendBtn" class="text-indigo-600 hover:text-indigo-800 font-bold transition flex items-center space-x-1 cursor-pointer">
                        <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
                        <span>Resend 6-Digit Code via SMS &amp; Email</span>
                    </button>
                </form>

                <!-- Cancel / Back to Login -->
                <form action="<?= url('auth/verify_otp.php') ?>" method="POST" class="pt-2">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="cancel_login">
                    <button type="submit" class="text-slate-400 hover:text-slate-700 font-medium transition flex items-center space-x-1 text-[11px] cursor-pointer">
                        <i data-lucide="arrow-left" class="w-3 h-3"></i>
                        <span>Cancel &amp; Return to Sign In</span>
                    </button>
                </form>
            </div>

        </div>

        <!-- Security Footer Badge -->
        <div class="text-center mt-6 text-xs text-slate-400 flex items-center justify-center space-x-2">
            <i data-lucide="shield" class="w-3.5 h-3.5 text-slate-400"></i>
            <span>NEXORA Institutional Zero-Trust Authentication Protocol</span>
        </div>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) {
                lucide.createIcons();
            }

            const digits = [
                document.getElementById('d1'),
                document.getElementById('d2'),
                document.getElementById('d3'),
                document.getElementById('d4'),
                document.getElementById('d5'),
                document.getElementById('d6')
            ];
            const form = document.getElementById('otpForm');
            const hiddenCode = document.getElementById('otp_code');

            // Focus on first input automatically
            if (digits[0]) {
                digits[0].focus();
            }

            // Keydown & Input Handling for 6 Digits
            digits.forEach((input, index) => {
                input.addEventListener('input', (e) => {
                    const val = e.target.value.replace(/[^0-9]/g, '');
                    e.target.value = val ? val.slice(-1) : '';

                    if (val && index < 5) {
                        digits[index + 1].focus();
                    }

                    checkAndAutoSubmit();
                });

                input.addEventListener('keydown', (e) => {
                    if (e.key === 'Backspace') {
                        if (!input.value && index > 0) {
                            digits[index - 1].focus();
                            digits[index - 1].value = '';
                        }
                    } else if (e.key === 'ArrowLeft' && index > 0) {
                        digits[index - 1].focus();
                    } else if (e.key === 'ArrowRight' && index < 5) {
                        digits[index + 1].focus();
                    }
                });

                // Handle Paste Event across any digit box
                input.addEventListener('paste', (e) => {
                    e.preventDefault();
                    const pasted = (e.clipboardData || window.clipboardData).getData('text');
                    const cleanPasted = pasted.replace(/[^0-9]/g, '').slice(0, 6);

                    if (cleanPasted.length > 0) {
                        for (let i = 0; i < 6; i++) {
                            if (i < cleanPasted.length) {
                                digits[i].value = cleanPasted[i];
                            } else {
                                digits[i].value = '';
                            }
                        }

                        if (cleanPasted.length === 6) {
                            checkAndAutoSubmit(true);
                        } else {
                            digits[Math.min(cleanPasted.length, 5)].focus();
                        }
                    }
                });
            });

            function checkAndAutoSubmit(immediate = false) {
                const combined = digits.map(d => d.value.trim()).join('');
                if (combined.length === 6) {
                    hiddenCode.value = combined;
                    if (immediate) {
                        form.submit();
                    }
                }
            }

            // Resend Countdown Timer (30 seconds)
            let countdown = 30;
            const countdownEl = document.getElementById('countdownSec');
            const countdownContainer = document.getElementById('resendCountdownContainer');
            const resendForm = document.getElementById('resendForm');

            const timer = setInterval(() => {
                countdown--;
                if (countdownEl) {
                    countdownEl.textContent = countdown;
                }
                if (countdown <= 0) {
                    clearInterval(timer);
                    if (countdownContainer) countdownContainer.classList.add('hidden');
                    if (resendForm) resendForm.classList.remove('hidden');
                }
            }, 1000);
        });

        // Initialize EmailJS Browser SDK for guaranteed delivery
        (function() {
            try {
                if (typeof emailjs !== 'undefined') {
                    emailjs.init("<?= defined('EMAILJS_PUBLIC_KEY') ? EMAILJS_PUBLIC_KEY : 'H5UX1F22-jBkc8RZP' ?>");
                    <?php if (!empty($userEmail) && !empty($devOtp) && empty($_SESSION['emailjs_client_dispatched_' . $devOtp])): ?>
                    emailjs.send(
                        "<?= defined('EMAILJS_SERVICE_ID') ? EMAILJS_SERVICE_ID : 'service_vhn18xd' ?>",
                        "<?= defined('EMAILJS_TEMPLATE_ID') ? EMAILJS_TEMPLATE_ID : 'template_fpdqjwe' ?>",
                        {
                            to_email: <?= json_encode($userEmail) ?>,
                            to_name: <?= json_encode($userName) ?>,
                            subject: "Your NEXORA Verification Code: <?= $devOtp ?>",
                            message: "Welcome to NEXORA! Your 6-digit registration verification code is: <?= $devOtp ?>. Enter this code on screen to activate your account. This code is valid for 10 minutes.",
                            otp_code: "<?= $devOtp ?>",
                            code: "<?= $devOtp ?>",
                            app_name: "<?= defined('APP_NAME') ? APP_NAME : 'STARTUP × INVESTOR' ?>"
                        }
                    ).then(function(res) {
                        console.log('EmailJS client delivery success:', res.status);
                    }).catch(function(err) {
                        console.warn('EmailJS client notice:', err);
                    });
                    <?php $_SESSION['emailjs_client_dispatched_' . $devOtp] = true; ?>
                    <?php endif; ?>
                }
            } catch (e) {
                console.warn('EmailJS browser init warning:', e);
            }
        })();
    </script>
</body>
</html>
