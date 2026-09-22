<?php
/**
 * Authentication: Reset Password (Token-Based)
 * Validates token from URL, allows user to set new password
 */
require_once __DIR__ . '/../config.php';

if (auth_check()) {
    header('Location: ' . url('index.php'));
    exit;
}

$error = '';
$success = false;
$validToken = false;
$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$email = trim($_GET['email'] ?? $_POST['email'] ?? '');

$db = get_db();

// Validate token on GET
if (!empty($token) && !empty($email) && $db) {
    $stmt = $db->prepare("SELECT u.id, u.name, u.email, pr.token_hash, pr.expires_at, pr.used_at, pr.id as reset_id
                          FROM password_resets pr 
                          JOIN users u ON pr.user_id = u.id 
                          WHERE u.email = ? AND pr.used_at IS NULL AND pr.expires_at > NOW()
                          ORDER BY pr.created_at DESC LIMIT 1");
    $stmt->execute([$email]);
    $resetRecord = $stmt->fetch();
    
    if ($resetRecord && password_verify($token, $resetRecord['token_hash'])) {
        $validToken = true;
    }
}

// Handle POST (new password submission)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $newPassword = $_POST['password'] ?? '';
        $confirmPassword = $_POST['password_confirm'] ?? '';
        
        if (strlen($newPassword) < 6) {
            $error = 'Password must be at least 6 characters long.';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'Passwords do not match.';
        } else {
            try {
                $db->beginTransaction();
                
                // Update password
                $hash = password_hash($newPassword, PASSWORD_DEFAULT);
                $db->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?")->execute([$hash, $resetRecord['id']]);
                
                // Mark token as used
                $db->prepare("UPDATE password_resets SET used_at = NOW() WHERE id = ?")->execute([$resetRecord['reset_id']]);
                
                // Audit log
                log_audit($resetRecord['id'], 'PASSWORD_RESET_COMPLETED', 'users', $resetRecord['id'], 'Password successfully reset via recovery token');
                
                // Send notification
                send_notification($resetRecord['id'], 'Password Changed', 'Your password was successfully reset. If you did not make this change, please contact support immediately.', 'warning');
                
                $db->commit();
                $success = true;
                
            } catch (Exception $e) {
                $db->rollBack();
                $error = 'An error occurred. Please try again.';
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
    <title>Set New Password • <?= APP_NAME ?></title>
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
                    <span class="block text-[8px] tracking-widest text-slate-400 uppercase font-bold">New Password</span>
                </div>
            </a>
        </div>

        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
            
            <?php if ($success): ?>
                <!-- Success -->
                <div class="text-center py-4">
                    <div class="w-14 h-14 mx-auto mb-4 rounded-2xl bg-emerald-50 border border-emerald-200 flex items-center justify-center">
                        <i data-lucide="check-circle" class="w-7 h-7 text-emerald-600"></i>
                    </div>
                    <h1 class="text-lg font-bold text-slate-900 mb-1">Password Updated!</h1>
                    <p class="text-[11px] text-slate-500 leading-relaxed mb-4">Your password has been successfully changed. You can now sign in with your new credentials.</p>
                    <a href="<?= url('auth/login.php') ?>" class="inline-flex items-center space-x-1.5 px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold shadow-sm transition">
                        <i data-lucide="log-in" class="w-3.5 h-3.5"></i>
                        <span>Sign In Now</span>
                    </a>
                </div>
                
            <?php elseif (!$validToken): ?>
                <!-- Invalid / Expired Token -->
                <div class="text-center py-4">
                    <div class="w-14 h-14 mx-auto mb-4 rounded-2xl bg-rose-50 border border-rose-200 flex items-center justify-center">
                        <i data-lucide="alert-triangle" class="w-7 h-7 text-rose-500"></i>
                    </div>
                    <h1 class="text-lg font-bold text-slate-900 mb-1">Invalid or Expired Link</h1>
                    <p class="text-[11px] text-slate-500 leading-relaxed mb-4">This password reset link is invalid, has already been used, or has expired. Please request a new one.</p>
                    <a href="<?= url('auth/forgot_password.php') ?>" class="inline-flex items-center space-x-1.5 px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold shadow-sm transition">
                        <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
                        <span>Request New Link</span>
                    </a>
                </div>
                
            <?php else: ?>
                <!-- Reset Form -->
                <div class="mb-5">
                    <h1 class="text-lg font-bold tracking-tight text-slate-900">Set New Password</h1>
                    <p class="text-[11px] text-slate-500 mt-0.5">
                        Choose a strong new password for <strong class="text-slate-800"><?= htmlspecialchars($resetRecord['email']) ?></strong>
                    </p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="mb-4 p-3 rounded-lg text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200 flex items-center space-x-2">
                        <i data-lucide="alert-circle" class="w-3.5 h-3.5 flex-shrink-0"></i>
                        <span><?= htmlspecialchars($error) ?></span>
                    </div>
                <?php endif; ?>

                <form action="<?= url('auth/reset_password.php') ?>" method="POST" class="space-y-3.5">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                    <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
                    
                    <div>
                        <label class="block text-[10.5px] font-bold uppercase tracking-wider text-slate-600 mb-1">New Password</label>
                        <div class="relative">
                            <i data-lucide="lock" class="w-3.5 h-3.5 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                            <input type="password" name="password" required minlength="6" placeholder="Minimum 6 characters"
                                   class="w-full pl-9 pr-3 py-2.5 bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-600 focus:ring-2 focus:ring-indigo-600/10 rounded-lg text-xs text-slate-900 placeholder-slate-400 outline-none transition" id="pwField">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-[10.5px] font-bold uppercase tracking-wider text-slate-600 mb-1">Confirm Password</label>
                        <div class="relative">
                            <i data-lucide="lock" class="w-3.5 h-3.5 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                            <input type="password" name="password_confirm" required minlength="6" placeholder="Re-enter your password"
                                   class="w-full pl-9 pr-3 py-2.5 bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-600 focus:ring-2 focus:ring-indigo-600/10 rounded-lg text-xs text-slate-900 placeholder-slate-400 outline-none transition">
                        </div>
                    </div>

                    <!-- Password Strength Indicator -->
                    <div class="flex space-x-1" id="strengthBars">
                        <div class="h-1 flex-1 rounded-full bg-slate-200 transition-all" id="str1"></div>
                        <div class="h-1 flex-1 rounded-full bg-slate-200 transition-all" id="str2"></div>
                        <div class="h-1 flex-1 rounded-full bg-slate-200 transition-all" id="str3"></div>
                        <div class="h-1 flex-1 rounded-full bg-slate-200 transition-all" id="str4"></div>
                    </div>
                    <div class="text-[10px] text-slate-400" id="strengthText">Enter a password</div>

                    <button type="submit" class="w-full py-2.5 px-4 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-lg shadow-sm transition flex items-center justify-center space-x-1.5 mt-1">
                        <i data-lucide="check" class="w-3.5 h-3.5"></i>
                        <span>Update Password</span>
                    </button>
                </form>
            <?php endif; ?>
        </div>

        <div class="mt-4 text-center text-[10px] text-slate-400 flex items-center justify-center space-x-1">
            <i data-lucide="shield-check" class="w-3 h-3 text-emerald-600"></i>
            <span>Encrypted & Secure Password Recovery</span>
        </div>
    </div>

    <script>
        lucide.createIcons();
        gsap.from("#auth-container", { duration: 0.5, y: 15, opacity: 0, ease: "power2.out" });
        
        // Password strength indicator
        const pwField = document.getElementById('pwField');
        if (pwField) {
            pwField.addEventListener('input', function() {
                const pw = this.value;
                let score = 0;
                if (pw.length >= 6) score++;
                if (pw.length >= 10) score++;
                if (/[A-Z]/.test(pw) && /[a-z]/.test(pw)) score++;
                if (/[0-9]/.test(pw) || /[^A-Za-z0-9]/.test(pw)) score++;
                
                const colors = ['bg-slate-200', 'bg-rose-400', 'bg-amber-400', 'bg-emerald-400', 'bg-emerald-500'];
                const texts = ['Enter a password', 'Weak', 'Fair', 'Good', 'Strong'];
                
                for (let i = 1; i <= 4; i++) {
                    const bar = document.getElementById('str' + i);
                    bar.className = 'h-1 flex-1 rounded-full transition-all ' + (i <= score ? colors[score] : 'bg-slate-200');
                }
                document.getElementById('strengthText').textContent = texts[score];
            });
        }
    </script>
</body>
</html>
