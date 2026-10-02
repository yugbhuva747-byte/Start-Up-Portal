<?php
/**
 * Authentication: Login Page
 * Split-Screen Modern Redesign
 * Left Side: Visual Showcase, Venture Graphics & Platform Highlights
 * Right Side: High-Precision Authentication Form with Fast Demo Fill & Password Visibility Toggle
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
        $remember = !empty($_POST['remember']);

        if (empty($email) || empty($password)) {
            $error = 'Please enter both email and password.';
        } else {
            $db = get_db();
            if ($db) {
                $clientIp = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

                // Check Brute-Force Rate Limiting
                if (function_exists('is_login_rate_limited') && is_login_rate_limited($clientIp, $email)) {
                    $error = 'Security Alert: Too many failed login attempts. Please wait 15 minutes before trying again.';
                    log_security_event(null, 'LOGIN_LOCKED_OUT', 'high', "Temporary lockout triggered for IP: {$clientIp}, Email: {$email}");
                } else {
                    $stmt = $db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
                    $stmt->execute([$email]);
                    $user = $stmt->fetch();

                    if ($user && password_verify($password, $user['password_hash'])) {
                        // Clear failure count on success
                        if (function_exists('clear_failed_logins')) {
                            clear_failed_logins($clientIp, $email);
                        }

                        if ($user['status'] === 'suspended') {
                            $error = 'Your account has been suspended. Please contact portal compliance.';
                            log_security_event($user['id'], 'LOGIN_SUSPENDED_USER', 'medium', "Suspended user tried to log in: {$email}");
                        } elseif ($user['role'] === 'admin' && is_admin_2fa_enforced((int)$user['id'])) {
                            // Admin 2FA Verification Flow
                            unset($_SESSION['user_id'], $_SESSION['user_role'], $_SESSION['user_name'], $_SESSION['2fa_verified']);
                            $_SESSION['2fa_pending_user_id'] = (int)$user['id'];
                            $_SESSION['2fa_pending_email'] = $user['email'];
                            $_SESSION['2fa_pending_role'] = $user['role'];
                            $_SESSION['2fa_pending_name'] = $user['name'];
                            $_SESSION['2fa_pending_remember'] = $remember;

                            if (!is_admin_totp_setup((int)$user['id'])) {
                                header('Location: ' . url('auth/setup_2fa.php'));
                                exit;
                            }

                            header('Location: ' . url('auth/verify_2fa.php'));
                            exit;
                        } else {
                            session_regenerate_id(true);
                            $_SESSION['user_id'] = $user['id'];
                            $_SESSION['user_role'] = $user['role'];
                            $_SESSION['user_name'] = $user['name'];

                            // Persistent Cookie Session ("Remember Me")
                            if ($remember) {
                                set_remember_me_cookie((int)$user['id'], 30);
                            } else {
                                clear_remember_me_cookie();
                            }

                            log_audit($user['id'], 'USER_LOGIN', 'users', $user['id'], 'User logged into portal' . ($remember ? ' (Remember Me activated)' : ''));

                            // Register active login session
                            $sessionToken = bin2hex(random_bytes(32));
                            $_SESSION['session_token'] = $sessionToken;
                            $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 255);
                            $db->prepare("INSERT INTO login_sessions (user_id, session_token, ip_address, user_agent, created_at, last_active_at) VALUES (?, ?, ?, ?, NOW(), NOW())")->execute([$user['id'], $sessionToken, $clientIp, $ua]);

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
                        if (function_exists('record_failed_login')) {
                            record_failed_login($clientIp, $email);
                        }
                        $error = 'Invalid email address or password.';
                    }
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
    <meta name="description" content="Access your Startup × Investor portal dashboard for founders, angel syndicates, and venture compliance.">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
        }
        .mesh-bg {
            background-color: #080C17;
            background-image: 
                radial-gradient(at 0% 0%, rgba(99, 102, 241, 0.22) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(139, 92, 246, 0.18) 0px, transparent 50%),
                radial-gradient(at 50% 50%, rgba(30, 27, 75, 0.35) 0px, transparent 60%);
        }
        .dot-pattern {
            background-image: radial-gradient(rgba(255, 255, 255, 0.12) 1px, transparent 1px);
            background-size: 24px 24px;
        }
        .glass-panel {
            background: rgba(255, 255, 255, 0.04);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        .glass-panel-hover:hover {
            background: rgba(255, 255, 255, 0.07);
            border-color: rgba(255, 255, 255, 0.16);
        }
        .input-highlight {
            animation: inputPulse 0.5s ease-in-out;
        }
        @keyframes inputPulse {
            0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(99, 102, 241, 0.5); }
            50% { transform: scale(1.008); box-shadow: 0 0 0 5px rgba(99, 102, 241, 0.15); }
            100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(99, 102, 241, 0); }
        }
        /* Custom sleek scrollbar */
        .custom-scroll::-webkit-scrollbar {
            width: 5px;
        }
        .custom-scroll::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-scroll::-webkit-scrollbar-thumb {
            background: rgba(148, 163, 184, 0.2);
            border-radius: 9999px;
        }
        .custom-scroll::-webkit-scrollbar-thumb:hover {
            background: rgba(148, 163, 184, 0.4);
        }
    </style>
</head>
<body class="bg-slate-900 text-slate-900 min-h-screen selection:bg-indigo-500 selection:text-white">

    <div class="min-h-screen lg:h-screen lg:overflow-hidden flex flex-col lg:flex-row">
        
        <!-- ========================================================
             LEFT SIDE: VISUAL SHOWCASE, BRANDING & INFORMATION
             ======================================================== -->
        <div class="lg:w-1/2 xl:w-7/12 mesh-bg text-white p-6 sm:p-8 xl:p-10 2xl:p-12 flex flex-col justify-between relative overflow-y-auto custom-scroll border-b lg:border-b-0 lg:border-r border-slate-800" id="left-column">
            
            <!-- Subtle background ambient dot grid -->
            <div class="absolute inset-0 dot-pattern opacity-40 pointer-events-none"></div>
            
            <!-- Ambient Glow Orbs -->
            <div class="absolute -top-20 -left-20 w-80 h-80 bg-indigo-600/20 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute bottom-8 -right-8 w-72 h-72 bg-violet-600/20 rounded-full blur-3xl pointer-events-none"></div>

            <div class="relative z-10">
                <!-- Top Brand Header & Return Link -->
                <div class="flex items-center justify-between mb-5 xl:mb-6">
                    <a href="<?= url('index.php') ?>" class="inline-flex items-center space-x-3 group">
                        <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-600 via-indigo-500 to-violet-500 flex items-center justify-center text-white shadow-lg shadow-indigo-500/30 group-hover:scale-105 transition-transform duration-200">
                            <i data-lucide="zap" class="w-4 h-4 text-white"></i>
                        </div>
                        <div>
                            <span class="text-sm font-extrabold tracking-tight text-white flex items-center gap-1.5">
                                STARTUP <span class="text-indigo-400 font-black">×</span> INVESTOR
                            </span>
                            <span class="block text-[8.5px] tracking-widest text-slate-400 uppercase font-bold">Venture & Capital Ecosystem</span>
                        </div>
                    </a>

                    <a href="<?= url('index.php') ?>" class="hidden sm:inline-flex items-center text-xs font-semibold text-slate-300 hover:text-white transition group space-x-1.5 px-3 py-1.5 rounded-lg glass-panel hover:bg-white/10">
                        <i data-lucide="arrow-left" class="w-3.5 h-3.5 group-hover:-translate-x-0.5 transition-transform"></i>
                        <span>Explore Platform</span>
                    </a>
                </div>

                <!-- Category Pill Tag -->
                <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full glass-panel text-[11px] font-semibold text-indigo-300 mb-3 border border-indigo-500/30">
                    <span class="w-1.5 h-1.5 rounded-full bg-indigo-400 animate-pulse"></span>
                    <span>INDIA'S PREMIER PRIVATE VENTURE PIPELINE</span>
                </div>

                <!-- Big Headline -->
                <h1 class="text-2xl sm:text-3xl xl:text-4xl font-black text-white tracking-tight leading-snug mb-2">
                    Where High-Growth Startups Meet Institutional Capital.
                </h1>

                <!-- Subtitle -->
                <p class="text-slate-300 text-xs sm:text-sm leading-relaxed mb-4 max-w-xl">
                    Direct founder-investor deal rooms, automated MCA compliance, verified cap tables, and institutional syndicates in one unified portal.
                </p>

                <!-- ==============================================
                     IMAGE SHOWCASE & ECOSYSTEM CARD
                     ============================================== -->
                <div class="relative rounded-2xl overflow-hidden border border-slate-700/60 shadow-xl shadow-indigo-950/60 group mb-4">
                    <!-- High-tech Venture Graphic -->
                    <img src="<?= url('assets/images/login_hero.jpg') ?>" 
                         alt="Startup and Investor Venture Capital Ecosystem" 
                         class="w-full h-44 sm:h-52 xl:h-56 object-cover object-center group-hover:scale-105 transition-transform duration-700">
                    
                    <!-- Gradient darkening overlay for crisp contrast -->
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-950/95 via-slate-950/35 to-transparent"></div>
                    
                    <!-- Floating Glass Badges on Top of Image -->
                    <div class="absolute top-3 left-3 flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-900/85 backdrop-blur-md border border-white/15 text-[10.5px] font-semibold text-white shadow-lg">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        <span>Live Due Diligence Pipeline</span>
                    </div>

                    <div class="absolute top-3 right-3 flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-900/85 backdrop-blur-md border border-white/15 text-[10.5px] font-semibold text-indigo-300 shadow-lg">
                        <i data-lucide="shield-check" class="w-3.5 h-3.5 text-emerald-400"></i>
                        <span>SEBI & MCA Verified</span>
                    </div>

                    <!-- Bottom Overlay Details on Image -->
                    <div class="absolute bottom-2.5 left-3 right-3 flex items-center justify-between text-xs text-slate-300">
                        <div class="flex items-center gap-2">
                            <div class="flex -space-x-1.5">
                                <span class="w-6 h-6 rounded-full bg-gradient-to-tr from-indigo-600 to-indigo-400 flex items-center justify-center text-[9px] font-bold text-white border-2 border-slate-900">AS</span>
                                <span class="w-6 h-6 rounded-full bg-gradient-to-tr from-emerald-600 to-emerald-400 flex items-center justify-center text-[9px] font-bold text-white border-2 border-slate-900">VS</span>
                                <span class="w-6 h-6 rounded-full bg-gradient-to-tr from-violet-600 to-pink-500 flex items-center justify-center text-[9px] font-bold text-white border-2 border-slate-900">PP</span>
                            </div>
                            <span class="text-[11px] font-medium text-slate-200">500+ Active Syndicate Leads</span>
                        </div>
                        <div>
                            <span class="px-2 py-0.5 rounded-md bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 text-[10.5px] font-bold flex items-center gap-1">
                                <i data-lucide="trending-up" class="w-3 h-3"></i>
                                ₹185 Cr+ Raised
                            </span>
                        </div>
                    </div>
                </div>

                <!-- ==============================================
                     INFORMATION HIGHLIGHTS (3 PILLARS)
                     ============================================== -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 mb-4">
                    <!-- Pillar 1 -->
                    <div class="p-2.5 rounded-xl glass-panel glass-panel-hover transition">
                        <div class="flex items-center space-x-2 mb-1">
                            <div class="w-6 h-6 rounded-lg bg-indigo-500/20 border border-indigo-500/30 flex items-center justify-center text-indigo-400 flex-shrink-0">
                                <i data-lucide="rocket" class="w-3.5 h-3.5"></i>
                            </div>
                            <h3 class="text-[11px] font-bold text-white tracking-wide uppercase">Direct Matching</h3>
                        </div>
                        <p class="text-[10px] text-slate-300 leading-snug">
                            MCA-verified startups connect directly with sector-aligned angels.
                        </p>
                    </div>

                    <!-- Pillar 2 -->
                    <div class="p-2.5 rounded-xl glass-panel glass-panel-hover transition">
                        <div class="flex items-center space-x-2 mb-1">
                            <div class="w-6 h-6 rounded-lg bg-violet-500/20 border border-violet-500/30 flex items-center justify-center text-violet-400 flex-shrink-0">
                                <i data-lucide="file-lock-2" class="w-3.5 h-3.5"></i>
                            </div>
                            <h3 class="text-[11px] font-bold text-white tracking-wide uppercase">Secure Rooms</h3>
                        </div>
                        <p class="text-[10px] text-slate-300 leading-snug">
                            Encrypted pitch decks, automated NDAs & real-time viewer tracking.
                        </p>
                    </div>

                    <!-- Pillar 3 -->
                    <div class="p-2.5 rounded-xl glass-panel glass-panel-hover transition">
                        <div class="flex items-center space-x-2 mb-1">
                            <div class="w-6 h-6 rounded-lg bg-emerald-500/20 border border-emerald-500/30 flex items-center justify-center text-emerald-400 flex-shrink-0">
                                <i data-lucide="file-check-2" class="w-3.5 h-3.5"></i>
                            </div>
                            <h3 class="text-[11px] font-bold text-white tracking-wide uppercase">Fast Execution</h3>
                        </div>
                        <p class="text-[10px] text-slate-300 leading-snug">
                            Standardized SAFE notes, escrow disbursement, and digital cap tables.
                        </p>
                    </div>
                </div>

                <!-- Testimonial / Social Proof Snippet -->
                <div class="p-3 rounded-xl glass-panel border border-indigo-500/20 mb-4">
                    <div class="flex items-center justify-between mb-1">
                        <div class="flex items-center space-x-2.5">
                            <div class="w-6 h-6 rounded-full bg-gradient-to-tr from-indigo-500 to-pink-500 flex items-center justify-center font-bold text-[10px] text-white">
                                AS
                            </div>
                            <div>
                                <span class="text-[11px] font-bold text-white">Aarav Sharma</span>
                                <span class="text-[9.5px] text-slate-400 ml-1">• CEO, TechPulse AI (Raised ₹4.5 Cr)</span>
                            </div>
                        </div>
                        <div class="flex items-center text-amber-400 text-xs gap-0.5">
                            <i data-lucide="star" class="w-3 h-3 fill-amber-400"></i>
                            <i data-lucide="star" class="w-3 h-3 fill-amber-400"></i>
                            <i data-lucide="star" class="w-3 h-3 fill-amber-400"></i>
                            <i data-lucide="star" class="w-3 h-3 fill-amber-400"></i>
                            <i data-lucide="star" class="w-3 h-3 fill-amber-400"></i>
                        </div>
                    </div>
                    <p class="text-[10.5px] text-slate-300 italic leading-snug">
                        "Closing our seed round in 3 weeks was only possible because of verified angel matching. Total game changer."
                    </p>
                </div>
            </div>

            <!-- Bottom Proof & Trust Bar -->
            <div class="relative z-10 pt-4 mt-auto border-t border-slate-800/80">
                <div class="grid grid-cols-3 gap-3 text-center">
                    <div>
                        <div class="text-base xl:text-lg font-black text-white tracking-tight">₹280 Cr+</div>
                        <div class="text-[9px] uppercase font-bold text-slate-400 tracking-wider">Capital Raised</div>
                    </div>
                    <div class="border-x border-slate-800">
                        <div class="text-base xl:text-lg font-black text-white tracking-tight">1,400+</div>
                        <div class="text-[9px] uppercase font-bold text-slate-400 tracking-wider">Vetted Startups</div>
                    </div>
                    <div>
                        <div class="text-base xl:text-lg font-black text-white tracking-tight">99.2%</div>
                        <div class="text-[9px] uppercase font-bold text-slate-400 tracking-wider">Diligence Match</div>
                    </div>
                </div>

                <div class="mt-3 flex items-center justify-center space-x-1.5 text-[9.5px] text-slate-400">
                    <i data-lucide="shield-check" class="w-3.5 h-3.5 text-emerald-400"></i>
                    <span>SEBI & MCA Compliant Governance • 256-Bit SSL Protection</span>
                </div>
            </div>

        </div>


        <!-- ========================================================
             RIGHT SIDE: AUTHENTICATION FORM
             ======================================================== -->
        <div class="lg:w-1/2 xl:w-5/12 bg-white flex flex-col justify-between p-6 sm:p-8 xl:p-12 overflow-y-auto custom-scroll relative" id="right-column">
            
            <!-- Mobile Header with Brand & Sign Up Link -->
            <div class="flex items-center justify-between pb-4 mb-2 border-b border-slate-100 lg:border-none lg:pb-0">
                <div class="flex items-center space-x-2.5 lg:hidden">
                    <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center text-white">
                        <i data-lucide="zap" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <span class="text-xs font-extrabold text-slate-900 tracking-tight">STARTUP <span class="text-indigo-600">×</span> INVESTOR</span>
                        <span class="block text-[8px] text-slate-400 uppercase font-semibold">Sign In</span>
                    </div>
                </div>
                
                <div class="text-right ml-auto">
                    <span class="text-xs text-slate-500 hidden sm:inline">Don't have an account?</span>
                    <a href="<?= url('auth/register.php') ?>" class="sm:ml-1 text-xs font-bold text-indigo-600 hover:text-indigo-800 transition inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100">
                        <span>Create Account</span>
                        <i data-lucide="arrow-right" class="w-3 h-3"></i>
                    </a>
                </div>
            </div>

            <!-- Form Container (Centered Vertically) -->
            <div class="max-w-md w-full mx-auto my-auto py-2">
                
                <!-- Main Form Header -->
                <div class="mb-5">
                    <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900">
                        Welcome back
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1">
                        Sign in to your Founder, Investor, or Admin portal
                    </p>
                </div>


                <!-- Alerts (Flash & Errors) -->
                <?php if ($flash): ?>
                    <div class="mb-4 p-3 rounded-xl text-xs font-semibold border <?= $flash['type'] === 'success' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-rose-50 text-rose-800 border-rose-200' ?> flex items-center space-x-2 shadow-sm">
                        <i data-lucide="check-circle" class="w-4 h-4 flex-shrink-0 text-emerald-600"></i>
                        <span><?= htmlspecialchars($flash['message']) ?></span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error)): ?>
                    <div class="mb-4 p-3 rounded-xl text-xs font-semibold bg-rose-50 text-rose-800 border border-rose-200 flex items-center space-x-2 shadow-sm">
                        <i data-lucide="alert-circle" class="w-4 h-4 flex-shrink-0 text-rose-600"></i>
                        <span><?= htmlspecialchars($error) ?></span>
                    </div>
                <?php endif; ?>

                <!-- Sign In Form -->
                <form action="<?= url('auth/login.php') ?>" method="POST" class="space-y-3.5" id="loginForm">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                    <!-- Email Field -->
                    <div>
                        <label for="email" class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Email Address
                        </label>
                        <div class="relative">
                            <i data-lucide="mail" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                            <input type="email" id="email" name="email" required placeholder="name@company.com" autocomplete="email"
                                   class="w-full pl-10 pr-4 py-2.5 bg-slate-50 hover:bg-slate-50/80 border border-slate-200 focus:bg-white focus:border-indigo-600 focus:ring-4 focus:ring-indigo-600/10 rounded-xl text-xs sm:text-sm text-slate-900 placeholder-slate-400 outline-none transition font-medium">
                        </div>
                    </div>

                    <!-- Password Field with Show/Hide Toggle -->
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label for="password" class="block text-[11px] font-bold uppercase tracking-wider text-slate-700">
                                Password
                            </label>
                            <a href="<?= url('auth/forgot_password.php') ?>" class="text-[11px] text-indigo-600 hover:text-indigo-800 font-bold transition">
                                Forgot Password?
                            </a>
                        </div>
                        <div class="relative">
                            <i data-lucide="lock" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                            <input type="password" id="password" name="password" required placeholder="••••••••" autocomplete="current-password"
                                   class="w-full pl-10 pr-10 py-2.5 bg-slate-50 hover:bg-slate-50/80 border border-slate-200 focus:bg-white focus:border-indigo-600 focus:ring-4 focus:ring-indigo-600/10 rounded-xl text-xs sm:text-sm text-slate-900 placeholder-slate-400 outline-none transition font-medium">
                            
                            <!-- Toggle Password Visibility Button -->
                            <button type="button" 
                                    id="togglePassBtn"
                                    onclick="togglePasswordVisibility()" 
                                    class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 p-1.5 rounded-lg transition"
                                    title="Toggle password visibility">
                                <i data-lucide="eye" id="togglePassIcon" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Remember Me Option -->
                    <div class="flex items-center justify-between pt-0.5">
                        <label class="flex items-center space-x-2 cursor-pointer">
                            <input type="checkbox" id="remember" name="remember" class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                            <span class="text-xs text-slate-600 font-medium">Remember this browser</span>
                        </label>
                        <span class="text-[11px] text-slate-400 flex items-center gap-1">
                            <i data-lucide="shield" class="w-3 h-3 text-emerald-600"></i>
                            Encrypted
                        </span>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" id="submitBtn" class="w-full py-2.5 px-4 bg-indigo-600 hover:bg-indigo-700 active:scale-[0.99] text-white text-xs sm:text-sm font-bold rounded-xl shadow-lg shadow-indigo-600/25 hover:shadow-indigo-600/35 transition-all flex items-center justify-center gap-2 group mt-2">
                        <span id="btnText">Sign In to Portal</span>
                        <i data-lucide="arrow-right" id="btnIcon" class="w-4 h-4 group-hover:translate-x-1 transition-transform"></i>
                    </button>
                </form>

                <!-- Bottom Helper Link -->
                <div class="mt-5 pt-4 border-t border-slate-100 text-center">
                    <p class="text-xs text-slate-500 mb-2">
                        New to the platform? 
                        <a href="<?= url('auth/register.php') ?>" class="text-indigo-600 hover:text-indigo-800 font-bold ml-1 transition">
                            Create Account
                        </a>
                    </p>
                    <div class="flex items-center justify-center gap-2 text-[11px]">
                        <a href="<?= url('auth/register.php?role=founder') ?>" class="px-2.5 py-1 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-semibold transition flex items-center gap-1">
                            <i data-lucide="rocket" class="w-3 h-3"></i>
                            <span>Register as Founder</span>
                        </a>
                        <a href="<?= url('auth/register.php?role=investor') ?>" class="px-2.5 py-1 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-semibold transition flex items-center gap-1">
                            <i data-lucide="trending-up" class="w-3 h-3"></i>
                            <span>Register as Investor</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Bottom Security Note -->
            <div class="pt-4 border-t border-slate-100 text-center text-[10.5px] text-slate-400 flex items-center justify-center gap-1.5">
                <i data-lucide="shield-check" class="w-3.5 h-3.5 text-emerald-600"></i>
                <span>SEBI & MCA Compliant Portal • 256-Bit SSL Protection</span>
            </div>

        </div>

    </div>

    <!-- Interactive Scripts -->
    <script>
        // Initialize Lucide icons
        lucide.createIcons();

        // GSAP Entrance Animations
        gsap.from("#left-column > div", { 
            duration: 0.6, 
            y: 15, 
            opacity: 0, 
            stagger: 0.08, 
            ease: "power2.out" 
        });
        gsap.from("#right-column", { 
            duration: 0.5, 
            opacity: 0, 
            delay: 0.15, 
            ease: "power2.out" 
        });


        // Toggle Password Visibility
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('togglePassIcon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.setAttribute('data-lucide', 'eye-off');
            } else {
                passwordInput.type = 'password';
                toggleIcon.setAttribute('data-lucide', 'eye');
            }
            lucide.createIcons();
        }

        // Submit Button Loading Feedback
        document.getElementById('loginForm')?.addEventListener('submit', function() {
            const btn = document.getElementById('submitBtn');
            const btnText = document.getElementById('btnText');
            if (btn && btnText) {
                btn.classList.add('opacity-90', 'cursor-wait');
                btnText.textContent = 'Verifying credentials...';
            }
        });
    </script>
</body>
</html>
