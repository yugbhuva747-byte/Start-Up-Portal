<?php
/**
 * Authentication: Login Page
 * NEXORA Venture Capital Ecosystem
 * Role-Dedicated Clean Experience:
 * - If Founder: 100% pure Founder content, Founder deal room highlights, Founder sign-in form (No investor/admin tabs).
 * - If Investor: 100% pure Investor content, Deal pipeline highlights, Investor sign-in form (No founder/admin tabs).
 * - If Admin: Clean Administrative compliance sign-in.
 */
require_once __DIR__ . '/../config.php';

if (auth_check()) {
    $u = current_user();
    if ($u && $u['role'] !== 'admin' && function_exists('is_maintenance_mode') && is_maintenance_mode()) {
        header('Location: ' . url('maintenance.php'));
        exit;
    }
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

// Determine Role: ALWAYS Unified Single Login (User never has to choose who they are)
// Backend automatically identifies whether the user is a Founder or Investor from MySQL database
$role = (isset($_GET['role']) && $_GET['role'] === 'admin') ? 'admin' : 'unified';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please try again.';
    } elseif (function_exists('security_check_honeypot') && security_check_honeypot()) {
        $error = 'Suspicious automated request rejected by security shield.';
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
                        } elseif (function_exists('is_maintenance_mode') && is_maintenance_mode() && $user['role'] !== 'admin') {
                            $error = 'Access Restricted: The platform is currently in Maintenance Mode. Only administrators are permitted to sign in.';
                            log_security_event($user['id'], 'MAINTENANCE_LOGIN_BLOCKED', 'medium', "Non-admin user ({$user['role']}) tried to log in during maintenance mode: {$email}");
                        } elseif (empty($user['is_verified']) && (int)$user['is_verified'] === 0 && $user['role'] !== 'admin') {
                            require_once __DIR__ . '/../includes/mailer.php';
                            $otp = generate_2fa_otp((int)$user['id']);
                            send_registration_otp_email($user['email'], $user['name'], $otp, $user['role']);
                            send_registration_otp_sms($user['phone'] ?? '', $user['name'], $otp, $user['role']);

                            $_SESSION['reg_verify_user_id'] = (int)$user['id'];
                            $_SESSION['reg_verify_email'] = $user['email'];
                            $_SESSION['reg_verify_phone'] = $user['phone'] ?? '';
                            $_SESSION['reg_verify_name'] = $user['name'];
                            $_SESSION['reg_verify_role'] = $user['role'];
                            $_SESSION['reg_verify_code'] = $otp;
                            $_SESSION['reg_verify_sent_at'] = time();

                            set_flash('info', 'Please verify your registered account with the 6-digit code sent to your email and phone.');
                            header('Location: ' . url('auth/verify_otp.php'));
                            exit;
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

// Determine Role: Default to unified login for all visitors
$role = (isset($_GET['role']) && $_GET['role'] === 'admin') ? 'admin' : 'unified';

// Role-specific definitions
$roleMeta = [
    'unified' => [
        'page_title' => 'Sign In • ' . APP_NAME,
        'badge' => 'SMART VENTURE LOGIN • AUTO-DETECT ROLE',
        'badge_color' => 'bg-blue-50 text-blue-700 border-blue-200/80',
        'headline' => 'Connecting India & UAE High-Growth Startups with Institutional Capital.',
        'subtitle' => 'Unified secure login for Startup Founders, Accredited Investors, and Platform Administrators. Enter your registered email to access your dedicated portal.',
        'terminal_title' => 'NEXORA Portal',
        'terminal_round' => 'Live Ecosystem',
        'terminal_target' => '₹280 Cr+ Deployed',
        'terminal_percent' => '92%',
        'terminal_subtext' => 'Founders & Allocators Active',
        'stat_metric' => 'Smart Role Detect',
        'stat1_num' => '₹280 Cr+',
        'stat1_label' => 'Capital Raised',
        'stat2_num' => '1,400+',
        'stat2_label' => 'Active Members',
        'stat3_num' => '0%',
        'stat3_label' => 'Broker Fees',
        'pillar1_icon' => 'rocket',
        'pillar1_title' => 'For Founders',
        'pillar1_desc' => 'Direct angel syndicate matches, confidential data rooms & live pitch dealrooms.',
        'pillar2_icon' => 'layers',
        'pillar2_title' => 'For Investors',
        'pillar2_desc' => 'SEBI diligence, MCA audited cap tables, and syndication co-investments.',
        'pillar3_icon' => 'shield-check',
        'pillar3_title' => 'Smart Access',
        'pillar3_desc' => 'Single sign-in that automatically identifies your account type from your email.',
        'testimonial_avatar' => 'NX',
        'testimonial_name' => 'Venture Ecosystem Lead',
        'testimonial_role' => '• India & UAE Cross-Border Corridor',
        'testimonial_quote' => '"A seamless, unified experience for both entrepreneurs raising capital and allocators deploying checks."',
        'portal_badge' => 'SECURE SINGLE SIGN-IN',
        'portal_title' => 'Sign In to Your Account',
        'portal_sub' => 'Enter your registered email and password to access your dedicated portal.',
        'btn_text' => 'Sign In to Portal',
        'demo_email' => 'founder@techpulse.io',
        'demo_password' => 'password123',
        'demo_label' => 'Choose a Demo Account to test',
        'register_url' => url('auth/register.php'),
        'register_text' => 'Create New Account',
        'register_prompt' => 'New to NEXORA? Choose your path to register:',
        'switch_url' => url('index.php'),
        'switch_text' => 'Return to NEXORA Homepage'
    ],
    'founder' => [
        'page_title' => 'Founder Sign In • ' . APP_NAME,
        'badge' => 'FOUNDER CAPITAL ACCELERATOR • ZERO BROKERAGE',
        'badge_color' => 'bg-blue-50 text-blue-700 border-blue-200/80',
        'headline' => 'Where High-Growth Founders Close Seed & Series A Rounds.',
        'subtitle' => 'Pitch directly to 450+ verified angel syndicates and institutional VCs. 0% broker fees, automated MCA cap table compliance, and confidential data rooms.',
        'terminal_title' => 'TechPulse AI',
        'terminal_round' => 'Seed Round',
        'terminal_target' => '₹10.25 Cr / ₹12.5 Cr',
        'terminal_percent' => '82%',
        'terminal_subtext' => '450+ Active Allocators in Room',
        'stat_metric' => '18-Day Close Avg',
        'stat1_num' => '₹280 Cr+',
        'stat1_label' => 'Capital Raised',
        'stat2_num' => '18 Days',
        'stat2_label' => 'Avg Close Cycle',
        'stat3_num' => '0%',
        'stat3_label' => 'Broker Fees',
        'pillar1_icon' => 'rocket',
        'pillar1_title' => 'Direct Angel Match',
        'pillar1_desc' => 'Connect directly with verified angel networks with active checkbooks.',
        'pillar2_icon' => 'file-lock-2',
        'pillar2_title' => 'Confidential Rooms',
        'pillar2_desc' => 'Watermarked pitch decks, automated NDAs & real-time viewer tracking.',
        'pillar3_icon' => 'file-check-2',
        'pillar3_title' => 'Fast Term Sheets',
        'pillar3_desc' => 'Standardized SAFE notes, milestone escrow, and automated cap tables.',
        'testimonial_avatar' => 'VM',
        'testimonial_name' => 'Vivek Morwadiya',
        'testimonial_role' => '• Founder & CEO',
        'testimonial_quote' => '"Closing our seed round in 18 days was only possible through verified angel matching. Total game changer."',
        'portal_badge' => 'FOUNDER ACCESS PORTAL',
        'portal_title' => 'Founder Sign In',
        'portal_sub' => 'Sign in to access your Startup Deal Room, Pitch Analytics & Term Sheets.',
        'btn_text' => 'Sign In to Founder Portal',
        'demo_email' => 'founder@techpulse.io',
        'demo_password' => 'password123',
        'demo_label' => 'Auto-Fill Demo Founder',
        'register_url' => url('auth/register.php?role=founder'),
        'register_text' => 'Apply to Raise as Founder',
        'register_prompt' => 'New Founder on NEXORA?',
        'switch_url' => url('auth/login.php?role=investor'),
        'switch_text' => 'Are you an Accredited Investor? Sign in to Investor Portal'
    ],
    'investor' => [
        'page_title' => 'Investor Sign In • ' . APP_NAME,
        'badge' => 'ACCREDITED CAPITAL PIPELINE • SEBI VERIFIED',
        'badge_color' => 'bg-emerald-50 text-emerald-700 border-emerald-200/80',
        'headline' => 'Discover High-Conviction Startups Vetted for Rapid Scale.',
        'subtitle' => 'Access institutional-grade private deals with audited cap tables, verified traction metrics, and seamless co-investment syndication.',
        'terminal_title' => 'Curated Deal Pipeline',
        'terminal_round' => 'Due Diligence Active',
        'terminal_target' => '₹280 Cr+ Deployed',
        'terminal_percent' => '94%',
        'terminal_subtext' => '1,400+ Vetted Tech Startups',
        'stat_metric' => '18.4% Average IRR',
        'stat1_num' => '₹280 Cr+',
        'stat1_label' => 'Capital Deployed',
        'stat2_num' => '1,400+',
        'stat2_label' => 'Vetted Startups',
        'stat3_num' => '99.2%',
        'stat3_label' => 'Diligence Match',
        'pillar1_icon' => 'layers',
        'pillar1_title' => 'Pre-Vetted Deal Flow',
        'pillar1_desc' => 'Filter seed rounds by sector, ARR, and valuation with verified MCA records.',
        'pillar2_icon' => 'shield-check',
        'pillar2_title' => 'Secure Due Diligence',
        'pillar2_desc' => 'Instant access to verified pitch decks, financial models, and compliance records.',
        'pillar3_icon' => 'trending-up',
        'pillar3_title' => 'Syndication & IRR',
        'pillar3_desc' => 'Co-invest starting from ₹2 Lakhs, receive digital share certificates & track portfolio.',
        'testimonial_avatar' => 'VS',
        'testimonial_name' => 'Vikramaditya Singhania',
        'testimonial_role' => '• Syndicate Lead (32 Deals Closed)',
        'testimonial_quote' => '"The diligence depth, verified MCA records, and clean cap table documentation makes this our primary early-stage syndication portal."',
        'portal_badge' => 'ACCREDITED INVESTOR PORTAL',
        'portal_title' => 'Investor Sign In',
        'portal_sub' => 'Sign in to access Curated Deal Pipeline, Vetted Cap Tables & Syndicates.',
        'btn_text' => 'Sign In to Investor Portal',
        'demo_email' => 'investor@venturecapital.com',
        'demo_password' => 'password123',
        'demo_label' => 'Auto-Fill Demo Investor',
        'register_url' => url('auth/register.php?role=investor'),
        'register_text' => 'Join as Accredited Investor',
        'register_prompt' => 'New Investor on NEXORA?',
        'switch_url' => url('auth/login.php?role=founder'),
        'switch_text' => 'Are you a Startup Founder? Sign in to Founder Portal'
    ],
    'admin' => [
        'page_title' => 'Admin Sign In • ' . APP_NAME,
        'badge' => 'ADMINISTRATIVE GOVERNANCE • INTERNAL PORTAL',
        'badge_color' => 'bg-purple-50 text-purple-700 border-purple-200/80',
        'headline' => 'Platform Governance, Compliance & Syndicate Oversight.',
        'subtitle' => 'Manage founder applications, verify MCA corporate records, oversee investor accreditations, and audit venture capital transactions.',
        'terminal_title' => 'Governance Console',
        'terminal_round' => 'System Active',
        'terminal_target' => '100% Monitored',
        'terminal_percent' => '100%',
        'terminal_subtext' => 'Bank-Grade Security Active',
        'stat_metric' => '256-Bit SSL',
        'stat1_num' => '100%',
        'stat1_label' => 'MCA Audited',
        'stat2_num' => '24/7',
        'stat2_label' => 'Compliance Feed',
        'stat3_num' => 'ISO',
        'stat3_label' => '27001 Certified',
        'pillar1_icon' => 'shield-check',
        'pillar1_title' => 'MCA Compliance',
        'pillar1_desc' => 'Direct verification of corporate CIN, director DIN, and filings.',
        'pillar2_icon' => 'file-lock-2',
        'pillar2_title' => 'Audit & Security',
        'pillar2_desc' => 'Full tamper-evident audit logging and 2FA enforcement.',
        'pillar3_icon' => 'trending-up',
        'pillar3_title' => 'Syndicate Oversight',
        'pillar3_desc' => 'Real-time monitoring of escrow milestones and cap table sync.',
        'testimonial_avatar' => 'AD',
        'testimonial_name' => 'Platform Compliance Officer',
        'testimonial_role' => '• NEXORA Governance Committee',
        'testimonial_quote' => '"Enforcing institutional-grade compliance and transparency across every private venture round."',
        'portal_badge' => 'PLATFORM GOVERNANCE',
        'portal_title' => 'Administrator Sign In',
        'portal_sub' => 'Sign in with your administrative credentials to manage portal operations.',
        'btn_text' => 'Sign In to Admin Portal',
        'demo_email' => 'admin@portal.com',
        'demo_password' => 'password123',
        'demo_label' => 'Auto-Fill Demo Admin',
        'register_url' => url('index.php'),
        'register_text' => 'Return to Home',
        'register_prompt' => 'Authorized Administrators Only',
        'switch_url' => url('auth/login.php?role=founder'),
        'switch_text' => 'Go to Founder Portal'
    ]
];

$cur = $roleMeta[$role] ?? $roleMeta['unified'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($cur['page_title']) ?></title>
    <meta name="description" content="<?= htmlspecialchars($cur['subtitle']) ?>">
    
    <!-- Satoshi Typography System -->
    <link rel="stylesheet" href="../assets/css/fonts.css">
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Satoshi', 'Inter', '-apple-system', 'sans-serif'],
                        satoshi: ['Satoshi', 'Inter', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            900: '#1e3a8a',
                        }
                    }
                }
            }
        };
    </script>
    
    <!-- Animation & Icon Libraries -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        body { 
            font-family: 'Satoshi', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .mesh-bg {
            background-color: #060A14;
            background-image: 
                radial-gradient(at 0% 0%, rgba(37, 99, 235, 0.28) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(99, 102, 241, 0.22) 0px, transparent 50%),
                radial-gradient(at 50% 50%, rgba(15, 23, 42, 0.85) 0px, transparent 75%);
        }

        .dot-pattern {
            background-image: radial-gradient(rgba(255, 255, 255, 0.12) 1px, transparent 1px);
            background-size: 24px 24px;
        }

        .glass-panel {
            background: rgba(255, 255, 255, 0.04);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.09);
        }

        .glass-panel-hover:hover {
            background: rgba(255, 255, 255, 0.07);
            border-color: rgba(255, 255, 255, 0.18);
            transform: translateY(-2px);
        }

        .custom-scroll::-webkit-scrollbar {
            width: 5px;
        }
        .custom-scroll::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-scroll::-webkit-scrollbar-thumb {
            background: rgba(148, 163, 184, 0.25);
            border-radius: 9999px;
        }
        .custom-scroll::-webkit-scrollbar-thumb:hover {
            background: rgba(148, 163, 184, 0.45);
        }

        .input-glow:focus-within {
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
            border-color: #2563EB;
        }
        .auth-glow-button {
            background-size: 200% auto;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .auth-glow-button:hover {
            background-position: right center;
        }
        .demo-pass-card {
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .demo-pass-card:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
<body class="bg-[#F8FAFC] text-slate-900 min-h-screen lg:h-screen lg:overflow-hidden selection:bg-blue-600 selection:text-white relative flex flex-col justify-between antialiased">

    <!-- Ambient background decorative graphics -->
    <div class="fixed inset-0 pointer-events-none overflow-hidden -z-10">
        <div class="absolute -top-40 left-1/2 -translate-x-1/2 w-[1000px] h-[500px] bg-gradient-to-b from-blue-500/10 via-indigo-500/5 to-transparent rounded-full blur-3xl"></div>
        <div class="absolute -bottom-32 -left-32 w-96 h-96 bg-blue-400/8 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-32 -right-32 w-96 h-96 bg-emerald-400/8 rounded-full blur-3xl"></div>
        <div class="absolute inset-0 dot-pattern opacity-50"></div>
    </div>

    <!-- Top Navigation Header -->
    <header class="w-full max-w-6xl mx-auto px-4 sm:px-8 py-3.5 sm:py-4 flex items-center justify-between shrink-0">
        <a href="<?= url('index.php') ?>" class="inline-flex items-center gap-2.5 group" aria-label="NEXORA Home">
            <span class="text-2xl font-black tracking-tight text-slate-900 font-satoshi flex items-center gap-1.5">
                NEXORA
                <span class="w-2.5 h-2.5 rounded-full bg-blue-600 shadow-[0_0_12px_rgba(37,99,235,0.7)] animate-pulse"></span>
            </span>
            <span class="hidden sm:inline-block text-xs tracking-wider text-slate-500 font-bold uppercase pl-2.5 border-l border-slate-300">
                <?= ucfirst($role) ?> Portal
            </span>
        </a>

        <div class="flex items-center gap-3">
            <span class="text-sm text-slate-600 font-medium hidden sm:inline">New to NEXORA?</span>
            <a href="<?= url('auth/register.php') ?>" class="text-xs sm:text-sm font-bold text-blue-700 hover:text-blue-900 transition inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-blue-50 hover:bg-blue-100 border border-blue-200 shadow-xs">
                <span>Sign Up</span>
                <i data-lucide="arrow-right" class="w-4 h-4"></i>
            </a>
            <a href="<?= url('index.php') ?>" class="text-xs sm:text-sm font-semibold text-slate-700 hover:text-slate-900 transition inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-slate-300 hover:bg-slate-100 bg-white shadow-xs">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                <span class="hidden xs:inline">Back to Home</span>
            </a>
        </div>
    </header>

    <!-- Main Centered Content (Big Width, Easy-to-Read, Zero Scroll) -->
    <main class="w-full max-w-5xl mx-auto px-4 sm:px-6 my-auto py-2 shrink-0" id="auth-main-container">
        
        <!-- Elevated Wide Login Card -->
        <div class="bg-white rounded-3xl border border-slate-200 shadow-[0_20px_50px_rgba(15,23,42,0.08)] p-6 sm:p-8 lg:p-9 relative overflow-hidden" id="auth-card">
            
            <!-- Top Subtle Gradient Line Accent -->
            <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-blue-600 via-indigo-600 to-emerald-500"></div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-10 items-center">
                
                <!-- LEFT COLUMN: 50% Width with Founder & Investor Switching -->
                <div class="space-y-4 lg:border-r lg:border-slate-200 lg:pr-8">

                    <!-- Role Switcher Tabs (Founder & Investor) -->
                    <div class="p-1 rounded-xl bg-slate-100 border border-slate-300 grid grid-cols-2 gap-1.5 shrink-0 w-full shadow-2xs">
                        <a href="<?= url('auth/login.php?role=founder') ?>" 
                           class="py-2 px-3 rounded-lg text-center text-xs sm:text-sm font-extrabold transition-all flex items-center justify-center gap-1.5 <?= $role === 'founder' ? 'bg-white text-blue-700 shadow-sm border border-slate-300' : 'text-slate-600 hover:text-slate-900' ?>">
                            <i data-lucide="rocket" class="w-4 h-4 <?= $role === 'founder' ? 'text-blue-600' : 'text-slate-500' ?>"></i>
                            <span>Founder</span>
                        </a>
                        <a href="<?= url('auth/login.php?role=investor') ?>" 
                           class="py-2 px-3 rounded-lg text-center text-xs sm:text-sm font-extrabold transition-all flex items-center justify-center gap-1.5 <?= $role === 'investor' ? 'bg-white text-emerald-700 shadow-sm border border-slate-300' : 'text-slate-600 hover:text-slate-900' ?>">
                            <i data-lucide="briefcase" class="w-4 h-4 <?= $role === 'investor' ? 'text-emerald-600' : 'text-slate-500' ?>"></i>
                            <span>Investor</span>
                        </a>
                    </div>

                    <div>
                        <div class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-bold mb-2.5 <?= $cur['badge_color'] ?> border border-blue-200 shadow-2xs">
                            <span class="w-2 h-2 rounded-full bg-blue-600 animate-pulse"></span>
                            <span class="tracking-wide"><?= htmlspecialchars($cur['portal_badge']) ?></span>
                        </div>

                        <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900 font-satoshi leading-tight" id="portalHeaderTitle">
                            <?= htmlspecialchars($cur['portal_title']) ?>
                        </h1>
                        <p class="text-sm text-slate-600 mt-1.5 font-medium leading-relaxed" id="portalHeaderSub">
                            <?= htmlspecialchars($cur['portal_sub']) ?>
                        </p>
                    </div>

                    <!-- Real-Time Role Detection Pill -->
                    <div id="roleDetectIndicator" class="transition-all duration-300 hidden">
                        <!-- Injected by JavaScript -->
                    </div>

                    <!-- Quick 1-Click Fast Demo Accounts (High Contrast & Clear) -->
                    <div class="p-4 rounded-2xl bg-slate-50/90 border border-slate-200 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-1.5 font-satoshi">
                                <i data-lucide="zap" class="w-4 h-4 text-amber-500 fill-amber-500"></i>
                                1-Click Demo Accounts
                            </span>
                            <span class="text-xs text-slate-600 font-mono font-semibold bg-white px-2 py-0.5 rounded-md border border-slate-300">pass: password123</span>
                        </div>
                        
                        <div class="grid grid-cols-3 gap-2.5">
                            <!-- Founder Demo -->
                            <button type="button" onclick="fillRoleDemo('founder@techpulse.io', 'password123', 'founder')" 
                                    class="p-2.5 rounded-xl bg-white hover:bg-blue-50 border border-slate-300 hover:border-blue-400 text-left transition flex flex-col justify-between shadow-2xs group cursor-pointer">
                                <div class="flex items-center justify-between mb-1">
                                    <div class="w-6 h-6 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center shrink-0">
                                        <i data-lucide="rocket" class="w-3.5 h-3.5"></i>
                                    </div>
                                    <span class="text-[10px] font-bold text-blue-700 group-hover:translate-x-0.5 transition-transform">Use &rarr;</span>
                                </div>
                                <div>
                                    <span class="block text-xs font-extrabold text-slate-900 group-hover:text-blue-700">Founder</span>
                                    <span class="block text-[11px] text-slate-500 font-medium truncate">TechPulse AI</span>
                                </div>
                            </button>

                            <!-- Investor Demo -->
                            <button type="button" onclick="fillRoleDemo('investor@venturecapital.com', 'password123', 'investor')" 
                                    class="p-2.5 rounded-xl bg-white hover:bg-emerald-50 border border-slate-300 hover:border-emerald-400 text-left transition flex flex-col justify-between shadow-2xs group cursor-pointer">
                                <div class="flex items-center justify-between mb-1">
                                    <div class="w-6 h-6 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                                        <i data-lucide="briefcase" class="w-3.5 h-3.5"></i>
                                    </div>
                                    <span class="text-[10px] font-bold text-emerald-700 group-hover:translate-x-0.5 transition-transform">Use &rarr;</span>
                                </div>
                                <div>
                                    <span class="block text-xs font-extrabold text-slate-900 group-hover:text-emerald-700">Investor</span>
                                    <span class="block text-[11px] text-slate-500 font-medium truncate">Venture VC</span>
                                </div>
                            </button>

                            <!-- Admin Demo -->
                            <button type="button" onclick="fillRoleDemo('admin@portal.com', 'password123', 'admin')" 
                                    class="p-2.5 rounded-xl bg-white hover:bg-purple-50 border border-slate-300 hover:border-purple-400 text-left transition flex flex-col justify-between shadow-2xs group cursor-pointer">
                                <div class="flex items-center justify-between mb-1">
                                    <div class="w-6 h-6 rounded-lg bg-purple-100 text-purple-700 flex items-center justify-center shrink-0">
                                        <i data-lucide="shield" class="w-3.5 h-3.5"></i>
                                    </div>
                                    <span class="text-[10px] font-bold text-purple-700 group-hover:translate-x-0.5 transition-transform">Use &rarr;</span>
                                </div>
                                <div>
                                    <span class="block text-xs font-extrabold text-slate-900 group-hover:text-purple-700">Admin</span>
                                    <span class="block text-[11px] text-slate-500 font-medium truncate">Compliance</span>
                                </div>
                            </button>
                        </div>

                        <p class="text-xs text-slate-500 leading-normal flex items-center gap-1.5 font-medium">
                            <i data-lucide="check-circle" class="w-3.5 h-3.5 text-blue-600 shrink-0"></i>
                            <span>Click any card to auto-fill credentials and test login.</span>
                        </p>
                    </div>

                    <!-- Trust Tags Strip -->
                    <div class="flex items-center gap-3 text-xs text-slate-500 pt-1 font-semibold">
                        <span class="flex items-center gap-1 text-slate-700">
                            <i data-lucide="shield-check" class="w-4 h-4 text-emerald-600"></i>
                            SEBI &amp; MCA Diligence
                        </span>
                        <span>&bull;</span>
                        <span class="flex items-center gap-1 text-slate-700">
                            <i data-lucide="lock" class="w-4 h-4 text-blue-600"></i>
                            256-Bit SSL
                        </span>
                        <span>&bull;</span>
                        <span class="text-slate-700">0% Commission</span>
                    </div>
                </div>

                <!-- RIGHT COLUMN: 50% Width Authentication Form -->
                <div class="space-y-4">
                    
                    <!-- Alerts (Maintenance, Flash & Errors) -->
                    <?php if (function_exists('is_maintenance_mode') && is_maintenance_mode()): ?>
                        <div class="p-3.5 rounded-2xl text-sm font-semibold bg-amber-50 text-amber-950 border border-amber-300 flex items-start space-x-2.5 shadow-sm">
                            <i data-lucide="alert-triangle" class="w-5 h-5 flex-shrink-0 text-amber-600 mt-0.5"></i>
                            <div>
                                <span class="font-bold block text-sm">Maintenance Mode Active</span>
                                <span class="text-xs text-amber-900 font-normal">Public access is paused. Platform Administrators only.</span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($flash): ?>
                        <div class="p-3.5 rounded-2xl text-sm font-semibold border <?= $flash['type'] === 'success' ? 'bg-emerald-50 text-emerald-950 border-emerald-300' : 'bg-rose-50 text-rose-950 border-rose-300' ?> flex items-center space-x-2.5 shadow-sm">
                            <i data-lucide="check-circle" class="w-5 h-5 flex-shrink-0 text-emerald-600"></i>
                            <span><?= htmlspecialchars($flash['message']) ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($error)): ?>
                        <div class="p-3.5 rounded-2xl text-sm font-semibold bg-rose-50 text-rose-950 border border-rose-300 flex items-center justify-between shadow-sm">
                            <div class="flex items-center space-x-2.5">
                                <i data-lucide="alert-circle" class="w-5 h-5 flex-shrink-0 text-rose-600"></i>
                                <span><?= htmlspecialchars($error) ?></span>
                            </div>
                            <a href="<?= url('auth/forgot_password.php') . (!empty($email) ? '?email=' . urlencode($email) : '') ?>" class="text-xs text-blue-700 hover:text-blue-900 font-extrabold underline whitespace-nowrap ml-2">
                                Reset Password?
                            </a>
                        </div>
                    <?php endif; ?>

                    <!-- Sign In Form -->
                    <form action="<?= url('auth/login.php') ?>" method="POST" class="space-y-4" id="loginForm">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <?= security_honeypot_field() ?>

                        <!-- Email Field -->
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label for="email" class="block text-sm font-bold text-slate-800">
                                    Email Address <span class="text-rose-600 font-bold">*</span>
                                </label>
                                <span id="roleDetectStatus" class="text-xs font-semibold text-blue-600"></span>
                            </div>
                            <div class="relative">
                                <i data-lucide="mail" class="w-5 h-5 text-slate-500 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                                <input type="email" id="email" name="email" required placeholder="name@company.com" autocomplete="email"
                                       class="w-full pl-11 pr-4 py-3 bg-white border-2 border-slate-200 hover:border-slate-300 focus:border-blue-600 focus:ring-4 focus:ring-blue-100 rounded-xl text-sm sm:text-base text-slate-900 placeholder-slate-400 outline-none transition font-medium shadow-2xs">
                            </div>
                        </div>

                        <!-- Password Field with Show/Hide Toggle -->
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label for="password" class="block text-sm font-bold text-slate-800">
                                    Password <span class="text-rose-600 font-bold">*</span>
                                </label>
                                <a href="<?= url('auth/forgot_password.php') ?>" id="forgotPassLink" class="text-xs sm:text-sm text-blue-600 hover:text-blue-800 font-bold transition">
                                    Forgot Password?
                                </a>
                            </div>
                            <div class="relative">
                                <i data-lucide="lock" class="w-5 h-5 text-slate-500 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                                <input type="password" id="password" name="password" required placeholder="Enter your password" autocomplete="current-password"
                                       class="w-full pl-11 pr-11 py-3 bg-white border-2 border-slate-200 hover:border-slate-300 focus:border-blue-600 focus:ring-4 focus:ring-blue-100 rounded-xl text-sm sm:text-base text-slate-900 placeholder-slate-400 outline-none transition font-medium shadow-2xs">
                                
                                <button type="button" 
                                        id="togglePassBtn"
                                        onclick="togglePasswordVisibility()" 
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-800 p-1.5 rounded-lg transition"
                                        title="Toggle password visibility">
                                    <i data-lucide="eye" id="togglePassIcon" class="w-5 h-5"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Remember Me Option & Encryption Indicator -->
                        <div class="flex items-center justify-between pt-1">
                            <label class="flex items-center space-x-2 cursor-pointer">
                                <input type="checkbox" id="remember" name="remember" class="w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500 cursor-pointer">
                                <span class="text-sm text-slate-700 font-semibold">Remember this device</span>
                            </label>
                            <span class="text-xs text-slate-500 font-semibold flex items-center gap-1.5 bg-slate-100 px-2.5 py-1 rounded-lg">
                                <i data-lucide="shield-check" class="w-4 h-4 text-emerald-600"></i>
                                <span>256-Bit Encrypted</span>
                            </span>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" id="submitBtn" class="w-full py-3.5 px-6 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white text-base font-extrabold rounded-xl shadow-lg shadow-blue-600/30 hover:shadow-blue-600/40 transition-all flex items-center justify-center gap-2 group mt-2 font-satoshi tracking-wide cursor-pointer">
                            <span id="btnText"><?= htmlspecialchars($cur['btn_text']) ?></span>
                            <i data-lucide="arrow-right" id="btnIcon" class="w-5 h-5 group-hover:translate-x-1 transition-transform"></i>
                        </button>
                    </form>

                    <!-- Registration Prompt -->
                    <div class="pt-4 border-t border-slate-200 flex items-center justify-between text-sm text-slate-600 font-medium">
                        <span>Don't have an account yet?</span>
                        <a href="<?= url('auth/register.php') ?>" class="text-blue-600 font-bold hover:text-blue-800 hover:underline transition">
                            Create New Account &rarr;
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <!-- Bottom Footer Trust Strip -->
    <footer class="w-full max-w-6xl mx-auto px-4 sm:px-8 py-3 text-center text-xs text-slate-500 flex flex-col sm:flex-row items-center justify-between gap-2 border-t border-slate-200 shrink-0 font-medium">
        <div class="flex items-center gap-2">
            <i data-lucide="shield-check" class="w-4 h-4 text-emerald-600"></i>
            <span>SEBI &amp; MCA Compliant Governance &bull; 256-Bit SSL Protection</span>
        </div>
        <div class="flex items-center gap-4 text-slate-600">
            <a href="<?= url('legal/terms.php') ?>" class="hover:text-slate-900 transition font-semibold">Terms of Service</a>
            <a href="<?= url('legal/privacy.php') ?>" class="hover:text-slate-900 transition font-semibold">Privacy Policy</a>
            <span>&copy; <?= date('Y') ?> NEXORA Portal</span>
        </div>
    </footer>

<!-- Interactive Scripts -->
    <script>
        lucide.createIcons();

        // GSAP Entrance Animations
        gsap.from("#auth-card", { duration: 0.6, y: 20, opacity: 0, ease: "power2.out" });

        // 1-Click Fast Demo Fill with Auto-Detection Trigger
        function fillRoleDemo(email, pass, roleHint) {
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');

            if (emailInput && passwordInput) {
                emailInput.value = email;
                passwordInput.value = pass;

                emailInput.classList.add('bg-blue-50');
                passwordInput.classList.add('bg-blue-50');
                setTimeout(() => {
                    emailInput.classList.remove('bg-blue-50');
                    passwordInput.classList.remove('bg-blue-50');
                }, 500);

                // Trigger live role check immediately
                detectEmailRole(email);
            }
        }

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

        // Real-Time Dynamic Email Role Detection Engine
        let detectTimeout = null;
        const emailInput = document.getElementById('email');
        const roleIndicator = document.getElementById('roleDetectIndicator');
        const roleStatus = document.getElementById('roleDetectStatus');
        const btnText = document.getElementById('btnText');
        const portalHeaderTitle = document.getElementById('portalHeaderTitle');
        const portalHeaderSub = document.getElementById('portalHeaderSub');

        async function detectEmailRole(email) {
            email = email.trim();
            if (!email || !email.includes('@') || email.length < 5) {
                if (roleIndicator) roleIndicator.classList.add('hidden');
                if (roleStatus) roleStatus.textContent = '';
                if (btnText) btnText.textContent = 'Sign In to Portal';
                return;
            }

            if (roleStatus) roleStatus.textContent = 'Checking account...';

            try {
                const res = await fetch(`<?= url('api/check_email.php') ?>?email=${encodeURIComponent(email)}`);
                const data = await res.json();

                if (data.success && data.exists) {
                    if (roleStatus) roleStatus.textContent = 'Verified Account';
                    roleIndicator.classList.remove('hidden');

                    if (data.role === 'founder') {
                        roleIndicator.innerHTML = `
                            <div class="p-3 rounded-xl bg-blue-50/90 border border-blue-200 text-blue-900 flex items-center justify-between text-xs sm:text-sm animate-fadeIn">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center shrink-0">
                                        <svg class="w-4.5 h-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z"/>
                                            <path d="m12 15-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-4 11a22.35 22.35 0 0 1-4 2z"/>
                                            <path d="M9 12H4s.55-3.03 2-4c1.62-1.08 5 0 5 0"/>
                                            <path d="M12 15v5s3.03-.55 4-2c1.08-1.62 0-5 0-5"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="font-black text-slate-900 flex items-center gap-1.5 text-xs sm:text-sm">
                                            <span>Startup Founder Account</span>
                                            <span class="text-[10px] bg-blue-200/80 text-blue-800 px-2 py-0.5 rounded-md font-bold uppercase tracking-wider">Verified</span>
                                        </div>
                                        <div class="text-xs text-blue-700 font-medium">${data.name || ''} ${data.subtitle ? '&bull; ' + data.subtitle : ''}</div>
                                    </div>
                                </div>
                                <span class="w-2.5 h-2.5 rounded-full bg-blue-600 animate-pulse"></span>
                            </div>
                        `;
                        if (btnText) btnText.textContent = 'Sign In to Founder Portal →';
                        if (portalHeaderTitle) portalHeaderTitle.textContent = 'Founder Sign In';
                        if (portalHeaderSub) portalHeaderSub.textContent = 'Access your startup deal room, pitch analytics & active angel syndicates.';
                    } else if (data.role === 'investor') {
                        roleIndicator.innerHTML = `
                            <div class="p-3 rounded-xl bg-emerald-50/90 border border-emerald-200 text-emerald-900 flex items-center justify-between text-xs sm:text-sm animate-fadeIn">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                                        <svg class="w-4.5 h-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <rect width="20" height="14" x="2" y="7" rx="2" ry="2"/>
                                            <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="font-black text-slate-900 flex items-center gap-1.5 text-xs sm:text-sm">
                                            <span>Accredited Investor Account</span>
                                            <span class="text-[10px] bg-emerald-200/80 text-emerald-800 px-2 py-0.5 rounded-md font-bold uppercase tracking-wider">Verified</span>
                                        </div>
                                        <div class="text-xs text-emerald-700 font-medium">${data.name || ''} ${data.subtitle ? '&bull; ' + data.subtitle : ''}</div>
                                    </div>
                                </div>
                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 animate-pulse"></span>
                            </div>
                        `;
                        if (btnText) btnText.textContent = 'Sign In to Investor Portal →';
                        if (portalHeaderTitle) portalHeaderTitle.textContent = 'Investor Sign In';
                        if (portalHeaderSub) portalHeaderSub.textContent = 'Access curated startup deal pipeline, audited cap tables & co-investments.';
                    } else if (data.role === 'admin') {
                        roleIndicator.innerHTML = `
                            <div class="p-3 rounded-xl bg-purple-50/90 border border-purple-200 text-purple-900 flex items-center justify-between text-xs sm:text-sm animate-fadeIn">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-lg bg-purple-100 text-purple-700 flex items-center justify-center shrink-0">
                                        <svg class="w-4.5 h-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                                            <path d="m9 12 2 2 4-4"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="font-black text-slate-900 text-xs sm:text-sm">Platform Governance Administrator</div>
                                        <div class="text-xs text-purple-700 font-medium">${data.name || ''} &bull; Compliance Console</div>
                                    </div>
                                </div>
                                <span class="w-2.5 h-2.5 rounded-full bg-purple-600 animate-pulse"></span>
                            </div>
                        `;
                        if (btnText) btnText.textContent = 'Sign In to Admin Console →';
                        if (portalHeaderTitle) portalHeaderTitle.textContent = 'Administrator Sign In';
                    }
                } else {
                    if (roleStatus) roleStatus.textContent = '';
                    roleIndicator.classList.remove('hidden');
                    roleIndicator.innerHTML = `
                        <div class="p-2.5 rounded-xl bg-amber-50/90 border border-amber-200/80 text-amber-900 flex items-center justify-between text-xs">
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 rounded-md bg-amber-100 text-amber-700 flex items-center justify-center shrink-0">
                                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>
                                    </svg>
                                </div>
                                <span class="font-medium">New email? You can register as a <strong>Founder</strong> or <strong>Investor</strong> below.</span>
                            </div>
                        </div>
                    `;
                    if (btnText) btnText.textContent = 'Sign In to Portal →';
                }
            } catch (err) {
                if (roleStatus) roleStatus.textContent = '';
            }
        }

        if (emailInput) {
            emailInput.addEventListener('input', function() {
                clearTimeout(detectTimeout);
                detectTimeout = setTimeout(() => {
                    detectEmailRole(this.value);
                }, 300);
            });
            emailInput.addEventListener('paste', function() {
                setTimeout(() => {
                    detectEmailRole(this.value);
                }, 50);
            });
            emailInput.addEventListener('change', function() {
                detectEmailRole(this.value);
            });
            emailInput.addEventListener('blur', function() {
                detectEmailRole(this.value);
            });
            // If already filled on page load
            if (emailInput.value.trim()) {
                detectEmailRole(emailInput.value.trim());
            }
        }

        // Submit Button Loading Feedback
        document.getElementById('loginForm')?.addEventListener('submit', function() {
            const btn = document.getElementById('submitBtn');
            const btnText = document.getElementById('btnText');
            if (btn && btnText) {
                btn.classList.add('opacity-90', 'cursor-wait');
                btnText.textContent = 'Verifying credentials & role...';
            }
        });

        // Dynamic forward of typed email to Forgot Password page
        const forgotPassLink = document.getElementById('forgotPassLink');
        if (forgotPassLink) {
            forgotPassLink.addEventListener('click', function() {
                const emailInput = document.getElementById('email');
                if (emailInput && emailInput.value.trim()) {
                    this.href = '<?= url('auth/forgot_password.php') ?>?email=' + encodeURIComponent(emailInput.value.trim());
                }
            });
        }
    </script>
</body>
</html>
