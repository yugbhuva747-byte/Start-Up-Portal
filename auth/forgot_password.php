<?php
/**
 * Authentication: Forgot Password / Password Recovery Request
 * Generates a secure token and shows instructions (in production, sends email)
 */
require_once __DIR__ . '/../config.php';

if (auth_check()) {
    header('Location: ' . url('index.php'));
    exit;
}

$error = '';
$success = false;
$resetLink = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $email = trim($_POST['email'] ?? '');
        
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            $db = get_db();
            if ($db) {
                $stmt = $db->prepare("SELECT id, name, email FROM users WHERE email = ? AND status != 'suspended' LIMIT 1");
                $stmt->execute([$email]);
                $user = $stmt->fetch();
                
                if ($user) {
                    // Generate secure token
                    $token = bin2hex(random_bytes(32));
                    $tokenHash = password_hash($token, PASSWORD_DEFAULT);
                    $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
                    
                    // Invalidate any existing tokens for this user
                    $db->prepare("UPDATE password_resets SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL")->execute([$user['id']]);
                    
                    // Store new token
                    $ins = $db->prepare("INSERT INTO password_resets (user_id, token_hash, expires_at, created_at) VALUES (?, ?, ?, NOW())");
                    $ins->execute([$user['id'], $tokenHash, $expiresAt]);
                    
                    log_audit($user['id'], 'PASSWORD_RESET_REQUESTED', 'users', $user['id'], 'Password reset token generated');
                    
                    // In production, send email. For demo, show the link directly.
                    $resetLink = url('auth/reset_password.php?token=' . $token . '&email=' . urlencode($user['email']));
                    $success = true;
                } else {
                    // Don't reveal whether email exists (security best practice)
                    // But for demo purposes, we'll still show success
                    $success = true;
                }
            } else {
                $error = 'Database connection error.';
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
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #FAFAFB; }
    </style>
</head>
<body class="bg-[#FAFAFB] text-slate-900 min-h-screen flex items-center justify-center p-4">

    <div class="max-w-sm w-full my-8" id="auth-container">
        
        <!-- Header Logo -->
        <div class="text-center mb-6">
            <a href="<?= url('index.php') ?>" class="inline-flex items-center space-x-2 group">
                <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center text-white shadow-sm shadow-indigo-600/20 group-hover:scale-105 transition">
                    <i data-lucide="zap" class="w-4 h-4"></i>
                </div>
                <div class="text-left">
                    <span class="text-sm font-black tracking-tight text-slate-900 flex items-center gap-1">
                        STARTUP <span class="text-indigo-600">×</span> INVESTOR
                    </span>
                    <span class="block text-[8px] tracking-widest text-slate-400 uppercase font-bold">Password Recovery</span>
                </div>
            </a>
        </div>

        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
            
            <?php if ($success): ?>
                <!-- Success State -->
                <div class="text-center py-4">
                    <div class="w-14 h-14 mx-auto mb-4 rounded-2xl bg-emerald-50 border border-emerald-200 flex items-center justify-center">
                        <i data-lucide="mail-check" class="w-7 h-7 text-emerald-600"></i>
                    </div>
                    <h1 class="text-lg font-bold text-slate-900 mb-1">Check your email</h1>
                    <p class="text-[11px] text-slate-500 leading-relaxed mb-4">
                        If an account exists with that email, we've sent password reset instructions. The link expires in 1 hour.
                    </p>
                    
                    <?php if (!empty($resetLink)): ?>
                        <!-- Demo Mode: Show direct link -->
                        <div class="p-3 rounded-xl bg-amber-50 border border-amber-200 text-left mb-4">
                            <div class="text-[10px] font-bold text-amber-700 uppercase tracking-wider mb-1.5 flex items-center space-x-1">
                                <i data-lucide="sparkles" class="w-3 h-3 text-amber-500"></i>
                                <span>Demo Mode — Direct Reset Link:</span>
                            </div>
                            <a href="<?= htmlspecialchars($resetLink) ?>" class="text-[11px] text-indigo-600 hover:text-indigo-700 font-semibold break-all leading-relaxed underline">
                                Click here to reset password →
                            </a>
                        </div>
                    <?php endif; ?>
                    
                    <a href="<?= url('auth/login.php') ?>" class="inline-flex items-center space-x-1.5 text-xs text-indigo-600 hover:text-indigo-700 font-semibold">
                        <i data-lucide="arrow-left" class="w-3 h-3"></i>
                        <span>Back to Sign In</span>
                    </a>
                </div>
            <?php else: ?>
                <!-- Request Form -->
                <div class="mb-5">
                    <h1 class="text-lg font-bold tracking-tight text-slate-900">Forgot your password?</h1>
                    <p class="text-[11px] text-slate-500 mt-0.5">Enter the email address linked to your account. We'll send a secure reset link.</p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="mb-4 p-3 rounded-lg text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200 flex items-center space-x-2">
                        <i data-lucide="alert-circle" class="w-3.5 h-3.5 flex-shrink-0"></i>
                        <span><?= htmlspecialchars($error) ?></span>
                    </div>
                <?php endif; ?>

                <form action="<?= url('auth/forgot_password.php') ?>" method="POST" class="space-y-3.5">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    
                    <div>
                        <label class="block text-[10.5px] font-bold uppercase tracking-wider text-slate-600 mb-1">Email Address</label>
                        <div class="relative">
                            <i data-lucide="mail" class="w-3.5 h-3.5 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                            <input type="email" name="email" required placeholder="name@company.com" autofocus
                                   class="w-full pl-9 pr-3 py-2.5 bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-600 focus:ring-2 focus:ring-indigo-600/10 rounded-lg text-xs text-slate-900 placeholder-slate-400 outline-none transition">
                        </div>
                    </div>

                    <button type="submit" class="w-full py-2.5 px-4 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-lg shadow-sm transition flex items-center justify-center space-x-1.5 mt-1">
                        <i data-lucide="send" class="w-3.5 h-3.5"></i>
                        <span>Send Reset Link</span>
                    </button>
                </form>

                <div class="mt-5 pt-4 border-t border-slate-100 text-center text-[11px] text-slate-500">
                    Remember your password? 
                    <a href="<?= url('auth/login.php') ?>" class="text-indigo-600 hover:text-indigo-700 font-bold ml-0.5">Sign In</a>
                </div>
            <?php endif; ?>
        </div>

        <div class="mt-4 text-center text-[10px] text-slate-400 flex items-center justify-center space-x-1">
            <i data-lucide="shield-check" class="w-3 h-3 text-emerald-600"></i>
            <span>Secure token-based recovery • 1hr expiry</span>
        </div>
    </div>

    <script>
        lucide.createIcons();
        gsap.from("#auth-container", { duration: 0.5, y: 15, opacity: 0, ease: "power2.out" });
    </script>
</body>
</html>
