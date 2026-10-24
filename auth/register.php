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
        'btn_text' => 'Submit Founder Application & Launch Deal Room',
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
        'btn_text' => 'Complete Investor Registration & Access Deals',
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
    </style>
</head>
<body class="bg-slate-900 text-slate-900 min-h-screen selection:bg-blue-600 selection:text-white">

    <div class="min-h-screen lg:h-screen lg:overflow-hidden flex flex-col lg:flex-row">
        
        <!-- ========================================================
             LEFT SIDE: ROLE-DEDICATED SHOWCASE (FOUNDER / INVESTOR)
             ======================================================== -->
        <div class="lg:w-1/2 xl:w-7/12 mesh-bg text-white p-6 sm:p-8 xl:p-10 2xl:p-12 flex flex-col justify-between relative overflow-y-auto custom-scroll border-b lg:border-b-0 lg:border-r border-slate-800/80" id="left-column">
            
            <div class="absolute inset-0 dot-pattern opacity-35 pointer-events-none"></div>
            
            <div class="absolute -top-20 -left-20 w-80 h-80 bg-blue-600/25 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute bottom-8 -right-8 w-72 h-72 bg-indigo-600/20 rounded-full blur-3xl pointer-events-none"></div>

            <div class="relative z-10">
                <!-- Top Brand Header & Return Link -->
                <div class="flex items-center justify-between mb-5 xl:mb-6">
                    <a href="<?= url('index.php') ?>" class="inline-flex items-center gap-2.5 group" aria-label="NEXORA Home">
                        <span class="text-2xl font-black tracking-tight text-white font-satoshi flex items-center gap-1.5">
                            NEXORA
                            <span class="w-2.5 h-2.5 rounded-full bg-blue-500 shadow-[0_0_12px_rgba(59,130,246,0.9)] animate-pulse"></span>
                        </span>
                        <span class="hidden sm:inline-block text-[9.5px] tracking-widest text-slate-400 font-bold uppercase pl-2.5 border-l border-slate-700/80">
                            <?= ucfirst($selectedRole) ?> Registration
                        </span>
                    </a>

                    <a href="<?= url('index.php') ?>" class="inline-flex items-center text-xs font-semibold text-slate-300 hover:text-white transition group space-x-1.5 px-3.5 py-1.5 rounded-xl glass-panel hover:bg-white/10">
                        <i data-lucide="arrow-left" class="w-3.5 h-3.5 group-hover:-translate-x-0.5 transition-transform"></i>
                        <span>Back to Home</span>
                    </a>
                </div>

                <!-- Category Pill Tag -->
                <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full glass-panel text-[11px] font-semibold text-blue-300 mb-3 border border-blue-500/30 shadow-sm">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span><?= htmlspecialchars($cur['badge']) ?></span>
                </div>

                <!-- Big Dynamic Headline -->
                <h1 class="text-2xl sm:text-3xl xl:text-4xl font-black text-white tracking-tight leading-snug mb-2 font-satoshi">
                    <?= htmlspecialchars($cur['headline']) ?>
                </h1>

                <!-- Subtitle -->
                <p class="text-slate-300 text-xs sm:text-sm leading-relaxed mb-4 max-w-xl">
                    <?= htmlspecialchars($cur['subtitle']) ?>
                </p>

                <!-- ==============================================
                     VENTURE TERMINAL & LIVE DEALROOM HUD
                     ============================================== -->
                <div class="relative rounded-2xl overflow-hidden border border-slate-700/70 shadow-2xl shadow-blue-950/70 group mb-4 bg-slate-950/60 backdrop-blur-xl">
                    <div class="relative h-44 sm:h-52 xl:h-56 overflow-hidden">
                        <img src="<?= url('assets/images/login_hero.jpg') ?>" 
                             alt="NEXORA Venture Dealroom" 
                             class="w-full h-full object-cover object-center group-hover:scale-105 transition-transform duration-700 opacity-75">
                        
                        <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/50 to-slate-950/20"></div>

                        <!-- Floating Glass Badges on Top of Image -->
                        <div class="absolute top-3 left-3 flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-900/90 backdrop-blur-md border border-white/15 text-[10.5px] font-semibold text-white shadow-lg">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                            <span>Live Dealroom Pipeline</span>
                        </div>

                        <div class="absolute top-3 right-3 flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-900/90 backdrop-blur-md border border-white/15 text-[10.5px] font-semibold text-blue-300 shadow-lg">
                            <i data-lucide="shield-check" class="w-3.5 h-3.5 text-emerald-400"></i>
                            <span>SEBI & MCA Verified</span>
                        </div>

                        <!-- Center Active Deal Spotlight Card -->
                        <div class="absolute bottom-3 left-3 right-3 p-3 rounded-xl bg-slate-900/85 backdrop-blur-md border border-white/10 shadow-xl">
                            <div class="flex items-center justify-between mb-1.5">
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-lg bg-blue-600 flex items-center justify-center text-white text-[10px] font-black">
                                        <?= strtoupper(substr($cur['terminal_title'], 0, 2)) ?>
                                    </div>
                                    <div>
                                        <div class="text-[11px] font-bold text-white flex items-center gap-1.5">
                                            <span><?= htmlspecialchars($cur['terminal_title']) ?></span>
                                            <span class="px-1.5 py-0.2 rounded text-[9px] bg-blue-500/20 text-blue-300 border border-blue-500/30 font-medium"><?= htmlspecialchars($cur['terminal_round']) ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="text-[11px] font-black text-emerald-400"><?= htmlspecialchars($cur['terminal_target']) ?></span>
                                </div>
                            </div>

                            <!-- Progress Track -->
                            <div class="w-full bg-slate-800 rounded-full h-1.5 mb-2 overflow-hidden">
                                <div class="bg-gradient-to-r from-blue-500 to-emerald-400 h-1.5 rounded-full" style="width: <?= htmlspecialchars($cur['terminal_percent']) ?>"></div>
                            </div>

                            <div class="flex items-center justify-between text-[10px] text-slate-300">
                                <div class="flex items-center gap-1.5">
                                    <div class="flex -space-x-1">
                                        <span class="w-5 h-5 rounded-full bg-blue-600 flex items-center justify-center text-[8px] font-bold text-white border border-slate-900">AS</span>
                                        <span class="w-5 h-5 rounded-full bg-emerald-600 flex items-center justify-center text-[8px] font-bold text-white border border-slate-900">VS</span>
                                        <span class="w-5 h-5 rounded-full bg-violet-600 flex items-center justify-center text-[8px] font-bold text-white border border-slate-900">PP</span>
                                    </div>
                                    <span class="text-[10px] text-slate-300"><?= htmlspecialchars($cur['terminal_subtext']) ?></span>
                                </div>
                                <span class="text-emerald-400 font-bold flex items-center gap-1">
                                    <i data-lucide="zap" class="w-3 h-3 text-amber-400"></i>
                                    <?= htmlspecialchars($cur['stat_metric']) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3 Pillars -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 mb-4">
                    <div class="p-2.5 rounded-xl glass-panel glass-panel-hover transition duration-200">
                        <div class="flex items-center space-x-2 mb-1">
                            <div class="w-6 h-6 rounded-lg bg-blue-500/20 border border-blue-500/30 flex items-center justify-center text-blue-400 flex-shrink-0">
                                <i data-lucide="<?= $cur['pillar1_icon'] ?>" class="w-3.5 h-3.5"></i>
                            </div>
                            <h3 class="text-[11px] font-bold text-white tracking-wide uppercase font-satoshi"><?= htmlspecialchars($cur['pillar1_title']) ?></h3>
                        </div>
                        <p class="text-[10px] text-slate-300 leading-snug">
                            <?= htmlspecialchars($cur['pillar1_desc']) ?>
                        </p>
                    </div>

                    <div class="p-2.5 rounded-xl glass-panel glass-panel-hover transition duration-200">
                        <div class="flex items-center space-x-2 mb-1">
                            <div class="w-6 h-6 rounded-lg bg-indigo-500/20 border border-indigo-500/30 flex items-center justify-center text-indigo-400 flex-shrink-0">
                                <i data-lucide="<?= $cur['pillar2_icon'] ?>" class="w-3.5 h-3.5"></i>
                            </div>
                            <h3 class="text-[11px] font-bold text-white tracking-wide uppercase font-satoshi"><?= htmlspecialchars($cur['pillar2_title']) ?></h3>
                        </div>
                        <p class="text-[10px] text-slate-300 leading-snug">
                            <?= htmlspecialchars($cur['pillar2_desc']) ?>
                        </p>
                    </div>

                    <div class="p-2.5 rounded-xl glass-panel glass-panel-hover transition duration-200">
                        <div class="flex items-center space-x-2 mb-1">
                            <div class="w-6 h-6 rounded-lg bg-emerald-500/20 border border-emerald-500/30 flex items-center justify-center text-emerald-400 flex-shrink-0">
                                <i data-lucide="<?= $cur['pillar3_icon'] ?>" class="w-3.5 h-3.5"></i>
                            </div>
                            <h3 class="text-[11px] font-bold text-white tracking-wide uppercase font-satoshi"><?= htmlspecialchars($cur['pillar3_title']) ?></h3>
                        </div>
                        <p class="text-[10px] text-slate-300 leading-snug">
                            <?= htmlspecialchars($cur['pillar3_desc']) ?>
                        </p>
                    </div>
                </div>

                <!-- Testimonial -->
                <div class="p-3 rounded-xl glass-panel border border-blue-500/20 mb-4 shadow-sm">
                    <div class="flex items-center justify-between mb-1">
                        <div class="flex items-center space-x-2.5">
                            <div class="w-6 h-6 rounded-full bg-gradient-to-tr from-blue-600 to-indigo-500 flex items-center justify-center font-bold text-[10px] text-white">
                                <?= htmlspecialchars($cur['testimonial_avatar']) ?>
                            </div>
                            <div>
                                <span class="text-[11px] font-bold text-white"><?= htmlspecialchars($cur['testimonial_name']) ?></span>
                                <span class="text-[9.5px] text-slate-400 ml-1"><?= htmlspecialchars($cur['testimonial_role']) ?></span>
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
                        <?= htmlspecialchars($cur['testimonial_quote']) ?>
                    </p>
                </div>
            </div>

            <!-- Bottom Proof & Trust Bar -->
            <div class="relative z-10 pt-4 mt-auto border-t border-slate-800/80">
                <div class="grid grid-cols-3 gap-3 text-center">
                    <div>
                        <div class="text-base xl:text-lg font-black text-white tracking-tight font-satoshi"><?= htmlspecialchars($cur['stat1_num']) ?></div>
                        <div class="text-[9px] uppercase font-bold text-slate-400 tracking-wider"><?= htmlspecialchars($cur['stat1_label']) ?></div>
                    </div>
                    <div class="border-x border-slate-800">
                        <div class="text-base xl:text-lg font-black text-white tracking-tight font-satoshi"><?= htmlspecialchars($cur['stat2_num']) ?></div>
                        <div class="text-[9px] uppercase font-bold text-slate-400 tracking-wider"><?= htmlspecialchars($cur['stat2_label']) ?></div>
                    </div>
                    <div>
                        <div class="text-base xl:text-lg font-black text-white tracking-tight font-satoshi"><?= htmlspecialchars($cur['stat3_num']) ?></div>
                        <div class="text-[9px] uppercase font-bold text-slate-400 tracking-wider"><?= htmlspecialchars($cur['stat3_label']) ?></div>
                    </div>
                </div>

                <div class="mt-3 flex items-center justify-center space-x-1.5 text-[9.5px] text-slate-400">
                    <i data-lucide="shield-check" class="w-3.5 h-3.5 text-emerald-400"></i>
                    <span>SEBI & MCA Compliant Governance • 256-Bit SSL Protection</span>
                </div>
            </div>

        </div>


        <!-- ========================================================
             RIGHT SIDE: PURE ROLE REGISTRATION FORM (NO TABS)
             ======================================================== -->
        <div class="lg:w-1/2 xl:w-5/12 bg-white flex flex-col justify-between p-6 sm:p-8 xl:p-12 overflow-y-auto custom-scroll relative" id="right-column">
            
            <!-- Mobile Header with Brand & Sign In Link -->
            <div class="flex items-center justify-between pb-4 mb-2 border-b border-slate-100 lg:border-none lg:pb-0">
                <div class="flex items-center space-x-2 lg:hidden">
                    <span class="text-xl font-black tracking-tight text-slate-900 font-satoshi flex items-center gap-1">
                        NEXORA
                        <span class="w-2 h-2 rounded-full bg-blue-600"></span>
                    </span>
                    <span class="text-xs text-slate-400 font-bold uppercase pl-2 border-l border-slate-200"><?= ucfirst($selectedRole) ?></span>
                </div>
                
                <div class="text-right ml-auto flex items-center gap-2">
                    <span class="text-xs sm:text-sm text-slate-500 hidden sm:inline">Already have an account?</span>
                    <a href="<?= url('auth/login.php') ?>" class="text-xs sm:text-sm font-bold text-blue-600 hover:text-blue-800 transition inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-blue-50 hover:bg-blue-100 border border-blue-200/70">
                        <span>Log In</span>
                        <i data-lucide="arrow-right" class="w-4 h-4"></i>
                    </a>
                </div>
            </div>

            <!-- Form Content Wrapper (Centered Vertically) -->
            <div class="max-w-md w-full mx-auto my-auto py-2">
                
                <!-- Role Choice Switcher Tabs (With Crisp SVG Icons & Bigger Text) -->
                <div class="mb-5 p-1 rounded-2xl bg-slate-100/90 border border-slate-200/90 grid grid-cols-2 gap-1.5 shadow-2xs">
                    <a href="<?= url('auth/register.php?role=founder') ?>" 
                       class="py-3 px-3.5 rounded-xl text-center text-xs sm:text-sm font-bold transition-all flex items-center justify-center gap-2 <?= $selectedRole === 'founder' ? 'bg-white text-blue-700 shadow-xs border border-slate-200/80' : 'text-slate-600 hover:text-slate-900' ?>">
                        <i data-lucide="rocket" class="w-4 h-4 <?= $selectedRole === 'founder' ? 'text-blue-600' : 'text-slate-400' ?>"></i>
                        <span>I am a Founder</span>
                    </a>
                    <a href="<?= url('auth/register.php?role=investor') ?>" 
                       class="py-3 px-3.5 rounded-xl text-center text-xs sm:text-sm font-bold transition-all flex items-center justify-center gap-2 <?= $selectedRole === 'investor' ? 'bg-white text-emerald-700 shadow-xs border border-slate-200/80' : 'text-slate-600 hover:text-slate-900' ?>">
                        <i data-lucide="briefcase" class="w-4 h-4 <?= $selectedRole === 'investor' ? 'text-emerald-600' : 'text-slate-400' ?>"></i>
                        <span>I am an Investor</span>
                    </a>
                </div>

                <!-- Main Header -->
                <div class="mb-5">
                    <div class="inline-flex items-center space-x-1.5 px-3 py-1 rounded-lg text-xs font-bold mb-2 <?= $cur['badge_color'] ?>">
                        <span class="w-2 h-2 rounded-full bg-blue-600 animate-pulse"></span>
                        <span><?= htmlspecialchars($cur['form_badge']) ?></span>
                    </div>
                    <h2 class="text-2xl sm:text-3xl lg:text-4xl font-black tracking-tight text-slate-900 font-satoshi">
                        <?= htmlspecialchars($cur['form_title']) ?>
                    </h2>
                    <p class="text-sm sm:text-base text-slate-600 mt-1.5 font-medium leading-relaxed">
                        <?= htmlspecialchars($cur['form_sub']) ?>
                    </p>
                </div>

                <!-- Error Notification -->
                <?php if (!empty($error)): ?>
                    <div class="mb-4 p-3.5 rounded-xl text-sm font-semibold bg-rose-50 text-rose-800 border border-rose-200 flex items-center space-x-2.5 shadow-sm">
                        <i data-lucide="alert-circle" class="w-5 h-5 flex-shrink-0 text-rose-600"></i>
                        <span><?= htmlspecialchars($error) ?></span>
                    </div>
                <?php endif; ?>

                <!-- Dedicated Registration Form -->
                <form action="<?= url('auth/register.php?role=' . urlencode($selectedRole)) ?>" method="POST" class="space-y-4" id="registrationForm">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <?= security_honeypot_field() ?>
                    <input type="hidden" name="role" value="<?= htmlspecialchars($selectedRole) ?>">

                    <!-- Shared: Full Legal Name -->
                    <div>
                        <label class="block text-xs sm:text-sm font-bold uppercase tracking-wider text-slate-800 mb-1.5">
                            Full Legal Name <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative input-glow rounded-xl">
                            <i data-lucide="user" class="w-5 h-5 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                            <input type="text" name="name" required placeholder="e.g. Aarav Sharma"
                                   value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                                   class="w-full pl-11 pr-4 py-3 bg-slate-50 hover:bg-slate-50/80 border border-slate-200 focus:bg-white focus:border-blue-600 focus:ring-2 focus:ring-blue-600/10 rounded-xl text-sm sm:text-base text-slate-900 placeholder-slate-400 outline-none transition font-medium">
                        </div>
                    </div>

                    <!-- Shared: Email & Phone (Grid) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                        <div>
                            <label class="block text-xs sm:text-sm font-bold uppercase tracking-wider text-slate-800 mb-1.5">
                                Work Email <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative input-glow rounded-xl">
                                <i data-lucide="mail" class="w-5 h-5 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                                <input type="email" name="email" required placeholder="name@company.com"
                                       value="<?= htmlspecialchars($_POST['email'] ?? $_GET['email'] ?? '') ?>"
                                       class="w-full pl-11 pr-3.5 py-3 bg-slate-50 hover:bg-slate-50/80 border border-slate-200 focus:bg-white focus:border-blue-600 focus:ring-2 focus:ring-blue-600/10 rounded-xl text-sm sm:text-base text-slate-900 placeholder-slate-400 outline-none transition font-medium">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs sm:text-sm font-bold uppercase tracking-wider text-slate-800 mb-1.5">
                                Phone Number
                            </label>
                            <div class="relative input-glow rounded-xl">
                                <i data-lucide="phone" class="w-5 h-5 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                                <input type="tel" name="phone" placeholder="+91 98765 43210"
                                       value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
                                       class="w-full pl-11 pr-3.5 py-3 bg-slate-50 hover:bg-slate-50/80 border border-slate-200 focus:bg-white focus:border-blue-600 focus:ring-2 focus:ring-blue-600/10 rounded-xl text-sm sm:text-base text-slate-900 placeholder-slate-400 outline-none transition font-medium">
                            </div>
                        </div>
                    </div>

                    <?php if ($selectedRole === 'founder'): ?>
                        <!-- ==============================================
                             FOUNDER-ONLY FIELDS
                             ============================================== -->
                        <div class="space-y-3.5 p-4 rounded-2xl bg-blue-50/40 border border-blue-100">
                            <div class="flex items-center space-x-2 text-blue-700 font-bold text-xs sm:text-sm uppercase tracking-wider font-satoshi">
                                <i data-lucide="rocket" class="w-4 h-4 text-blue-600"></i>
                                <span>Startup &amp; Venture Profile</span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-800 mb-1.5">
                                        Startup / Company Name <span class="text-rose-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <i data-lucide="building" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                                        <input type="text" name="company_name" required placeholder="e.g. TechPulse AI"
                                               value="<?= htmlspecialchars($_POST['company_name'] ?? '') ?>"
                                               class="w-full pl-9 pr-3 py-2.5 bg-white border border-slate-200 focus:border-blue-600 focus:ring-2 focus:ring-blue-600/10 rounded-xl text-xs sm:text-sm text-slate-900 placeholder-slate-400 outline-none transition font-medium">
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-800 mb-1.5">
                                        Primary Sector
                                    </label>
                                    <select name="industry" class="w-full px-3 py-2.5 bg-white border border-slate-200 focus:border-blue-600 focus:ring-2 focus:ring-blue-600/10 rounded-xl text-xs sm:text-sm text-slate-900 outline-none transition font-medium">
                                        <option value="AI/SaaS" <?= ($_POST['industry'] ?? '') === 'AI/SaaS' ? 'selected' : '' ?>>AI & Enterprise SaaS</option>
                                        <option value="FinTech" <?= ($_POST['industry'] ?? '') === 'FinTech' ? 'selected' : '' ?>>FinTech & Web3</option>
                                        <option value="HealthTech" <?= ($_POST['industry'] ?? '') === 'HealthTech' ? 'selected' : '' ?>>HealthTech & Bio</option>
                                        <option value="CleanTech" <?= ($_POST['industry'] ?? '') === 'CleanTech' ? 'selected' : '' ?>>CleanTech & Climate</option>
                                        <option value="Consumer/D2C" <?= ($_POST['industry'] ?? '') === 'Consumer/D2C' ? 'selected' : '' ?>>Consumer & D2C</option>
                                        <option value="DeepTech" <?= ($_POST['industry'] ?? '') === 'DeepTech' ? 'selected' : '' ?>>Robotics & DeepTech</option>
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-800 mb-1.5">
                                        Current Venture Stage
                                    </label>
                                    <select name="stage" class="w-full px-3 py-2.5 bg-white border border-slate-200 focus:border-blue-600 focus:ring-2 focus:ring-blue-600/10 rounded-xl text-xs sm:text-sm text-slate-900 outline-none transition font-medium">
                                        <option value="Seed" <?= ($_POST['stage'] ?? '') === 'Seed' ? 'selected' : '' ?>>Seed Round (Raising ₹1 Cr - ₹5 Cr)</option>
                                        <option value="Pre-Seed" <?= ($_POST['stage'] ?? '') === 'Pre-Seed' ? 'selected' : '' ?>>Pre-Seed / Prototype (₹25L - ₹1 Cr)</option>
                                        <option value="Pre-Series A" <?= ($_POST['stage'] ?? '') === 'Pre-Series A' ? 'selected' : '' ?>>Pre-Series A (₹5 Cr - ₹15 Cr)</option>
                                        <option value="Series A+" <?= ($_POST['stage'] ?? '') === 'Series A+' ? 'selected' : '' ?>>Series A+ (₹15 Cr+)</option>
                                        <option value="Idea" <?= ($_POST['stage'] ?? '') === 'Idea' ? 'selected' : '' ?>>Concept / Early Validation</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-800 mb-1.5">
                                        Your Designation
                                    </label>
                                    <input type="text" name="designation" value="<?= htmlspecialchars($_POST['designation'] ?? 'Founder & CEO') ?>" placeholder="e.g. Founder & CEO"
                                           class="w-full px-3 py-2.5 bg-white border border-slate-200 focus:border-blue-600 focus:ring-2 focus:ring-blue-600/10 rounded-xl text-xs sm:text-sm text-slate-900 outline-none transition font-medium">
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-800 mb-1.5">
                                    One-Line Startup Elevator Pitch
                                </label>
                                <input type="text" name="pitch" placeholder="e.g. Autonomous AI workflow agent optimizing enterprise logistics."
                                       value="<?= htmlspecialchars($_POST['pitch'] ?? '') ?>"
                                       class="w-full px-3 py-2.5 bg-white border border-slate-200 focus:border-blue-600 focus:ring-2 focus:ring-blue-600/10 rounded-xl text-xs sm:text-sm text-slate-900 placeholder-slate-400 outline-none transition font-medium">
                            </div>
                        </div>

                    <?php else: ?>
                        <!-- ==============================================
                             INVESTOR-ONLY FIELDS
                             ============================================== -->
                        <div class="space-y-3.5 p-4 rounded-2xl bg-emerald-50/40 border border-emerald-100">
                            <div class="flex items-center space-x-2 text-emerald-700 font-bold text-xs sm:text-sm uppercase tracking-wider font-satoshi">
                                <i data-lucide="wallet" class="w-4 h-4 text-emerald-600"></i>
                                <span>Investor Profile &amp; Mandate</span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-800 mb-1.5">
                                        Investor Type
                                    </label>
                                    <select name="investor_type" class="w-full px-3 py-2.5 bg-white border border-slate-200 focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/10 rounded-xl text-xs sm:text-sm text-slate-900 outline-none transition font-medium">
                                        <option value="Angel Investor" <?= ($_POST['investor_type'] ?? '') === 'Angel Investor' ? 'selected' : '' ?>>Individual Angel Investor</option>
                                        <option value="Syndicate Lead" <?= ($_POST['investor_type'] ?? '') === 'Syndicate Lead' ? 'selected' : '' ?>>Angel Syndicate Lead</option>
                                        <option value="Venture Capital Partner" <?= ($_POST['investor_type'] ?? '') === 'Venture Capital Partner' ? 'selected' : '' ?>>Institutional VC Partner</option>
                                        <option value="Family Office" <?= ($_POST['investor_type'] ?? '') === 'Family Office' ? 'selected' : '' ?>>Family Office Principal</option>
                                        <option value="Corporate VC" <?= ($_POST['investor_type'] ?? '') === 'Corporate VC' ? 'selected' : '' ?>>Corporate Venture Capital (CVC)</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-800 mb-1.5">
                                        Typical Cheque / Ticket Size
                                    </label>
                                    <select name="ticket_range" class="w-full px-3 py-2.5 bg-white border border-slate-200 focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/10 rounded-xl text-xs sm:text-sm text-slate-900 outline-none transition font-medium">
                                        <option value="2-10L" <?= ($_POST['ticket_range'] ?? '') === '2-10L' ? 'selected' : '' ?>>₹2 Lakhs – ₹10 Lakhs</option>
                                        <option value="10-50L" <?= ($_POST['ticket_range'] ?? '10-50L') === '10-50L' ? 'selected' : '' ?>>₹10 Lakhs – ₹50 Lakhs (Popular)</option>
                                        <option value="50L-2Cr" <?= ($_POST['ticket_range'] ?? '') === '50L-2Cr' ? 'selected' : '' ?>>₹50 Lakhs – ₹2 Crores</option>
                                        <option value="2Cr+" <?= ($_POST['ticket_range'] ?? '') === '2Cr+' ? 'selected' : '' ?>>₹2 Crores+ (Institutional)</option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-800 mb-1.5">
                                    Primary Investment Focus Sectors
                                </label>
                                <input type="text" name="preferred_industries" 
                                       value="<?= htmlspecialchars($_POST['preferred_industries'] ?? 'AI & DeepTech, SaaS, FinTech, HealthTech') ?>" 
                                       placeholder="e.g. AI & DeepTech, SaaS, FinTech"
                                       class="w-full px-3 py-2.5 bg-white border border-slate-200 focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/10 rounded-xl text-xs sm:text-sm text-slate-900 outline-none transition font-medium">
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Shared: Password & Confirmation (Grid) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                        <div>
                            <label class="block text-xs sm:text-sm font-bold uppercase tracking-wider text-slate-800 mb-1.5">
                                Password <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative input-glow rounded-xl">
                                <i data-lucide="lock" class="w-5 h-5 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                                <input type="password" id="password" name="password" required placeholder="Min 6 characters"
                                       oninput="checkPasswordStrength(this.value)" autocomplete="new-password"
                                       class="w-full pl-11 pr-11 py-3 bg-slate-50 hover:bg-slate-50/80 border border-slate-200 focus:bg-white focus:border-blue-600 focus:ring-2 focus:ring-blue-600/10 rounded-xl text-sm sm:text-base text-slate-900 placeholder-slate-400 outline-none transition font-medium">
                                <button type="button" onclick="togglePasswordVisibility('password', 'pwd-icon-1')" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 p-1.5 rounded-lg transition" title="Toggle password visibility">
                                    <i data-lucide="eye" id="pwd-icon-1" class="w-5 h-5"></i>
                                </button>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs sm:text-sm font-bold uppercase tracking-wider text-slate-800 mb-1.5">
                                Confirm Password <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative input-glow rounded-xl">
                                <i data-lucide="lock" class="w-5 h-5 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                                <input type="password" id="password_confirmation" name="password_confirmation" required placeholder="Repeat password"
                                       autocomplete="new-password"
                                       class="w-full pl-11 pr-11 py-3 bg-slate-50 hover:bg-slate-50/80 border border-slate-200 focus:bg-white focus:border-blue-600 focus:ring-2 focus:ring-blue-600/10 rounded-xl text-sm sm:text-base text-slate-900 placeholder-slate-400 outline-none transition font-medium">
                                <button type="button" onclick="togglePasswordVisibility('password_confirmation', 'pwd-icon-2')" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 p-1.5 rounded-lg transition" title="Toggle password visibility">
                                    <i data-lucide="eye" id="pwd-icon-2" class="w-5 h-5"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Password Strength Meter -->
                    <div class="space-y-1.5" id="strength-container" style="display: none;">
                        <div class="flex items-center justify-between text-xs text-slate-500">
                            <span>Password strength:</span>
                            <span id="strength-label" class="font-bold text-slate-700">Weak</span>
                        </div>
                        <div class="h-2 w-full bg-slate-100 rounded-full overflow-hidden flex">
                            <div id="strength-bar" class="h-full bg-rose-500 transition-all duration-300 w-1/4"></div>
                        </div>
                    </div>

                    <!-- Shared: City & Country (Grid) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                        <div>
                            <label class="block text-xs sm:text-sm font-bold uppercase tracking-wider text-slate-800 mb-1.5">
                                City
                            </label>
                            <div class="relative input-glow rounded-xl">
                                <i data-lucide="map-pin" class="w-5 h-5 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                                <input type="text" name="city" value="<?= htmlspecialchars($_POST['city'] ?? 'Bengaluru') ?>" required
                                       class="w-full pl-11 pr-3.5 py-2.5 sm:py-3 bg-slate-50 hover:bg-slate-50/80 border border-slate-200 focus:bg-white focus:border-blue-600 focus:ring-2 focus:ring-blue-600/10 rounded-xl text-sm sm:text-base text-slate-900 outline-none transition font-medium">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs sm:text-sm font-bold uppercase tracking-wider text-slate-800 mb-1.5">
                                Country
                            </label>
                            <div class="relative input-glow rounded-xl">
                                <i data-lucide="globe" class="w-5 h-5 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                                <input type="text" name="country" value="<?= htmlspecialchars($_POST['country'] ?? 'India') ?>" required
                                       class="w-full pl-11 pr-3.5 py-2.5 sm:py-3 bg-slate-50 hover:bg-slate-50/80 border border-slate-200 focus:bg-white focus:border-blue-600 focus:ring-2 focus:ring-blue-600/10 rounded-xl text-sm sm:text-base text-slate-900 outline-none transition font-medium">
                            </div>
                        </div>
                    </div>

                    <!-- Terms & Conditions Checkbox -->
                    <div class="pt-1">
                        <label class="flex items-start space-x-2.5 cursor-pointer group">
                            <input type="checkbox" name="terms" required <?= isset($_POST['terms']) ? 'checked' : '' ?>
                                   class="mt-1 w-4 h-4 text-blue-600 rounded border-slate-300 focus:ring-blue-500 cursor-pointer">
                            <span class="text-xs sm:text-sm text-slate-600 leading-relaxed group-hover:text-slate-800 transition">
                                I agree to the <a href="<?= url('legal/terms.php') ?>" target="_blank" class="text-blue-600 hover:text-blue-800 font-bold underline decoration-blue-200">Terms of Service</a>, <a href="<?= url('legal/privacy.php') ?>" target="_blank" class="text-blue-600 hover:text-blue-800 font-bold underline decoration-blue-200">Privacy Policy</a>, and understand private venture disclosures.
                            </span>
                        </label>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-2">
                        <button type="submit" id="submitBtn"
                                class="w-full py-3.5 px-6 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 active:scale-[0.99] text-white text-sm sm:text-base font-black rounded-xl shadow-lg shadow-blue-600/25 hover:shadow-blue-600/35 transition-all duration-200 flex items-center justify-center space-x-2 group font-satoshi tracking-wide">
                            <span id="btn-submit-text"><?= htmlspecialchars($cur['btn_text']) ?></span>
                            <i data-lucide="arrow-right" class="w-5 h-5 group-hover:translate-x-1 transition-transform"></i>
                        </button>
                    </div>
                </form>

                <!-- Subtle Switch Link at Bottom -->
                <div class="mt-5 text-center">
                    <a href="<?= $cur['switch_url'] ?>" class="text-xs sm:text-sm text-slate-500 hover:text-slate-800 transition underline font-medium">
                        <?= htmlspecialchars($cur['switch_text']) ?> &rarr;
                    </a>
                </div>

                <!-- Security Guarantee Pill -->
                <div class="mt-4 flex flex-wrap items-center justify-center gap-y-1.5 gap-x-3 text-[10.5px] text-slate-400">
                    <span class="flex items-center space-x-1">
                        <i data-lucide="lock" class="w-3 h-3 text-slate-400"></i>
                        <span>256-bit SSL</span>
                    </span>
                    <span class="text-slate-300">•</span>
                    <span class="flex items-center space-x-1">
                        <i data-lucide="check-circle-2" class="w-3 h-3 text-emerald-500"></i>
                        <span>DigiLocker KYC</span>
                    </span>
                    <span class="text-slate-300">•</span>
                    <span class="flex items-center space-x-1">
                        <i data-lucide="shield" class="w-3 h-3 text-blue-500"></i>
                        <span>SEBI & MCA Compliant</span>
                    </span>
                </div>

            </div>

            <!-- Bottom Copyright -->
            <div class="pt-4 text-center text-[10.5px] text-slate-400 border-t border-slate-100">
                &copy; <?= date('Y') ?> <?= APP_NAME ?>. All rights reserved.
            </div>

        </div>

    </div>

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
                btnText.textContent = 'Submitting Application...';
            }
        });
    </script>
</body>
</html>
