<?php
/**
 * Authentication: Login Page
 * Clean White Theme, Small Crisp Typography, Professional Look
 */
require_once __DIR__ . '/../config.php';

if (auth_check()) {
    $u = current_user();
    if ($u && $u['role'] === 'admin' && empty($_SESSION['2fa_verified']) && is_admin_2fa_enforced((int)$u['id'])) {
        if (!is_admin_totp_setup((int)$u['id'])) {
            header('Location: ' . url('auth/setup_2fa.php'));
            exit;
        }
        header('Location: ' . url('auth/verify_2fa.php'));
        exit;
    }
    $redirect = match($u['role'] ?? '') {
        'founder' => 'founder/dashboard.php',
        'investor' => 'investor/dashboard.php',
        'admin' => 'admin/dashboard.php',
        default => 'index.php'
    };
    header('Location: ' . url($redirect));
    exit;
}

$error = '';
$flash = get_flash();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $error = 'Please enter both email and password.';
        } else {
            $db = get_db();
            if ($db) {
                $stmt = $db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password_hash'])) {
                    if ($user['status'] === 'suspended') {
                        $error = 'Your account has been suspended. Please contact portal compliance.';
                    } elseif ($user['role'] === 'admin' && is_admin_2fa_enforced((int)$user['id'])) {
                        // Admin 2FA Verification Flow
                        unset($_SESSION['user_id'], $_SESSION['user_role'], $_SESSION['user_name'], $_SESSION['2fa_verified']);
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
                    } else {
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_role'] = $user['role'];
                        $_SESSION['user_name'] = $user['name'];

                        log_audit($user['id'], 'USER_LOGIN', 'users', $user['id'], 'User logged into portal');

                        $redirect = match($user['role']) {
                            'founder' => 'founder/dashboard.php',
                            'investor' => 'investor/dashboard.php',
                            'admin' => 'admin/dashboard.php',
                            default => 'index.php'
                        };
                        header('Location: ' . url($redirect));
                        exit;
                    }
                } else {
                    $error = 'Invalid email address or password.';
                }
            } else {
                $error = 'Database connection error. Please run setup.php.';
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
    <title>Sign In • <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background-color: #FAFAFB;
            color: #0F172A;
        }
    </style>
</head>
<body class="bg-[#FAFAFB] text-slate-900 min-h-screen flex items-center justify-center p-4 selection:bg-indigo-100 selection:text-indigo-900">

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
                    <span class="block text-[8px] tracking-widest text-slate-400 uppercase font-bold">Sign In</span>
                </div>
            </a>
        </div>

        <!-- White Card -->
        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
            
            <div class="mb-5">
                <h1 class="text-lg font-bold tracking-tight text-slate-900">Welcome back</h1>
                <p class="text-[11px] text-slate-500 mt-0.5">Sign in to your Founder, Investor, or Admin portal</p>
            </div>

            <!-- Demo Quick Selector -->
            <div class="mb-5 p-3 rounded-xl bg-slate-50 border border-slate-200 text-xs">
                <div class="text-slate-500 font-semibold mb-2 flex items-center space-x-1.5 text-[10px] uppercase tracking-wider">
                    <i data-lucide="sparkles" class="w-3 h-3 text-amber-500"></i>
                    <span>Fast Demo Fill:</span>
                </div>
                <div class="grid grid-cols-3 gap-1.5 text-center">
                    <button type="button" onclick="fillCreds('founder@techpulse.io', 'password123')" class="px-2 py-1.5 rounded-lg bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 text-[11px] font-semibold transition">
                        Founder
                    </button>
                    <button type="button" onclick="fillCreds('investor@venturecapital.com', 'password123')" class="px-2 py-1.5 rounded-lg bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 text-[11px] font-semibold transition">
                        Investor
                    </button>
                    <button type="button" onclick="fillCreds('admin@portal.com', 'password123')" class="px-2 py-1.5 rounded-lg bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 text-[11px] font-semibold transition">
                        Admin
                    </button>
                </div>
            </div>

            <!-- Alerts -->
            <?php if ($flash): ?>
                <div class="mb-4 p-3 rounded-lg text-xs font-semibold border <?= $flash['type'] === 'success' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200' ?> flex items-center space-x-2">
                    <i data-lucide="check-circle" class="w-3.5 h-3.5 flex-shrink-0"></i>
                    <span><?= htmlspecialchars($flash['message']) ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="mb-4 p-3 rounded-lg text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200 flex items-center space-x-2">
                    <i data-lucide="alert-circle" class="w-3.5 h-3.5 flex-shrink-0"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <form action="<?= url('auth/login.php') ?>" method="POST" class="space-y-3.5" id="loginForm">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                <div>
                    <label class="block text-[10.5px] font-bold uppercase tracking-wider text-slate-600 mb-1">
                        Email Address
                    </label>
                    <div class="relative">
                        <i data-lucide="mail" class="w-3.5 h-3.5 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                        <input type="email" id="email" name="email" required placeholder="name@company.com"
                               class="w-full pl-9 pr-3 py-2 bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-600 focus:ring-2 focus:ring-indigo-600/10 rounded-lg text-xs text-slate-900 placeholder-slate-400 outline-none transition">
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-[10.5px] font-bold uppercase tracking-wider text-slate-600">Password</label>
                        <a href="<?= url('auth/forgot_password.php') ?>" class="text-[10px] text-indigo-600 hover:text-indigo-700 font-semibold">Forgot Password?</a>
                    </div>
                    <div class="relative">
                        <i data-lucide="lock" class="w-3.5 h-3.5 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                        <input type="password" id="password" name="password" required placeholder="••••••••"
                               class="w-full pl-9 pr-3 py-2 bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-600 focus:ring-2 focus:ring-indigo-600/10 rounded-lg text-xs text-slate-900 placeholder-slate-400 outline-none transition">
                    </div>
                </div>

                <button type="submit" class="w-full py-2.5 px-4 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-lg shadow-sm transition flex items-center justify-center space-x-1.5 mt-2">
                    <span>Sign In</span>
                    <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                </button>
            </form>

            <div class="mt-5 pt-4 border-t border-slate-100 text-center text-[11px] text-slate-500">
                New to the platform? 
                <a href="<?= url('auth/register.php') ?>" class="text-indigo-600 hover:text-indigo-700 font-bold ml-0.5">
                    Create Account
                </a>
            </div>
        </div>

        <div class="mt-4 text-center text-[10px] text-slate-400 flex items-center justify-center space-x-1">
            <i data-lucide="shield-check" class="w-3 h-3 text-emerald-600"></i>
            <span>SEBI & MCA Compliant Portal</span>
        </div>
    </div>

    <script>
        lucide.createIcons();
        gsap.from("#auth-container", { duration: 0.5, y: 15, opacity: 0, ease: "power2.out" });

        function fillCreds(email, password) {
            document.getElementById('email').value = email;
            document.getElementById('password').value = password;
        }
    </script>
</body>
</html>
