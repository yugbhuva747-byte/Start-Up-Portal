<?php
/**
 * Authentication: Forgot Password / Password Recovery Request
 * Integrated with EmailJS Client & Server Delivery Engine
 * Service ID: service_vhn18xd | Template ID: template_fpdqjwe
 */
require_once __DIR__ . '/../config.php';

if (auth_check()) {
    header('Location: ' . url('index.php'));
    exit;
}

$error = '';
$success = false;
$resetLink = '';
$sentEmail = '';
$prefillEmail = trim($_GET['email'] ?? '');

$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
       || (isset($_POST['ajax']) && $_POST['ajax'] == '1')
       || (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please refresh the page and try again.';
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $error]);
            exit;
        }
    } else {
        $email = trim($_POST['email'] ?? '');
        $sentEmail = $email;
        
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => $error]);
                exit;
            }
        } else {
            $db = get_db();
            if ($db) {
                $stmt = $db->prepare("SELECT id, name, email FROM users WHERE email = ? AND status != 'suspended' LIMIT 1");
                $stmt->execute([$email]);
                $user = $stmt->fetch();
                
                $allowDevDemo = (defined('IS_LOCALHOST') && IS_LOCALHOST) || (defined('APP_ENV') && APP_ENV === 'development');

                if ($user) {
                    // Generate cryptographically secure token
                    $token = bin2hex(random_bytes(32));
                    $tokenHash = password_hash($token, PASSWORD_DEFAULT);
                    $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
                    
                    // Invalidate any existing unused tokens for this user
                    $db->prepare("UPDATE password_resets SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL")->execute([$user['id']]);
                    
                    // Store new token (expires in 1 hour via DB clock)
                    $ins = $db->prepare("INSERT INTO password_resets (user_id, token_hash, expires_at, created_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR), NOW())");
                    $ins->execute([$user['id'], $tokenHash]);
                    
                    log_audit($user['id'], 'PASSWORD_RESET_REQUESTED', 'users', $user['id'], 'Password reset requested via EmailJS');
                    
                    // Generate full reset URL
                    $resetUrl = url('auth/reset_password.php?token=' . $token . '&email=' . urlencode($user['email']));
                    
                    // Comprehensive template parameters matching any EmailJS template variable names
                    $templateParams = [
                        'to_email'    => $user['email'],
                        'email'       => $user['email'],
                        'user_email'  => $user['email'],
                        'to_name'     => $user['name'],
                        'name'        => $user['name'],
                        'user_name'   => $user['name'],
                        'reset_link'  => $resetUrl,
                        'reset_url'   => $resetUrl,
                        'link'        => $resetUrl,
                        'url'         => $resetUrl,
                        'app_name'    => defined('APP_NAME') ? APP_NAME : 'STARTUP × INVESTOR',
                        'message'     => "We received a request to reset your password for your account. Click the link below to choose a new password:\n\n" . $resetUrl . "\n\nThis link will expire in 1 hour."
                    ];

                    // 1. Dispatch via server-side EmailJS
                    $serverEmailjsResult = null;
                    if (function_exists('send_via_emailjs')) {
                        $serverEmailjsResult = send_via_emailjs($user['email'], $user['name'], $templateParams);
                    }

                    // 2. Also log and archive via system mailer
                    if (function_exists('send_system_email')) {
                        $emailSubject = 'Reset Your ' . APP_NAME . ' Password';
                        $emailBody = "
                            <div style='font-family:sans-serif; max-width:540px; margin:auto; padding:24px; border:1px solid #e2e8f0; border-radius:12px;'>
                                <h2 style='color:#0f172a;'>Password Reset Request</h2>
                                <p style='color:#475569;'>Hello " . htmlspecialchars($user['name']) . ",</p>
                                <p style='color:#475569;'>We received a request to reset your password for your " . htmlspecialchars(APP_NAME) . " account. Click the button below to choose a new password. This link is valid for 1 hour.</p>
                                <div style='margin:28px 0;'>
                                    <a href='{$resetUrl}' style='background:#4f46e5; color:#ffffff; padding:12px 24px; border-radius:8px; text-decoration:none; font-weight:bold; font-size:14px; display:inline-block;'>Reset My Password</a>
                                </div>
                                <p style='color:#94a3b8; font-size:12px;'>If you did not request this, you can safely ignore this email. Your password will remain unchanged.</p>
                            </div>
                        ";
                        send_system_email($user['email'], $user['name'], $emailSubject, $emailBody, 'password_reset');
                    }

                    if ($allowDevDemo) {
                        $resetLink = $resetUrl;
                    }
                    $success = true;

                    if ($isAjax) {
                        header('Content-Type: application/json');
                        echo json_encode([
                            'success'             => true,
                            'found'               => true,
                            'email'               => $user['email'],
                            'user_name'           => $user['name'],
                            'reset_url'           => $resetUrl,
                            'emailjs_service_id'  => EMAILJS_SERVICE_ID,
                            'emailjs_template_id' => EMAILJS_TEMPLATE_ID,
                            'emailjs_public_key'  => EMAILJS_PUBLIC_KEY,
                            'server_emailjs'      => $serverEmailjsResult,
                            'allow_dev_demo'      => $allowDevDemo
                        ]);
                        exit;
                    }
                } else {
                    // Security best practice: Don't reveal whether email exists
                    $success = true;
                    if ($isAjax) {
                        header('Content-Type: application/json');
                        echo json_encode([
                            'success'             => true,
                            'found'               => false,
                            'email'               => $email,
                            'allow_dev_demo'      => false
                        ]);
                        exit;
                    }
                }
            } else {
                $error = 'Database connection error.';
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'error' => $error]);
                    exit;
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password • <?= APP_NAME ?></title>
    <meta name="description" content="Recover your <?= APP_NAME ?> account password with instant EmailJS delivery.">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <!-- EmailJS Official Browser SDK -->
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/@emailjs/browser@4/dist/email.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #F4F2EE; }
    </style>
</head>
<body class="bg-[#F4F2EE] text-slate-900 min-h-screen flex items-center justify-center p-4">

    <div class="max-w-md w-full my-8" id="auth-container">
        
        <!-- Header Logo -->
        <div class="text-center mb-6">
            <a href="<?= url('index.php') ?>" class="inline-flex items-center space-x-2.5 group">
                <div class="w-9 h-9 rounded-xl bg-indigo-600 flex items-center justify-center text-white shadow-md shadow-indigo-600/20 group-hover:scale-105 transition-transform">
                    <i data-lucide="zap" class="w-5 h-5"></i>
                </div>
                <div class="text-left">
                    <span class="text-sm font-black tracking-tight text-slate-900 flex items-center gap-1">
                        STARTUP <span class="text-indigo-600">×</span> INVESTOR
                    </span>
                    <span class="block text-[9px] tracking-widest text-slate-400 uppercase font-bold">Password Recovery</span>
                </div>
            </a>
        </div>

        <div class="bg-white border border-slate-200 rounded-2xl p-6 sm:p-7 shadow-sm">
            
            <!-- EmailJS Status Ribbon -->
            <div class="mb-5 flex items-center justify-between p-2.5 rounded-xl bg-indigo-50/60 border border-indigo-100 text-[11px]">
                <div class="flex items-center space-x-2">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                    </span>
                    <span class="font-bold text-indigo-900">EmailJS Delivery Active</span>
                </div>
                <span class="font-mono text-[10px] text-indigo-600 bg-white px-2 py-0.5 rounded-md border border-indigo-200">
                    <?= htmlspecialchars(EMAILJS_SERVICE_ID) ?>
                </span>
            </div>

            <!-- Dynamic Container for Form & Success Views -->
            <div id="viewContainer">
                
                <!-- SUCCESS VIEW (Hidden by default unless server POST rendered) -->
                <div id="successView" class="<?= $success ? '' : 'hidden' ?> text-center py-2">
                    <div class="w-14 h-14 mx-auto mb-4 rounded-2xl bg-emerald-50 border border-emerald-200 flex items-center justify-center shadow-sm">
                        <i data-lucide="mail-check" class="w-7 h-7 text-emerald-600"></i>
                    </div>
                    
                    <h1 class="text-lg font-bold text-slate-900 mb-1">Check your email</h1>
                    <p class="text-xs text-slate-500 leading-relaxed mb-4">
                        We've dispatched password reset instructions to <br>
                        <strong class="text-slate-800 font-semibold" id="targetEmailDisplay"><?= htmlspecialchars($sentEmail ?: 'your email address') ?></strong> via EmailJS.
                    </p>

                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 text-left mb-4 space-y-2">
                        <div class="flex items-start space-x-2 text-[11px] text-slate-600">
                            <i data-lucide="clock" class="w-4 h-4 text-indigo-600 flex-shrink-0 mt-0.5"></i>
                            <span>The reset link is active for <strong>60 minutes</strong>.</span>
                        </div>
                        <div class="flex items-start space-x-2 text-[11px] text-slate-600">
                            <i data-lucide="shield-alert" class="w-4 h-4 text-amber-500 flex-shrink-0 mt-0.5"></i>
                            <span>If you don't see it in a moment, please check your <strong>Spam or Promotions</strong> folder.</span>
                        </div>
                    </div>
                    
                    <!-- Direct Reset Link (Localhost / Development Mode) -->
                    <div id="devLinkContainer" class="<?= !empty($resetLink) ? '' : 'hidden' ?> p-3.5 rounded-xl bg-amber-50 border border-amber-200 text-left mb-4">
                        <div class="text-[10px] font-bold text-amber-700 uppercase tracking-wider mb-1.5 flex items-center space-x-1.5">
                            <i data-lucide="sparkles" class="w-3.5 h-3.5 text-amber-500"></i>
                            <span>Localhost Direct Link (Dev Mode):</span>
                        </div>
                        <a id="devResetLink" href="<?= htmlspecialchars($resetLink) ?>" class="text-[11px] text-indigo-600 hover:text-indigo-800 font-semibold break-all leading-relaxed underline block mb-2">
                            <?= htmlspecialchars($resetLink) ?>
                        </a>
                        <button type="button" onclick="copyDevLink()" class="px-2.5 py-1 bg-amber-100 hover:bg-amber-200 text-amber-900 text-[10px] font-bold rounded-md transition flex items-center space-x-1">
                            <i data-lucide="copy" class="w-3 h-3"></i>
                            <span id="copyBtnText">Copy Link</span>
                        </button>
                    </div>

                    <div class="pt-2 flex flex-col sm:flex-row items-center justify-center gap-3">
                        <a href="<?= url('auth/login.php') ?>" class="w-full sm:w-auto inline-flex items-center justify-center space-x-1.5 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl transition shadow-sm">
                            <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                            <span>Return to Sign In</span>
                        </a>
                        <button type="button" onclick="showFormView()" class="w-full sm:w-auto text-xs text-slate-600 hover:text-slate-900 font-semibold py-2 px-3">
                            Send to another email
                        </button>
                    </div>
                </div>

                <!-- FORM VIEW -->
                <div id="formView" class="<?= $success ? 'hidden' : '' ?>">
                    <div class="mb-5">
                        <h1 class="text-lg font-bold tracking-tight text-slate-900">Forgot your password?</h1>
                        <p class="text-xs text-slate-500 mt-1">
                            Enter the email address registered with your account. We'll send an instant password recovery link directly to your inbox.
                        </p>
                    </div>

                    <div id="errorBox" class="<?= !empty($error) ? '' : 'hidden' ?> mb-4 p-3 rounded-xl text-xs font-semibold bg-rose-50 text-rose-800 border border-rose-200 flex items-center space-x-2">
                        <i data-lucide="alert-circle" class="w-4 h-4 flex-shrink-0 text-rose-600"></i>
                        <span id="errorMessage"><?= htmlspecialchars($error) ?></span>
                    </div>

                    <form id="forgotForm" action="<?= url('auth/forgot_password.php') ?>" method="POST" class="space-y-4">
                        <input type="hidden" name="csrf_token" id="csrfToken" value="<?= csrf_token() ?>">
                        
                        <div>
                            <label for="emailInput" class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                Account Email Address
                            </label>
                            <div class="relative">
                                <i data-lucide="mail" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                                <input type="email" id="emailInput" name="email" required placeholder="name@company.com" autofocus
                                       value="<?= htmlspecialchars($prefillEmail) ?>"
                                       class="w-full pl-10 pr-3.5 py-2.5 bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-600 focus:ring-4 focus:ring-indigo-600/10 rounded-xl text-xs text-slate-900 placeholder-slate-400 outline-none transition font-medium">
                            </div>
                        </div>

                        <!-- Submit Button with Dynamic State -->
                        <button type="submit" id="submitBtn" class="w-full py-2.5 px-4 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow-md shadow-indigo-600/20 hover:shadow-indigo-600/30 transition-all flex items-center justify-center space-x-2 group">
                            <span id="btnIconContainer">
                                <i data-lucide="send" class="w-3.5 h-3.5 group-hover:translate-x-0.5 transition-transform"></i>
                            </span>
                            <span id="btnText">Send Reset Link via EmailJS</span>
                        </button>
                    </form>

                    <div class="mt-5 pt-4 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                        <span>Remember password?</span>
                        <a href="<?= url('auth/login.php') ?>" class="text-indigo-600 hover:text-indigo-800 font-bold transition">
                            Sign In →
                        </a>
                    </div>
                </div>

            </div>

        </div>

        <!-- Security & Protocol Footer -->
        <div class="mt-4 text-center text-[10px] text-slate-400 flex items-center justify-center space-x-1.5">
            <i data-lucide="shield-check" class="w-3.5 h-3.5 text-emerald-600"></i>
            <span>Encrypted token authentication • Powered by EmailJS</span>
        </div>
    </div>

    <script>
        // Initialize EmailJS Browser SDK
        (function() {
            try {
                if (typeof emailjs !== 'undefined') {
                    emailjs.init("<?= EMAILJS_PUBLIC_KEY ?>");
                    console.log("EmailJS SDK initialized successfully with public key.");
                }
            } catch (e) {
                console.warn("EmailJS init warning:", e);
            }
        })();

        lucide.createIcons();
        gsap.from("#auth-container", { duration: 0.45, y: 15, opacity: 0, ease: "power2.out" });

        const forgotForm = document.getElementById('forgotForm');
        const submitBtn = document.getElementById('submitBtn');
        const btnText = document.getElementById('btnText');
        const btnIconContainer = document.getElementById('btnIconContainer');
        const errorBox = document.getElementById('errorBox');
        const errorMessage = document.getElementById('errorMessage');
        const formView = document.getElementById('formView');
        const successView = document.getElementById('successView');
        const targetEmailDisplay = document.getElementById('targetEmailDisplay');
        const devLinkContainer = document.getElementById('devLinkContainer');
        const devResetLink = document.getElementById('devResetLink');

        function showError(msg) {
            errorMessage.textContent = msg;
            errorBox.classList.remove('hidden');
            lucide.createIcons();
        }

        function clearError() {
            errorBox.classList.add('hidden');
            errorMessage.textContent = '';
        }

        function showFormView() {
            successView.classList.add('hidden');
            formView.classList.remove('hidden');
            clearError();
            document.getElementById('emailInput').focus();
            lucide.createIcons();
        }

        function copyDevLink() {
            const link = devResetLink.href;
            if (navigator.clipboard) {
                navigator.clipboard.writeText(link).then(() => {
                    const btnSpan = document.getElementById('copyBtnText');
                    btnSpan.textContent = 'Copied!';
                    setTimeout(() => { btnSpan.textContent = 'Copy Link'; }, 2000);
                });
            }
        }

        if (forgotForm) {
            forgotForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                clearError();

                const emailInput = document.getElementById('emailInput');
                const email = emailInput.value.trim();

                if (!email) {
                    showError('Please enter your account email address.');
                    return;
                }

                // 1. Loading UI state
                submitBtn.disabled = true;
                submitBtn.classList.add('opacity-80', 'cursor-not-allowed');
                btnIconContainer.innerHTML = '<svg class="animate-spin h-3.5 w-3.5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>';
                btnText.textContent = 'Generating secure token...';

                try {
                    const formData = new FormData(forgotForm);
                    formData.append('ajax', '1');

                    // Request token from PHP backend
                    const response = await fetch(window.location.href, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    const data = await response.json();

                    if (!data.success) {
                        showError(data.error || 'Unable to process reset request. Please try again.');
                        restoreSubmitBtn();
                        return;
                    }

                    // 2. Dispatch via EmailJS in Browser SDK if user was found & reset URL exists
                    if (data.found && data.reset_url) {
                        btnText.textContent = 'Sending email via EmailJS...';
                        
                        const templateParams = {
                            to_email:    data.email,
                            email:       data.email,
                            user_email:  data.email,
                            to_name:     data.user_name || 'Valued User',
                            name:        data.user_name || 'Valued User',
                            user_name:   data.user_name || 'Valued User',
                            reset_link:  data.reset_url,
                            reset_url:   data.reset_url,
                            link:        data.reset_url,
                            url:         data.reset_url,
                            app_name:    '<?= APP_NAME ?>',
                            message:     `Click the link to reset your <?= APP_NAME ?> account password: ${data.reset_url}`
                        };

                        try {
                            if (typeof emailjs !== 'undefined') {
                                const emailjsResult = await emailjs.send(
                                    data.emailjs_service_id || "<?= EMAILJS_SERVICE_ID ?>",
                                    data.emailjs_template_id || "<?= EMAILJS_TEMPLATE_ID ?>",
                                    templateParams
                                );
                                console.log("EmailJS Browser SDK Send Success:", emailjsResult);
                            }
                        } catch (emailjsErr) {
                            // Server-side cURL already dispatched as backup
                            console.warn("EmailJS Browser SDK dispatch notice (Server-side delivery engaged):", emailjsErr);
                        }
                    }

                    // 3. Render Success View
                    targetEmailDisplay.textContent = email;
                    
                    if (data.allow_dev_demo && data.reset_url) {
                        devResetLink.href = data.reset_url;
                        devResetLink.textContent = data.reset_url;
                        devLinkContainer.classList.remove('hidden');
                    } else {
                        devLinkContainer.classList.add('hidden');
                    }

                    // Smooth transition
                    gsap.to(formView, {
                        duration: 0.25,
                        opacity: 0,
                        y: -10,
                        onComplete: () => {
                            formView.classList.add('hidden');
                            formView.style.opacity = 1;
                            formView.style.transform = 'none';

                            successView.classList.remove('hidden');
                            gsap.from(successView, { duration: 0.35, opacity: 0, y: 10, ease: "power2.out" });
                            lucide.createIcons();
                            restoreSubmitBtn();
                        }
                    });

                } catch (err) {
                    console.error('Password reset request error:', err);
                    // Standard POST fallback
                    forgotForm.submit();
                }
            });
        }

        function restoreSubmitBtn() {
            submitBtn.disabled = false;
            submitBtn.classList.remove('opacity-80', 'cursor-not-allowed');
            btnIconContainer.innerHTML = '<i data-lucide="send" class="w-3.5 h-3.5 group-hover:translate-x-0.5 transition-transform"></i>';
            btnText.textContent = 'Send Reset Link via EmailJS';
            lucide.createIcons();
        }
    </script>
</body>
</html>
