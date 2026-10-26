<?php
/**
 * Authentication: Registration Page
 * NEXORA Venture Capital Ecosystem
 * Role-Dedicated Clean Experience:
 * - If Founder: 100% pure Founder onboarding form, Founder deal room perks (No investor/admin tabs).
 * - If Investor: 100% pure Investor accreditation form, Deal pipeline perks (No founder/admin tabs).
 */
require_once __DIR__ . '/../config.php';

if (auth_check()) {
    $u = current_user();
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
$selectedRole = strtolower($_GET['role'] ?? $_SESSION['portal_role'] ?? $_COOKIE['portal_role'] ?? 'founder');
if ($selectedRole === 'startup') {
    $selectedRole = 'founder';
}
if (!in_array($selectedRole, ['founder', 'investor'])) {
    $selectedRole = 'founder';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please try again.';
    } elseif (function_exists('security_check_honeypot') && security_check_honeypot()) {
        $error = 'Suspicious automated registration attempt blocked by security shield.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirmation'] ?? '';
        $role = strtolower(trim($_POST['role'] ?? $selectedRole));
        if (!in_array($role, ['founder', 'investor'])) {
            $role = 'founder';
        }
        $city = trim($_POST['city'] ?? 'Bengaluru');
        $country = trim($_POST['country'] ?? 'India');
        $acceptTerms = isset($_POST['terms']);

        // Founder-specific fields
        $companyName = trim($_POST['company_name'] ?? '');
        $industry = trim($_POST['industry'] ?? 'AI/SaaS');
        $stage = trim($_POST['stage'] ?? 'Seed');
        $designation = trim($_POST['designation'] ?? 'Founder & CEO');
        $pitch = trim($_POST['pitch'] ?? '');

        // Investor-specific fields
        $investorType = trim($_POST['investor_type'] ?? 'Angel Investor');
        $ticketRange = trim($_POST['ticket_range'] ?? '10-50L');
        $preferredIndustries = trim($_POST['preferred_industries'] ?? 'FinTech, AI/SaaS, HealthTech');

        // Parse ticket range
        $minTicket = 200000.00;
        $maxTicket = 5000000.00;
        if ($ticketRange === '2-10L') {
            $minTicket = 200000.00;
            $maxTicket = 1000000.00;
        } elseif ($ticketRange === '10-50L') {
            $minTicket = 1000000.00;
            $maxTicket = 5000000.00;
        } elseif ($ticketRange === '50L-2Cr') {
            $minTicket = 5000000.00;
            $maxTicket = 20000000.00;
        } elseif ($ticketRange === '2Cr+') {
            $minTicket = 20000000.00;
            $maxTicket = 100000000.00;
        }

        if (empty($name) || empty($email) || empty($password)) {
            $error = 'Please fill in all mandatory fields.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters long.';
        } elseif ($password !== $passwordConfirm) {
            $error = 'Passwords do not match.';
        } elseif (!$acceptTerms) {
            $error = 'You must agree to the Terms of Use and Risk Disclosures.';
        } else {
            $db = get_db();
            if ($db) {
                $checkStmt = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
                $checkStmt->execute([$email]);
                if ($checkStmt->fetch()) {
                    $error = 'An account with this email address already exists. Please sign in.';
                } else {
                    $passwordHash = password_hash($password, PASSWORD_BCRYPT);
                    $stmt = $db->prepare("
                        INSERT INTO users (name, email, phone, password_hash, role, status, is_verified, city, country, created_at)
                        VALUES (?, ?, ?, ?, ?, 'active', 0, ?, ?, NOW())
                    ");
                    $stmt->execute([$name, $email, $phone, $passwordHash, $role, $city, $country]);
                    $userId = (int)$db->lastInsertId();

                    if ($role === 'founder') {
                        // Create Founder Profile
                        $fpStmt = $db->prepare("INSERT INTO founder_profiles (user_id, designation, bio, created_at) VALUES (?, ?, ?, NOW())");
                        $fpStmt->execute([$userId, $designation ?: 'Founder & CEO', $pitch]);

                        // Create Company Record
                        $cName = !empty($companyName) ? $companyName : ($name . "'s Venture");
                        $compStmt = $db->prepare("
                            INSERT INTO companies (name, industry, stage, pitch, description, city, country, verified_status, created_at)
                            VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
                        ");
                        $compStmt->execute([
                            $cName,
                            $industry ?: 'AI/SaaS',
                            $stage ?: 'Seed',
                            $pitch ?: "Building scalable solutions in {$industry}",
                            $pitch ?: "High-growth venture focused on {$industry} innovations.",
                            $city ?: 'Bengaluru',
                            $country ?: 'India'
                        ]);
                        $companyId = (int)$db->lastInsertId();

                        $cfStmt = $db->prepare("INSERT INTO company_founders (company_id, user_id, designation, is_signatory, created_at) VALUES (?, ?, ?, 1, NOW())");
                        $cfStmt->execute([$companyId, $userId, $designation ?: 'Founder & CEO']);
                    } elseif ($role === 'investor') {
                        // Create Investor Profile
                        $ipStmt = $db->prepare("INSERT INTO investor_profiles (user_id, investor_type, experience_years, risk_disclosure_accepted, created_at) VALUES (?, ?, 3, 1, NOW())");
                        $ipStmt->execute([$userId, $investorType ?: 'Angel Investor']);
                        
                        // Create Investor Preferences
                        $prefStmt = $db->prepare("INSERT INTO investor_preferences (user_id, preferred_industries, preferred_stages, min_ticket, max_ticket, created_at) VALUES (?, ?, 'Seed, Pre-Series A, Series A', ?, ?, NOW())");
                        $prefStmt->execute([$userId, $preferredIndustries, $minTicket, $maxTicket]);
                    }

                    // Connect Founder Invite if registered through an invite link
                    $inviteCode = $_SESSION['active_founder_invite'] ?? trim($_GET['invite'] ?? ($_GET['ref'] ?? ''));
                    if (!empty($inviteCode)) {
                        try {
                            $db->prepare("
                                UPDATE founder_invites 
                                SET invited_user_id = ?, 
                                    status = 'registered', 
                                    updated_at = NOW() 
                                WHERE invite_code = ?
                            ")->execute([$userId, $inviteCode]);
                        } catch (\Throwable $e) {}
                    }

                    // Activate Free Trial plan subscription
                    if (function_exists('activate_user_subscription')) {
                        activate_user_subscription($userId, 'free_trial', 'monthly');
                    }

                    // 6-Digit One-Time Password (OTP) Verification for New Registration
                    require_once __DIR__ . '/../includes/mailer.php';

                    // Generate cryptographically secure 6-digit OTP code (10 minutes validity)
                    $otp = generate_2fa_otp($userId);

                    // Dispatch to Email
                    send_registration_otp_email($email, $name, $otp, $role);

                    // Dispatch to Mobile via SMS
                    send_registration_otp_sms($phone, $name, $otp, $role);

                    // Setup pending registration verification session
                    $_SESSION['reg_verify_user_id'] = $userId;
                    $_SESSION['reg_verify_email'] = $email;
                    $_SESSION['reg_verify_phone'] = $phone;
                    $_SESSION['reg_verify_name'] = $name;
                    $_SESSION['reg_verify_role'] = $role;
                    $_SESSION['reg_verify_code'] = $otp; // Preserved for local dev preview
                    $_SESSION['reg_verify_sent_at'] = time();

                    log_audit($userId, 'USER_REGISTER_OTP_SENT', 'users', $userId, "Registration verification OTP dispatched for {$role}");
                    set_flash('info', "A 6-digit verification code has been dispatched to your email and mobile number.");
                    
                    header('Location: ' . url('auth/verify_otp.php'));
                    exit;
                }
            } else {
                $error = 'Database connection error. Please run setup.php.';
            }
        }
    }
}

// Role-specific configuration for Register
$regMeta = [
    'founder' => [
        'page_title' => 'Apply as a Startup Founder • ' . APP_NAME,
        'badge' => 'FOUNDER CAPITAL ACCELERATOR • ZERO BROKERAGE',
        'badge_color' => 'bg-blue-50 text-blue-700 border-blue-200/80',
        'headline' => 'Connect Directly with 450+ Verified Angel Syndicates and VCs.',
        'subtitle' => 'Raise your Seed or Pre-Series A with zero broker fees. Share your confidential data room with accredited investors ready to deploy.',
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
        'pillar1_desc' => 'Pitch directly to 450+ verified active angels without cold emails.',
        'pillar2_icon' => 'file-lock-2',
        'pillar2_title' => 'Confidential Rooms',
        'pillar2_desc' => 'Watermarked pitch decks, automated NDAs & real-time viewer tracking.',
        'pillar3_icon' => 'file-check-2',
        'pillar3_title' => 'Standardized Terms',
        'pillar3_desc' => 'Close rounds faster with standardized SAFE notes, convertible notes & escrow.',
        'testimonial_avatar' => 'VM',
        'testimonial_name' => 'Vivek Morwadiya',
        'testimonial_role' => '• Founder & CEO',
        'testimonial_quote' => '"Closing our seed round in 18 days was only possible through verified angel matching. Total game changer."',
        'form_badge' => 'STARTUP FOUNDER APPLICATION',
        'form_title' => 'Apply as a Founder',
        'form_sub' => 'Launch your verified startup deal room and connect with active venture capital.',
        'btn_text' => 'Create Account',
        'login_url' => url('auth/login.php?role=founder'),
        'switch_url' => url('auth/register.php?role=investor'),
        'switch_text' => 'Are you an Accredited Investor looking to deploy capital? Join as Investor'
    ],
    'investor' => [
        'page_title' => 'Register as an Accredited Investor • ' . APP_NAME,
        'badge' => 'ACCREDITED CAPITAL PIPELINE • SEBI VERIFIED',
        'badge_color' => 'bg-emerald-50 text-emerald-700 border-emerald-200/80',
        'headline' => 'Discover High-Conviction Startups Vetted for Rapid Venture Scale.',
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
        'pillar2_desc' => 'Instant access to verified pitch decks, financial models, cap tables & diligence.',
        'pillar3_icon' => 'trending-up',
        'pillar3_title' => 'Syndication & High IRR',
        'pillar3_desc' => 'Co-invest starting from ₹2 Lakhs, receive digital share certificates & track portfolio.',
        'testimonial_avatar' => 'VS',
        'testimonial_name' => 'Vikramaditya Singhania',
        'testimonial_role' => '• Syndicate Lead (32 Deals Closed)',
        'testimonial_quote' => '"The diligence depth, verified MCA records, and clean cap table documentation makes this our primary early-stage syndication portal."',
        'form_badge' => 'ACCREDITED INVESTOR ONBOARDING',
        'form_title' => 'Register as an Investor',
        'form_sub' => 'Access curated private startup deals, verified cap tables, and syndicate co-investments.',
        'btn_text' => 'Create Account',
        'login_url' => url('auth/login.php?role=investor'),
        'switch_url' => url('auth/register.php?role=founder'),
        'switch_text' => 'Are you a Startup Founder seeking capital? Apply as Founder'
    ]
];

$cur = $regMeta[$selectedRole];
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
    </style>
</head>
<body class="bg-[#F8FAFC] text-slate-900 min-h-screen selection:bg-blue-600 selection:text-white relative flex flex-col justify-between antialiased">

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
                <?= ucfirst($selectedRole) ?> Registration
            </span>
        </a>

        <div class="flex items-center gap-3">
            <span class="text-sm text-slate-600 font-medium hidden sm:inline">Already registered?</span>
            <a href="<?= url('auth/login.php') ?>" class="text-xs sm:text-sm font-bold text-blue-700 hover:text-blue-900 transition inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-blue-50 hover:bg-blue-100 border border-blue-200 shadow-xs">
                <span>Sign In</span>
                <i data-lucide="arrow-right" class="w-4 h-4"></i>
            </a>
            <a href="<?= url('index.php') ?>" class="text-xs sm:text-sm font-semibold text-slate-700 hover:text-slate-900 transition inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-slate-300 hover:bg-slate-100 bg-white shadow-xs">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                <span class="hidden xs:inline">Back to Home</span>
            </a>
        </div>
    </header>

    <!-- Main Centered Content (50 / 50 Balanced Grid, Clean & Premium) -->
    <main class="w-full max-w-5xl mx-auto px-4 sm:px-6 my-auto py-4 shrink-0" id="auth-main-container">
        
        <!-- Elevated Wide Registration Card -->
        <div class="bg-white rounded-3xl border border-slate-200 shadow-[0_20px_50px_rgba(15,23,42,0.08)] p-6 sm:p-8 lg:p-9 relative overflow-hidden" id="auth-card">
            
            <!-- Top Subtle Gradient Line Accent -->
            <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-blue-600 via-indigo-600 to-emerald-500"></div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-10 items-center">
                
                <!-- LEFT COLUMN: 50% Role Switcher, Branding, Value Pillars & Stats -->
                <div class="space-y-4 lg:border-r lg:border-slate-200 lg:pr-8">

                    <!-- Role Switcher Tabs (Founder & Investor) -->
                    <div class="p-1 rounded-xl bg-slate-100 border border-slate-300 grid grid-cols-2 gap-1.5 shrink-0 w-full shadow-2xs">
                        <a href="<?= url('auth/register.php?role=founder') ?>" 
                           class="py-2 px-3 rounded-lg text-center text-xs sm:text-sm font-extrabold transition-all flex items-center justify-center gap-1.5 <?= $selectedRole === 'founder' ? 'bg-white text-blue-700 shadow-sm border border-slate-300' : 'text-slate-600 hover:text-slate-900' ?>">
                            <i data-lucide="rocket" class="w-4 h-4 <?= $selectedRole === 'founder' ? 'text-blue-600' : 'text-slate-500' ?>"></i>
                            <span>Founder</span>
                        </a>
                        <a href="<?= url('auth/register.php?role=investor') ?>" 
                           class="py-2 px-3 rounded-lg text-center text-xs sm:text-sm font-extrabold transition-all flex items-center justify-center gap-1.5 <?= $selectedRole === 'investor' ? 'bg-white text-emerald-700 shadow-sm border border-slate-300' : 'text-slate-600 hover:text-slate-900' ?>">
                            <i data-lucide="briefcase" class="w-4 h-4 <?= $selectedRole === 'investor' ? 'text-emerald-600' : 'text-slate-500' ?>"></i>
                            <span>Investor</span>
                        </a>
                    </div>

                    <div>
                        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold mb-2 <?= $cur['badge_color'] ?> border border-blue-200 shadow-2xs">
                            <span class="w-2 h-2 rounded-full bg-blue-600 animate-pulse"></span>
                            <span class="tracking-wide"><?= htmlspecialchars($cur['form_badge']) ?></span>
                        </div>

                        <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900 font-satoshi leading-tight">
                            <?= htmlspecialchars($cur['form_title']) ?>
                        </h1>
                        <p class="text-xs sm:text-sm text-slate-600 mt-1 font-medium leading-relaxed">
                            <?= htmlspecialchars($cur['form_sub']) ?>
                        </p>
                    </div>

                    <!-- 3 Value Pillars -->
                    <div class="space-y-2.5">
                        <div class="flex items-start gap-3 p-2.5 rounded-xl bg-slate-50 border border-slate-200/80 hover:bg-white hover:border-slate-300 transition-colors">
                            <div class="w-8 h-8 rounded-lg <?= $selectedRole === 'founder' ? 'bg-blue-100 text-blue-700' : 'bg-emerald-100 text-emerald-700' ?> flex items-center justify-center shrink-0 mt-0.5">
                                <i data-lucide="<?= htmlspecialchars($cur['pillar1_icon']) ?>" class="w-4 h-4"></i>
                            </div>
                            <div>
                                <h3 class="text-xs font-bold text-slate-900"><?= htmlspecialchars($cur['pillar1_title']) ?></h3>
                                <p class="text-[11px] text-slate-600 font-medium leading-snug"><?= htmlspecialchars($cur['pillar1_desc']) ?></p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3 p-2.5 rounded-xl bg-slate-50 border border-slate-200/80 hover:bg-white hover:border-slate-300 transition-colors">
                            <div class="w-8 h-8 rounded-lg <?= $selectedRole === 'founder' ? 'bg-blue-100 text-blue-700' : 'bg-emerald-100 text-emerald-700' ?> flex items-center justify-center shrink-0 mt-0.5">
                                <i data-lucide="<?= htmlspecialchars($cur['pillar2_icon']) ?>" class="w-4 h-4"></i>
                            </div>
                            <div>
                                <h3 class="text-xs font-bold text-slate-900"><?= htmlspecialchars($cur['pillar2_title']) ?></h3>
                                <p class="text-[11px] text-slate-600 font-medium leading-snug"><?= htmlspecialchars($cur['pillar2_desc']) ?></p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3 p-2.5 rounded-xl bg-slate-50 border border-slate-200/80 hover:bg-white hover:border-slate-300 transition-colors">
                            <div class="w-8 h-8 rounded-lg <?= $selectedRole === 'founder' ? 'bg-blue-100 text-blue-700' : 'bg-emerald-100 text-emerald-700' ?> flex items-center justify-center shrink-0 mt-0.5">
                                <i data-lucide="<?= htmlspecialchars($cur['pillar3_icon']) ?>" class="w-4 h-4"></i>
                            </div>
                            <div>
                                <h3 class="text-xs font-bold text-slate-900"><?= htmlspecialchars($cur['pillar3_title']) ?></h3>
                                <p class="text-[11px] text-slate-600 font-medium leading-snug"><?= htmlspecialchars($cur['pillar3_desc']) ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Live Market Stats Strip -->
                    <div class="grid grid-cols-3 gap-2 pt-1 border-t border-slate-150">
                        <div class="text-center p-2 rounded-lg bg-slate-50 border border-slate-200/70">
                            <div class="text-xs sm:text-sm font-black <?= $selectedRole === 'founder' ? 'text-blue-700' : 'text-emerald-700' ?>"><?= htmlspecialchars($cur['stat1_num']) ?></div>
                            <div class="text-[10px] text-slate-500 font-bold uppercase tracking-tight"><?= htmlspecialchars($cur['stat1_label']) ?></div>
                        </div>
                        <div class="text-center p-2 rounded-lg bg-slate-50 border border-slate-200/70">
                            <div class="text-xs sm:text-sm font-black text-slate-800"><?= htmlspecialchars($cur['stat2_num']) ?></div>
                            <div class="text-[10px] text-slate-500 font-bold uppercase tracking-tight"><?= htmlspecialchars($cur['stat2_label']) ?></div>
                        </div>
                        <div class="text-center p-2 rounded-lg bg-slate-50 border border-slate-200/70">
                            <div class="text-xs sm:text-sm font-black <?= $selectedRole === 'founder' ? 'text-blue-700' : 'text-emerald-700' ?>"><?= htmlspecialchars($cur['stat3_num']) ?></div>
                            <div class="text-[10px] text-slate-500 font-bold uppercase tracking-tight"><?= htmlspecialchars($cur['stat3_label']) ?></div>
                        </div>
                    </div>

                </div>

                <!-- RIGHT COLUMN: 50% Registration Form -->
                <div>
                    <!-- Error Notification -->
                    <?php if (!empty($error)): ?>
                        <div class="mb-3 p-3 rounded-2xl text-xs sm:text-sm font-semibold bg-rose-50 text-rose-950 border border-rose-300 flex items-center space-x-2.5 shadow-sm">
                            <i data-lucide="alert-circle" class="w-5 h-5 flex-shrink-0 text-rose-600"></i>
                            <span><?= htmlspecialchars($error) ?></span>
                        </div>
                    <?php endif; ?>

                    <form action="<?= url('auth/register.php?role=' . urlencode($selectedRole)) ?>" method="POST" id="registrationForm" class="space-y-3">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <?= security_honeypot_field() ?>
                        <input type="hidden" name="role" value="<?= htmlspecialchars($selectedRole) ?>">

                        <!-- Full Legal Name -->
                        <div>
                            <label class="block text-xs font-bold text-slate-800 mb-1">
                                Full Legal Name <span class="text-rose-600 font-bold">*</span>
                            </label>
                            <div class="relative">
                                <i data-lucide="user" class="w-4 h-4 text-slate-500 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                                <input type="text" name="name" required placeholder="e.g. Aarav Sharma"
                                       value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                                       class="w-full pl-10 pr-3.5 py-2.5 bg-white border-2 border-slate-200 hover:border-slate-300 focus:border-blue-600 focus:ring-4 focus:ring-blue-100 rounded-xl text-xs sm:text-sm text-slate-900 placeholder-slate-400 outline-none transition font-medium">
                            </div>
                        </div>

                        <!-- Email & Phone (Grid) -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-800 mb-1">
                                    Work Email <span class="text-rose-600 font-bold">*</span>
                                </label>
                                <div class="relative">
                                    <i data-lucide="mail" class="w-4 h-4 text-slate-500 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                                    <input type="email" name="email" required placeholder="name@company.com"
                                           value="<?= htmlspecialchars($_POST['email'] ?? $_GET['email'] ?? '') ?>"
                                           class="w-full pl-10 pr-3 py-2.5 bg-white border-2 border-slate-200 hover:border-slate-300 focus:border-blue-600 focus:ring-4 focus:ring-blue-100 rounded-xl text-xs sm:text-sm text-slate-900 placeholder-slate-400 outline-none transition font-medium">
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-800 mb-1">
                                    Phone Number
                                </label>
                                <div class="relative">
                                    <i data-lucide="phone" class="w-4 h-4 text-slate-500 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                                    <input type="tel" name="phone" placeholder="+91 98765 43210"
                                           value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
                                           class="w-full pl-10 pr-3 py-2.5 bg-white border-2 border-slate-200 hover:border-slate-300 focus:border-blue-600 focus:ring-4 focus:ring-blue-100 rounded-xl text-xs sm:text-sm text-slate-900 placeholder-slate-400 outline-none transition font-medium">
                                </div>
                            </div>
                        </div>

                        <!-- City & Country (Grid) -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-800 mb-1">City</label>
                                <div class="relative">
                                    <i data-lucide="map-pin" class="w-4 h-4 text-slate-500 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                                    <input type="text" name="city" value="<?= htmlspecialchars($_POST['city'] ?? 'Bengaluru') ?>" required
                                           class="w-full pl-10 pr-3 py-2.5 bg-white border-2 border-slate-200 hover:border-slate-300 focus:border-blue-600 focus:ring-4 focus:ring-blue-100 rounded-xl text-xs sm:text-sm text-slate-900 outline-none transition font-medium">
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-800 mb-1">Country</label>
                                <div class="relative">
                                    <i data-lucide="globe" class="w-4 h-4 text-slate-500 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                                    <input type="text" name="country" value="<?= htmlspecialchars($_POST['country'] ?? 'India') ?>" required
                                           class="w-full pl-10 pr-3 py-2.5 bg-white border-2 border-slate-200 hover:border-slate-300 focus:border-blue-600 focus:ring-4 focus:ring-blue-100 rounded-xl text-xs sm:text-sm text-slate-900 outline-none transition font-medium">
                                </div>
                            </div>
                        </div>

                        <!-- Password & Confirm Password (Grid) -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-800 mb-1">
                                    Password <span class="text-rose-600 font-bold">*</span>
                                </label>
                                <div class="relative">
                                    <i data-lucide="lock" class="w-4 h-4 text-slate-500 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                                    <input type="password" id="password" name="password" required placeholder="Min 6 characters"
                                           oninput="checkPasswordStrength(this.value)" autocomplete="new-password"
                                           class="w-full pl-10 pr-9 py-2.5 bg-white border-2 border-slate-200 hover:border-slate-300 focus:border-blue-600 focus:ring-4 focus:ring-blue-100 rounded-xl text-xs sm:text-sm text-slate-900 placeholder-slate-400 outline-none transition font-medium">
                                    <button type="button" onclick="togglePasswordVisibility('password', 'pwd-icon-1')" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-800 p-1 rounded-lg" title="Toggle password">
                                        <i data-lucide="eye" id="pwd-icon-1" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-800 mb-1">
                                    Confirm <span class="text-rose-600 font-bold">*</span>
                                </label>
                                <div class="relative">
                                    <i data-lucide="lock" class="w-4 h-4 text-slate-500 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                                    <input type="password" id="password_confirmation" name="password_confirmation" required placeholder="Repeat password"
                                           autocomplete="new-password"
                                           class="w-full pl-10 pr-9 py-2.5 bg-white border-2 border-slate-200 hover:border-slate-300 focus:border-blue-600 focus:ring-4 focus:ring-blue-100 rounded-xl text-xs sm:text-sm text-slate-900 placeholder-slate-400 outline-none transition font-medium">
                                    <button type="button" onclick="togglePasswordVisibility('password_confirmation', 'pwd-icon-2')" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-800 p-1 rounded-lg" title="Toggle password">
                                        <i data-lucide="eye" id="pwd-icon-2" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Strength meter -->
                        <div class="space-y-1" id="strength-container" style="display: none;">
                            <div class="flex items-center justify-between text-xs text-slate-600 font-medium">
                                <span>Strength: <span id="strength-label" class="font-bold text-slate-800">Weak</span></span>
                            </div>
                            <div class="h-1.5 w-full bg-slate-200 rounded-full overflow-hidden flex">
                                <div id="strength-bar" class="h-full bg-rose-500 transition-all duration-300 w-1/4"></div>
                            </div>
                        </div>

                        <!-- Terms & Conditions Checkbox -->
                        <div class="pt-0.5">
                            <label class="flex items-start space-x-2 cursor-pointer group">
                                <input type="checkbox" name="terms" required <?= isset($_POST['terms']) ? 'checked' : '' ?>
                                       class="mt-1 w-4 h-4 text-blue-600 rounded border-slate-300 focus:ring-blue-500 cursor-pointer">
                                <span class="text-xs text-slate-700 leading-snug font-medium group-hover:text-slate-900 transition">
                                    I agree to the <a href="<?= url('legal/terms.php') ?>" target="_blank" class="text-blue-600 hover:text-blue-800 font-bold underline">Terms of Service</a> &amp; <a href="<?= url('legal/privacy.php') ?>" target="_blank" class="text-blue-600 hover:text-blue-800 font-bold underline">Privacy Policy</a>.
                                </span>
                            </label>
                        </div>

                        <!-- Submit Button -->
                        <div class="pt-1">
                            <button type="submit" id="submitBtn"
                                    class="w-full py-3 px-6 <?= $selectedRole === 'founder' ? 'bg-blue-600 hover:bg-blue-700 shadow-blue-600/30 hover:shadow-blue-600/40' : 'bg-emerald-600 hover:bg-emerald-700 shadow-emerald-600/30 hover:shadow-emerald-600/40' ?> active:scale-[0.99] text-white text-sm sm:text-base font-extrabold rounded-xl shadow-lg transition-all flex items-center justify-center space-x-2 group font-satoshi tracking-wide cursor-pointer">
                                <span id="btn-submit-text"><?= htmlspecialchars($cur['btn_text']) ?></span>
                                <i data-lucide="arrow-right" class="w-4 h-4 group-hover:translate-x-1 transition-transform"></i>
                            </button>
                        </div>

                        <!-- Already have account link -->
                        <div class="pt-1 text-center text-xs sm:text-sm text-slate-600 font-medium">
                            Already registered? 
                            <a href="<?= url('auth/login.php') ?>" class="<?= $selectedRole === 'founder' ? 'text-blue-600' : 'text-emerald-600' ?> font-bold hover:underline">
                                Sign In &rarr;
                            </a>
                        </div>
                    </form>
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

<!-- Scripts -->
    <script>
        lucide.createIcons();

        // Toggle Password Visibility
        function togglePasswordVisibility(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            if (!input || !icon) return;

            if (input.type === 'password') {
                input.type = 'text';
                icon.setAttribute('data-lucide', 'eye-off');
            } else {
                input.type = 'password';
                icon.setAttribute('data-lucide', 'eye');
            }
            lucide.createIcons();
        }

        // Live Password Strength Indicator
        function checkPasswordStrength(password) {
            const container = document.getElementById('strength-container');
            const bar = document.getElementById('strength-bar');
            const label = document.getElementById('strength-label');

            if (!password) {
                container.style.display = 'none';
                return;
            }

            container.style.display = 'block';

            let score = 0;
            if (password.length >= 6) score++;
            if (password.length >= 10) score++;
            if (/[A-Z]/.test(password) && /[a-z]/.test(password)) score++;
            if (/[0-9]/.test(password) || /[^A-Za-z0-9]/.test(password)) score++;

            if (score <= 1) {
                bar.className = 'h-full bg-rose-500 transition-all duration-300 w-1/4';
                label.innerText = 'Weak';
                label.className = 'font-bold text-rose-600';
            } else if (score === 2) {
                bar.className = 'h-full bg-amber-500 transition-all duration-300 w-2/4';
                label.innerText = 'Moderate';
                label.className = 'font-bold text-amber-600';
            } else if (score === 3) {
                bar.className = 'h-full bg-blue-500 transition-all duration-300 w-3/4';
                label.innerText = 'Good';
                label.className = 'font-bold text-blue-600';
            } else {
                bar.className = 'h-full bg-emerald-500 transition-all duration-300 w-full';
                label.innerText = 'Strong';
                label.className = 'font-bold text-emerald-600';
            }
        }

        // Loading state on submit
        document.getElementById('registrationForm')?.addEventListener('submit', function() {
            const btn = document.getElementById('submitBtn');
            const btnText = document.getElementById('btn-submit-text');
            if (btn && btnText) {
                btn.classList.add('opacity-90', 'cursor-wait');
                btnText.textContent = 'Creating Account...';
            }
        });
    </script>
</body>
</html>
