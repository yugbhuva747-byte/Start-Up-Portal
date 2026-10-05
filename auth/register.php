<?php
/**
 * Authentication: Multi-Role Registration (Founder & Investor)
 * Split-Screen Modern Redesign
 * Left Side: Dynamic Visual Showcase, Hero Graphics & Role-Tailored Highlights
 * Right Side: Interactive Role Selector with Customized Dynamic Forms for Founders & Investors
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
$selectedRole = strtolower($_GET['role'] ?? 'founder');
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
        $role = strtolower(trim($_POST['role'] ?? 'founder'));
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

                        // Create Company Record (guaranteed creation with fallback)
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

                    // Activate Free Trial plan subscription
                    if (function_exists('activate_user_subscription')) {
                        activate_user_subscription($userId, 'free_trial', 'monthly');
                    }

                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $userId;
                    $_SESSION['user_role'] = $role;
                    $_SESSION['user_name'] = $name;

                    // Register active login session
                    $sessionToken = bin2hex(random_bytes(32));
                    $_SESSION['session_token'] = $sessionToken;
                    $clientIp = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
                    $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 255);
                    $db->prepare("INSERT INTO login_sessions (user_id, session_token, ip_address, user_agent, created_at, last_active_at) VALUES (?, ?, ?, ?, NOW(), NOW())")->execute([$userId, $sessionToken, $clientIp, $ua]);

                    // Set persistent 30-day cookie session
                    if (function_exists('set_remember_me_cookie')) {
                        set_remember_me_cookie($userId, 30);
                    }

                    log_audit($userId, 'USER_REGISTERED', 'users', $userId, "User registered as {$role}");
                    set_flash('success', "Welcome to the portal, " . htmlspecialchars($name) . "! Your " . ucfirst($role) . " account is ready.");
                    
                    $redirect = $role === 'investor' ? 'investor/dashboard.php' : 'founder/dashboard.php';
                    header('Location: ' . url($redirect));
                    exit;
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
    <title>Create Your Account • <?= APP_NAME ?></title>
    <meta name="description" content="Register as a Startup Founder or Accredited Investor. Access curated deal rooms, angel syndicates, and automated cap tables.">
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
             LEFT SIDE: DYNAMIC INFORMATION & VISUAL SHOWCASE
             ======================================================== -->
        <div class="lg:w-1/2 xl:w-7/12 mesh-bg text-white p-6 sm:p-8 xl:p-10 2xl:p-12 flex flex-col justify-between relative overflow-y-auto custom-scroll border-b lg:border-b-0 lg:border-r border-slate-800" id="left-column">
            
            <!-- Background Ambient Dot Grid -->
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
                            <span class="block text-[8.5px] tracking-widest text-slate-400 uppercase font-bold">Venture Network</span>
                        </div>
                    </a>

                    <a href="<?= url('index.php') ?>" class="hidden sm:inline-flex items-center text-xs font-semibold text-slate-300 hover:text-white transition group space-x-1.5 px-3 py-1.5 rounded-lg glass-panel hover:bg-white/10">
                        <i data-lucide="arrow-left" class="w-3.5 h-3.5 group-hover:-translate-x-0.5 transition-transform"></i>
                        <span>Back to Home</span>
                    </a>
                </div>

                <!-- Dynamic Role Category Tag -->
                <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full glass-panel text-[11px] font-semibold text-indigo-300 mb-3 border border-indigo-500/30" id="info-badge">
                    <span class="w-1.5 h-1.5 rounded-full bg-indigo-400 animate-pulse"></span>
                    <span id="badge-text"><?= $selectedRole === 'investor' ? 'ACCREDITED CAPITAL PIPELINE' : 'FOUNDER CAPITAL ACCELERATOR' ?></span>
                </div>

                <!-- Big Dynamic Headline -->
                <h1 class="text-2xl sm:text-3xl xl:text-4xl font-black text-white tracking-tight leading-snug mb-2" id="info-title">
                    <?= $selectedRole === 'investor' 
                        ? 'Discover high-conviction startups vetted for rapid scale.' 
                        : 'Connect directly with verified angel syndicates and institutional capital.' ?>
                </h1>

                <!-- Subtitle -->
                <p class="text-slate-300 text-xs sm:text-sm leading-relaxed mb-4 max-w-xl" id="info-subtitle">
                    <?= $selectedRole === 'investor'
                        ? 'Access institutional-grade private deals with audited cap tables, verified traction metrics, and seamless co-investment syndication.'
                        : 'Raise your Seed or Pre-Series A with zero broker fees. Share your confidential data room with accredited investors ready to deploy.' ?>
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
                        <span id="img-badge-1"><?= $selectedRole === 'investor' ? 'Curated Deal Flow Pipeline' : 'Live Pitch Deck Active' ?></span>
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
                            <span class="text-[11px] font-medium text-slate-200" id="img-subtext">
                                <?= $selectedRole === 'investor' ? '1,400+ Vetted Tech Startups' : '500+ Active Syndicate Leads' ?>
                            </span>
                        </div>
                        <div>
                            <span class="px-2 py-0.5 rounded-md bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 text-[10.5px] font-bold flex items-center gap-1">
                                <i data-lucide="trending-up" class="w-3 h-3"></i>
                                <span id="img-metric"><?= $selectedRole === 'investor' ? '18.4% Average IRR' : '₹185 Cr+ Raised' ?></span>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- ==============================================
                     DYNAMIC 3 PILLARS / HIGHLIGHTS
                     ============================================== -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 mb-4" id="info-features">
                    <!-- Pillar 1 -->
                    <div class="p-2.5 rounded-xl glass-panel glass-panel-hover transition">
                        <div class="flex items-center space-x-2 mb-1">
                            <div class="w-6 h-6 rounded-lg bg-indigo-500/20 border border-indigo-500/30 flex items-center justify-center text-indigo-400 flex-shrink-0" id="feat-icon-1">
                                <i data-lucide="<?= $selectedRole === 'investor' ? 'layers' : 'rocket' ?>" class="w-3.5 h-3.5"></i>
                            </div>
                            <h3 class="text-[11px] font-bold text-white tracking-wide uppercase" id="feat-title-1">
                                <?= $selectedRole === 'investor' ? 'Pre-Vetted Deal Flow' : 'Direct Angel & VC Access' ?>
                            </h3>
                        </div>
                        <p class="text-[10px] text-slate-300 leading-snug" id="feat-desc-1">
                            <?= $selectedRole === 'investor' 
                                ? 'Filter seed rounds by sector, ARR, and valuation with verified founder identity verification.' 
                                : 'Pitch directly to 500+ active angel investors and syndicates without cold email fatigue.' ?>
                        </p>
                    </div>

                    <!-- Pillar 2 -->
                    <div class="p-2.5 rounded-xl glass-panel glass-panel-hover transition">
                        <div class="flex items-center space-x-2 mb-1">
                            <div class="w-6 h-6 rounded-lg bg-violet-500/20 border border-violet-500/30 flex items-center justify-center text-violet-400 flex-shrink-0" id="feat-icon-2">
                                <i data-lucide="<?= $selectedRole === 'investor' ? 'shield-check' : 'file-lock-2' ?>" class="w-3.5 h-3.5"></i>
                            </div>
                            <h3 class="text-[11px] font-bold text-white tracking-wide uppercase" id="feat-title-2">
                                <?= $selectedRole === 'investor' ? 'Secure Due Diligence' : 'Confidential Rooms' ?>
                            </h3>
                        </div>
                        <p class="text-[10px] text-slate-300 leading-snug" id="feat-desc-2">
                            <?= $selectedRole === 'investor' 
                                ? 'Instant access to verified pitch decks, financial models, cap tables, and compliance records.' 
                                : 'Control who views your deck, monitor viewer analytics, and protect proprietary IP with automated NDAs.' ?>
                        </p>
                    </div>

                    <!-- Pillar 3 -->
                    <div class="p-2.5 rounded-xl glass-panel glass-panel-hover transition">
                        <div class="flex items-center space-x-2 mb-1">
                            <div class="w-6 h-6 rounded-lg bg-emerald-500/20 border border-emerald-500/30 flex items-center justify-center text-emerald-400 flex-shrink-0" id="feat-icon-3">
                                <i data-lucide="<?= $selectedRole === 'investor' ? 'trending-up' : 'banknote' ?>" class="w-3.5 h-3.5"></i>
                            </div>
                            <h3 class="text-[11px] font-bold text-white tracking-wide uppercase" id="feat-title-3">
                                <?= $selectedRole === 'investor' ? 'Syndication & IRR' : 'Standardized Terms' ?>
                            </h3>
                        </div>
                        <p class="text-[10px] text-slate-300 leading-snug" id="feat-desc-3">
                            <?= $selectedRole === 'investor' 
                                ? 'Co-invest starting from ₹2 Lakhs, receive digital share certificates, and track portfolio returns.' 
                                : 'Close rounds faster with standardized SAFE notes, convertible debentures, and milestone escrow.' ?>
                        </p>
                    </div>
                </div>

                <!-- Testimonial / Social Proof Snippet -->
                <div class="p-3 rounded-xl glass-panel border border-indigo-500/20 mb-4" id="testimonial-card">
                    <div class="flex items-center justify-between mb-1">
                        <div class="flex items-center space-x-2.5">
                            <div class="w-6 h-6 rounded-full bg-gradient-to-tr from-indigo-500 to-pink-500 flex items-center justify-center font-bold text-[10px] text-white" id="test-avatar">
                                <?= $selectedRole === 'investor' ? 'VS' : 'AS' ?>
                            </div>
                            <div>
                                <span class="text-[11px] font-bold text-white" id="test-name">
                                    <?= $selectedRole === 'investor' ? 'Vikram Singhania' : 'Aarav Sharma' ?>
                                </span>
                                <span class="text-[9.5px] text-slate-400 ml-1" id="test-role">
                                    <?= $selectedRole === 'investor' ? '• Syndicate Lead (32 Deals Closed)' : '• CEO, TechPulse AI (Raised ₹4.5 Cr)' ?>
                                </span>
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
                    <p class="text-[10.5px] text-slate-300 italic leading-snug" id="test-quote">
                        <?= $selectedRole === 'investor'
                            ? '"The diligence depth, verified MCA records, and clean cap table documentation makes this our primary early-stage syndication portal."'
                            : '"Closing our seed round in 3 weeks was only possible because of verified angel matching. Total game changer."' ?>
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
             RIGHT SIDE: ROLE SELECTOR & REGISTRATION FORM
             ======================================================== -->
        <div class="lg:w-1/2 xl:w-5/12 bg-white flex flex-col justify-between p-6 sm:p-8 xl:p-12 overflow-y-auto custom-scroll relative" id="right-column">
            
            <!-- Mobile Header with Brand & Sign In Link -->
            <div class="flex items-center justify-between pb-4 mb-2 border-b border-slate-100 lg:border-none lg:pb-0">
                <div class="flex items-center space-x-2.5 lg:hidden">
                    <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center text-white">
                        <i data-lucide="zap" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <span class="text-xs font-extrabold text-slate-900 tracking-tight">STARTUP <span class="text-indigo-600">×</span> INVESTOR</span>
                        <span class="block text-[8px] text-slate-400 uppercase font-semibold">Sign Up</span>
                    </div>
                </div>
                
                <div class="text-right ml-auto">
                    <span class="text-xs text-slate-500 hidden sm:inline">Already have an account?</span>
                    <a href="<?= url('auth/login.php') ?>" class="sm:ml-1 text-xs font-bold text-indigo-600 hover:text-indigo-800 transition inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100">
                        <span>Sign In</span>
                        <i data-lucide="arrow-right" class="w-3 h-3"></i>
                    </a>
                </div>
            </div>

            <!-- Form Content Wrapper (Centered Vertically) -->
            <div class="max-w-md w-full mx-auto my-auto py-2">
                
                <!-- Main Header -->
                <div class="mb-5">
                    <div class="inline-flex items-center space-x-1.5 px-2.5 py-0.5 rounded-md bg-indigo-50 border border-indigo-100 text-indigo-700 text-[10.5px] font-bold mb-1.5">
                        <i data-lucide="sparkles" class="w-3 h-3 text-indigo-600"></i>
                        <span>Start Your Venture Journey</span>
                    </div>
                    <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900">
                        Create Your Account
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1">
                        Select your portal role to open your customized registration form.
                    </p>
                </div>

                <!-- ==============================================
                     INTERACTIVE ROLE SWITCHER TABS
                     ============================================== -->
                <div class="mb-5">
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                        Select Portal Account Type *
                    </label>
                    <div class="grid grid-cols-2 gap-2 p-1 bg-slate-100 rounded-2xl border border-slate-200">
                        <!-- Founder Tab Button -->
                        <button type="button" 
                                onclick="selectRole('founder')" 
                                id="role-btn-founder"
                                class="py-2.5 px-3 rounded-xl text-xs font-bold flex items-center justify-center space-x-2 transition-all duration-200 <?= $selectedRole === 'founder' ? 'bg-white text-indigo-600 shadow-sm ring-1 ring-slate-200' : 'text-slate-500 hover:text-slate-800 hover:bg-white/60' ?>">
                            <div class="w-6 h-6 rounded-lg <?= $selectedRole === 'founder' ? 'bg-indigo-50 text-indigo-600' : 'bg-slate-200/70 text-slate-500' ?> flex items-center justify-center flex-shrink-0 transition-colors">
                                <i data-lucide="rocket" class="w-3.5 h-3.5"></i>
                            </div>
                            <div class="text-left">
                                <div class="font-bold leading-tight">Startup Founder</div>
                                <div class="text-[9px] font-medium opacity-75">Raising Capital</div>
                            </div>
                        </button>

                        <!-- Investor Tab Button -->
                        <button type="button" 
                                onclick="selectRole('investor')" 
                                id="role-btn-investor"
                                class="py-2.5 px-3 rounded-xl text-xs font-bold flex items-center justify-center space-x-2 transition-all duration-200 <?= $selectedRole === 'investor' ? 'bg-white text-indigo-600 shadow-sm ring-1 ring-slate-200' : 'text-slate-500 hover:text-slate-800 hover:bg-white/60' ?>">
                            <div class="w-6 h-6 rounded-lg <?= $selectedRole === 'investor' ? 'bg-indigo-50 text-indigo-600' : 'bg-slate-200/70 text-slate-500' ?> flex items-center justify-center flex-shrink-0 transition-colors">
                                <i data-lucide="trending-up" class="w-3.5 h-3.5"></i>
                            </div>
                            <div class="text-left">
                                <div class="font-bold leading-tight">Angel / Investor</div>
                                <div class="text-[9px] font-medium opacity-75">Deploying Capital</div>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Error Notification -->
                <?php if (!empty($error)): ?>
                    <div class="mb-4 p-3 rounded-xl text-xs font-semibold bg-rose-50 text-rose-800 border border-rose-200 flex items-center space-x-2 shadow-sm">
                        <i data-lucide="alert-circle" class="w-4 h-4 flex-shrink-0 text-rose-600"></i>
                        <span><?= htmlspecialchars($error) ?></span>
                    </div>
                <?php endif; ?>

                <!-- ==============================================
                     THE REGISTRATION FORM
                     ============================================== -->
                <form action="<?= url('auth/register.php') ?>" method="POST" class="space-y-3.5" id="registrationForm">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <?= security_honeypot_field() ?>
                    <input type="hidden" name="role" id="form-role" value="<?= htmlspecialchars($selectedRole) ?>">

                    <!-- Shared: Full Legal Name -->
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Full Legal Name <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <i data-lucide="user" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                            <input type="text" name="name" required placeholder="e.g. Aarav Sharma"
                                   value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                                   class="w-full pl-10 pr-4 py-2.5 bg-slate-50 hover:bg-slate-50/80 border border-slate-200 focus:bg-white focus:border-indigo-600 focus:ring-4 focus:ring-indigo-600/10 rounded-xl text-xs sm:text-sm text-slate-900 placeholder-slate-400 outline-none transition font-medium">
                        </div>
                    </div>

                    <!-- Shared: Email & Phone (Grid) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 mb-1">
                                Work Email <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <i data-lucide="mail" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                                <input type="email" name="email" required placeholder="name@domain.com"
                                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                       class="w-full pl-10 pr-3.5 py-2.5 bg-slate-50 hover:bg-slate-50/80 border border-slate-200 focus:bg-white focus:border-indigo-600 focus:ring-4 focus:ring-indigo-600/10 rounded-xl text-xs sm:text-sm text-slate-900 placeholder-slate-400 outline-none transition font-medium">
                            </div>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 mb-1">
                                Phone Number
                            </label>
                            <div class="relative">
                                <i data-lucide="phone" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                                <input type="tel" name="phone" placeholder="+91 98765 43210"
                                       value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
                                       class="w-full pl-10 pr-3.5 py-2.5 bg-slate-50 hover:bg-slate-50/80 border border-slate-200 focus:bg-white focus:border-indigo-600 focus:ring-4 focus:ring-indigo-600/10 rounded-xl text-xs sm:text-sm text-slate-900 placeholder-slate-400 outline-none transition font-medium">
                            </div>
                        </div>
                    </div>

                    <!-- ==============================================
                         FOUNDER-SPECIFIC FORM FIELDS
                         ============================================== -->
                    <div id="founder-fields" class="space-y-3.5 <?= $selectedRole === 'investor' ? 'hidden' : '' ?> p-3.5 rounded-2xl bg-indigo-50/40 border border-indigo-100/70">
                        <div class="flex items-center space-x-1.5 text-indigo-700 font-bold text-[10.5px] uppercase tracking-wider">
                            <i data-lucide="building" class="w-3.5 h-3.5"></i>
                            <span>Startup & Company Profile</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-700 mb-1">
                                    Startup / Company Name <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <i data-lucide="briefcase" class="w-3.5 h-3.5 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                                    <input type="text" name="company_name" id="founder-company" placeholder="e.g. TechPulse AI"
                                           value="<?= htmlspecialchars($_POST['company_name'] ?? '') ?>"
                                           class="w-full pl-9 pr-3 py-2 bg-white border border-slate-200 focus:border-indigo-600 focus:ring-2 focus:ring-indigo-600/10 rounded-xl text-xs text-slate-900 placeholder-slate-400 outline-none transition font-medium">
                                </div>
                            </div>

                            <div>
                                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-700 mb-1">
                                    Primary Sector / Industry
                                </label>
                                <select name="industry" class="w-full px-3 py-2 bg-white border border-slate-200 focus:border-indigo-600 focus:ring-2 focus:ring-indigo-600/10 rounded-xl text-xs text-slate-900 outline-none transition font-medium">
                                    <option value="AI/SaaS" <?= ($_POST['industry'] ?? '') === 'AI/SaaS' ? 'selected' : '' ?>>AI & Enterprise SaaS</option>
                                    <option value="FinTech" <?= ($_POST['industry'] ?? '') === 'FinTech' ? 'selected' : '' ?>>FinTech & Web3</option>
                                    <option value="HealthTech" <?= ($_POST['industry'] ?? '') === 'HealthTech' ? 'selected' : '' ?>>HealthTech & Bio</option>
                                    <option value="CleanTech" <?= ($_POST['industry'] ?? '') === 'CleanTech' ? 'selected' : '' ?>>CleanTech & Climate</option>
                                    <option value="Consumer/D2C" <?= ($_POST['industry'] ?? '') === 'Consumer/D2C' ? 'selected' : '' ?>>Consumer & D2C</option>
                                    <option value="DeepTech" <?= ($_POST['industry'] ?? '') === 'DeepTech' ? 'selected' : '' ?>>Robotics & DeepTech</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-700 mb-1">
                                    Current Venture Stage
                                </label>
                                <select name="stage" class="w-full px-3 py-2 bg-white border border-slate-200 focus:border-indigo-600 focus:ring-2 focus:ring-indigo-600/10 rounded-xl text-xs text-slate-900 outline-none transition font-medium">
                                    <option value="Seed" <?= ($_POST['stage'] ?? '') === 'Seed' ? 'selected' : '' ?>>Seed Round (Raising ₹1 Cr - ₹5 Cr)</option>
                                    <option value="Pre-Seed" <?= ($_POST['stage'] ?? '') === 'Pre-Seed' ? 'selected' : '' ?>>Pre-Seed / Prototype (₹25L - ₹1 Cr)</option>
                                    <option value="Pre-Series A" <?= ($_POST['stage'] ?? '') === 'Pre-Series A' ? 'selected' : '' ?>>Pre-Series A (₹5 Cr - ₹15 Cr)</option>
                                    <option value="Series A+" <?= ($_POST['stage'] ?? '') === 'Series A+' ? 'selected' : '' ?>>Series A+ (₹15 Cr+)</option>
                                    <option value="Idea" <?= ($_POST['stage'] ?? '') === 'Idea' ? 'selected' : '' ?>>Concept / Early Validation</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-700 mb-1">
                                    Your Designation
                                </label>
                                <input type="text" name="designation" value="<?= htmlspecialchars($_POST['designation'] ?? 'Founder & CEO') ?>" placeholder="e.g. Founder & CEO"
                                       class="w-full px-3 py-2 bg-white border border-slate-200 focus:border-indigo-600 focus:ring-2 focus:ring-indigo-600/10 rounded-xl text-xs text-slate-900 outline-none transition font-medium">
                            </div>
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-700 mb-1">
                                One-Line Startup Elevator Pitch
                            </label>
                            <input type="text" name="pitch" placeholder="e.g. AI-driven logistics intelligence optimizing last-mile delivery."
                                   value="<?= htmlspecialchars($_POST['pitch'] ?? '') ?>"
                                   class="w-full px-3 py-2 bg-white border border-slate-200 focus:border-indigo-600 focus:ring-2 focus:ring-indigo-600/10 rounded-xl text-xs text-slate-900 placeholder-slate-400 outline-none transition font-medium">
                        </div>
                    </div>

                    <!-- ==============================================
                         INVESTOR-SPECIFIC FORM FIELDS
                         ============================================== -->
                    <div id="investor-fields" class="space-y-3.5 <?= $selectedRole === 'founder' ? 'hidden' : '' ?> p-3.5 rounded-2xl bg-emerald-50/40 border border-emerald-100/70">
                        <div class="flex items-center space-x-1.5 text-emerald-700 font-bold text-[10.5px] uppercase tracking-wider">
                            <i data-lucide="wallet" class="w-3.5 h-3.5"></i>
                            <span>Investor Profile & Mandate</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-700 mb-1">
                                    Investor Type
                                </label>
                                <select name="investor_type" class="w-full px-3 py-2 bg-white border border-slate-200 focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/10 rounded-xl text-xs text-slate-900 outline-none transition font-medium">
                                    <option value="Angel Investor" <?= ($_POST['investor_type'] ?? '') === 'Angel Investor' ? 'selected' : '' ?>>Individual Angel Investor</option>
                                    <option value="Syndicate Lead" <?= ($_POST['investor_type'] ?? '') === 'Syndicate Lead' ? 'selected' : '' ?>>Angel Syndicate Lead</option>
                                    <option value="Venture Capital Partner" <?= ($_POST['investor_type'] ?? '') === 'Venture Capital Partner' ? 'selected' : '' ?>>Institutional VC Partner</option>
                                    <option value="Family Office" <?= ($_POST['investor_type'] ?? '') === 'Family Office' ? 'selected' : '' ?>>Family Office Principal</option>
                                    <option value="Corporate VC" <?= ($_POST['investor_type'] ?? '') === 'Corporate VC' ? 'selected' : '' ?>>Corporate Venture Capital (CVC)</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-700 mb-1">
                                    Typical Cheque / Ticket Size
                                </label>
                                <select name="ticket_range" class="w-full px-3 py-2 bg-white border border-slate-200 focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/10 rounded-xl text-xs text-slate-900 outline-none transition font-medium">
                                    <option value="2-10L" <?= ($_POST['ticket_range'] ?? '') === '2-10L' ? 'selected' : '' ?>>₹2 Lakhs – ₹10 Lakhs</option>
                                    <option value="10-50L" <?= ($_POST['ticket_range'] ?? '10-50L') === '10-50L' ? 'selected' : '' ?>>₹10 Lakhs – ₹50 Lakhs (Popular)</option>
                                    <option value="50L-2Cr" <?= ($_POST['ticket_range'] ?? '') === '50L-2Cr' ? 'selected' : '' ?>>₹50 Lakhs – ₹2 Crores</option>
                                    <option value="2Cr+" <?= ($_POST['ticket_range'] ?? '') === '2Cr+' ? 'selected' : '' ?>>₹2 Crores+ (Institutional)</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-700 mb-1">
                                Primary Investment Focus Sectors
                            </label>
                            <input type="text" name="preferred_industries" 
                                   value="<?= htmlspecialchars($_POST['preferred_industries'] ?? 'AI & DeepTech, SaaS, FinTech, HealthTech') ?>" 
                                   placeholder="e.g. AI & DeepTech, SaaS, FinTech"
                                   class="w-full px-3 py-2 bg-white border border-slate-200 focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/10 rounded-xl text-xs text-slate-900 outline-none transition font-medium">
                        </div>
                    </div>

                    <!-- Shared: Password & Confirmation (Grid) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 mb-1">
                                Password <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <i data-lucide="lock" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                                <input type="password" id="password" name="password" required placeholder="Min 6 characters"
                                       oninput="checkPasswordStrength(this.value)" autocomplete="new-password"
                                       class="w-full pl-10 pr-9 py-2.5 bg-slate-50 hover:bg-slate-50/80 border border-slate-200 focus:bg-white focus:border-indigo-600 focus:ring-4 focus:ring-indigo-600/10 rounded-xl text-xs sm:text-sm text-slate-900 placeholder-slate-400 outline-none transition font-medium">
                                <button type="button" onclick="togglePasswordVisibility('password', 'pwd-icon-1')" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 p-1">
                                    <i data-lucide="eye" id="pwd-icon-1" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 mb-1">
                                Confirm Password <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <i data-lucide="lock-check" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                                <input type="password" id="password_confirmation" name="password_confirmation" required placeholder="Repeat password"
                                       autocomplete="new-password"
                                       class="w-full pl-10 pr-9 py-2.5 bg-slate-50 hover:bg-slate-50/80 border border-slate-200 focus:bg-white focus:border-indigo-600 focus:ring-4 focus:ring-indigo-600/10 rounded-xl text-xs sm:text-sm text-slate-900 placeholder-slate-400 outline-none transition font-medium">
                                <button type="button" onclick="togglePasswordVisibility('password_confirmation', 'pwd-icon-2')" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 p-1">
                                    <i data-lucide="eye" id="pwd-icon-2" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Password Strength Meter -->
                    <div class="space-y-1" id="strength-container" style="display: none;">
                        <div class="flex items-center justify-between text-[10px] text-slate-500">
                            <span>Password strength:</span>
                            <span id="strength-label" class="font-bold text-slate-600">Weak</span>
                        </div>
                        <div class="h-1.5 w-full bg-slate-100 rounded-full overflow-hidden flex">
                            <div id="strength-bar" class="h-full bg-rose-500 transition-all duration-300 w-1/4"></div>
                        </div>
                    </div>

                    <!-- Shared: City & Country (Grid) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 mb-1">
                                City
                            </label>
                            <div class="relative">
                                <i data-lucide="map-pin" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                                <input type="text" name="city" value="<?= htmlspecialchars($_POST['city'] ?? 'Bengaluru') ?>" required
                                       class="w-full pl-10 pr-3.5 py-2 bg-slate-50 hover:bg-slate-50/80 border border-slate-200 focus:bg-white focus:border-indigo-600 focus:ring-2 focus:ring-indigo-600/10 rounded-xl text-xs sm:text-sm text-slate-900 outline-none transition font-medium">
                            </div>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 mb-1">
                                Country
                            </label>
                            <div class="relative">
                                <i data-lucide="globe" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                                <input type="text" name="country" value="<?= htmlspecialchars($_POST['country'] ?? 'India') ?>" required
                                       class="w-full pl-10 pr-3.5 py-2 bg-slate-50 hover:bg-slate-50/80 border border-slate-200 focus:bg-white focus:border-indigo-600 focus:ring-2 focus:ring-indigo-600/10 rounded-xl text-xs sm:text-sm text-slate-900 outline-none transition font-medium">
                            </div>
                        </div>
                    </div>

                    <!-- Terms & Conditions Checkbox -->
                    <div class="pt-1">
                        <label class="flex items-start space-x-2.5 cursor-pointer group">
                            <input type="checkbox" name="terms" required <?= isset($_POST['terms']) ? 'checked' : '' ?>
                                   class="mt-0.5 w-4 h-4 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500 cursor-pointer">
                            <span class="text-[11px] text-slate-600 leading-snug group-hover:text-slate-800 transition">
                                I agree to the <a href="<?= url('legal/terms.php') ?>" target="_blank" class="text-indigo-600 hover:text-indigo-800 font-bold underline decoration-indigo-200">Terms of Service</a>, <a href="<?= url('legal/privacy.php') ?>" target="_blank" class="text-indigo-600 hover:text-indigo-800 font-bold underline decoration-indigo-200">Privacy Policy</a>, and understand the private venture risk disclosures.
                            </span>
                        </label>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-2">
                        <button type="submit" id="submitBtn"
                                class="w-full py-3 px-6 bg-indigo-600 hover:bg-indigo-700 active:scale-[0.99] text-white text-xs sm:text-sm font-bold rounded-xl shadow-lg shadow-indigo-600/25 hover:shadow-indigo-600/35 transition-all duration-200 flex items-center justify-center space-x-2 group">
                            <span id="btn-submit-text">Complete <?= $selectedRole === 'investor' ? 'Investor' : 'Founder' ?> Registration</span>
                            <i data-lucide="arrow-right" class="w-4 h-4 group-hover:translate-x-1 transition-transform"></i>
                        </button>
                    </div>
                </form>

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
                        <i data-lucide="shield" class="w-3 h-3 text-indigo-500"></i>
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

    <!-- Interactive Role Toggle & Dynamic Left Panel Animation -->
    <script>
        lucide.createIcons();

        // Role details mapping
        const roleData = {
            founder: {
                badge: "FOUNDER CAPITAL ACCELERATOR",
                title: "Connect directly with verified angel syndicates and institutional capital.",
                subtitle: "Raise your Seed or Pre-Series A with zero broker fees. Share your confidential data room with accredited investors ready to deploy.",
                btnText: "Complete Founder Registration",
                imgBadge1: "Live Pitch Deck Active",
                imgSubtext: "500+ Active Syndicate Leads",
                imgMetric: "₹185 Cr+ Raised",
                features: [
                    {
                        icon: "rocket",
                        title: "Direct Angel & VC Access",
                        desc: "Pitch directly to 500+ active angel investors and syndicates without cold email fatigue."
                    },
                    {
                        icon: "file-lock-2",
                        title: "Confidential Rooms",
                        desc: "Control who views your deck, monitor viewer analytics, and protect proprietary IP with automated NDAs."
                    },
                    {
                        icon: "banknote",
                        title: "Standardized Terms",
                        desc: "Close rounds faster with standardized SAFE notes, convertible debentures, and milestone escrow."
                    }
                ],
                testimonial: {
                    avatar: "AS",
                    name: "Aarav Sharma",
                    role: "• CEO, TechPulse AI (Raised ₹4.5 Cr)",
                    quote: "“Closing our seed round in 3 weeks was only possible because of verified angel matching. Total game changer.”"
                }
            },
            investor: {
                badge: "ACCREDITED CAPITAL PIPELINE",
                title: "Discover high-conviction startups vetted for rapid scale.",
                subtitle: "Access institutional-grade private deals with audited cap tables, verified traction metrics, and seamless co-investment syndication.",
                btnText: "Complete Investor Registration",
                imgBadge1: "Curated Deal Flow Pipeline",
                imgSubtext: "1,400+ Vetted Tech Startups",
                imgMetric: "18.4% Average IRR",
                features: [
                    {
                        icon: "layers",
                        title: "Pre-Vetted Deal Flow",
                        desc: "Filter seed rounds by sector, ARR, and valuation with verified founder identity verification."
                    },
                    {
                        icon: "shield-check",
                        title: "Secure Due Diligence",
                        desc: "Instant access to verified pitch decks, financial models, cap tables, and compliance records."
                    },
                    {
                        icon: "trending-up",
                        title: "Syndication & IRR",
                        desc: "Co-invest starting from ₹2 Lakhs, receive digital share certificates, and track portfolio returns."
                    }
                ],
                testimonial: {
                    avatar: "VS",
                    name: "Vikram Singhania",
                    role: "• Syndicate Lead (32 Deals Closed)",
                    quote: "“The diligence depth, verified MCA records, and clean cap table documentation makes this our primary early-stage syndication portal.”"
                }
            }
        };

        function selectRole(role) {
            document.getElementById('form-role').value = role;
            
            const btnFounder = document.getElementById('role-btn-founder');
            const btnInvestor = document.getElementById('role-btn-investor');
            const founderFields = document.getElementById('founder-fields');
            const investorFields = document.getElementById('investor-fields');
            const founderCompInput = document.getElementById('founder-company');

            if (role === 'founder') {
                btnFounder.className = "py-2.5 px-3 rounded-xl text-xs font-bold flex items-center justify-center space-x-2 transition-all duration-200 bg-white text-indigo-600 shadow-sm ring-1 ring-slate-200";
                btnInvestor.className = "py-2.5 px-3 rounded-xl text-xs font-bold flex items-center justify-center space-x-2 transition-all duration-200 text-slate-500 hover:text-slate-800 hover:bg-white/60";
                
                founderFields.classList.remove('hidden');
                investorFields.classList.add('hidden');
                founderCompInput.setAttribute('required', 'required');
            } else {
                btnInvestor.className = "py-2.5 px-3 rounded-xl text-xs font-bold flex items-center justify-center space-x-2 transition-all duration-200 bg-white text-indigo-600 shadow-sm ring-1 ring-slate-200";
                btnFounder.className = "py-2.5 px-3 rounded-xl text-xs font-bold flex items-center justify-center space-x-2 transition-all duration-200 text-slate-500 hover:text-slate-800 hover:bg-white/60";
                
                investorFields.classList.remove('hidden');
                founderFields.classList.add('hidden');
                founderCompInput.removeAttribute('required');
            }

            // Animate transition on Left Information Column
            const data = roleData[role];
            if (window.gsap) {
                gsap.to(["#info-badge", "#info-title", "#info-subtitle", "#info-features", "#testimonial-card"], {
                    opacity: 0,
                    y: -5,
                    duration: 0.12,
                    onComplete: () => {
                        updateLeftPanel(data);
                        gsap.to(["#info-badge", "#info-title", "#info-subtitle", "#info-features", "#testimonial-card"], {
                            opacity: 1,
                            y: 0,
                            duration: 0.22,
                            stagger: 0.03
                        });
                    }
                });
            } else {
                updateLeftPanel(data);
            }
        }

        function updateLeftPanel(data) {
            document.getElementById('badge-text').innerText = data.badge;
            document.getElementById('info-title').innerText = data.title;
            document.getElementById('info-subtitle').innerText = data.subtitle;
            document.getElementById('btn-submit-text').innerText = data.btnText;
            document.getElementById('img-badge-1').innerText = data.imgBadge1;
            document.getElementById('img-subtext').innerText = data.imgSubtext;
            document.getElementById('img-metric').innerText = data.imgMetric;

            // Update 3 features
            for (let i = 0; i < 3; i++) {
                const feat = data.features[i];
                document.getElementById(`feat-title-${i+1}`).innerText = feat.title;
                document.getElementById(`feat-desc-${i+1}`).innerText = feat.desc;
                const iconBox = document.getElementById(`feat-icon-${i+1}`);
                iconBox.innerHTML = `<i data-lucide="${feat.icon}" class="w-3.5 h-3.5"></i>`;
            }

            // Update testimonial
            document.getElementById('test-avatar').innerText = data.testimonial.avatar;
            document.getElementById('test-name').innerText = data.testimonial.name;
            document.getElementById('test-role').innerText = data.testimonial.role;
            document.getElementById('test-quote').innerText = data.testimonial.quote;

            lucide.createIcons();
        }

        // Toggle Password Show/Hide
        function togglePasswordVisibility(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            if (input.type === 'password') {
                input.type = 'text';
                icon.setAttribute('data-lucide', 'eye-off');
            } else {
                input.type = 'password';
                icon.setAttribute('data-lucide', 'eye');
            }
            lucide.createIcons();
        }

        // Real-time Password Strength Check
        function checkPasswordStrength(password) {
            const container = document.getElementById('strength-container');
            const bar = document.getElementById('strength-bar');
            const label = document.getElementById('strength-label');

            if (!password || password.length === 0) {
                container.style.display = 'none';
                return;
            }

            container.style.display = 'block';

            let score = 0;
            if (password.length >= 6) score++;
            if (password.length >= 10) score++;
            if (/[0-9]/.test(password)) score++;
            if (/[^A-Za-z0-9]/.test(password)) score++;

            if (score <= 1) {
                bar.style.width = '25%';
                bar.className = 'h-full bg-rose-500 transition-all duration-300';
                label.innerText = 'Weak';
                label.className = 'font-bold text-rose-600';
            } else if (score === 2) {
                bar.style.width = '50%';
                bar.className = 'h-full bg-amber-500 transition-all duration-300';
                label.innerText = 'Fair';
                label.className = 'font-bold text-amber-600';
            } else if (score === 3) {
                bar.style.width = '75%';
                bar.className = 'h-full bg-blue-500 transition-all duration-300';
                label.innerText = 'Good';
                label.className = 'font-bold text-blue-600';
            } else {
                bar.style.width = '100%';
                bar.className = 'h-full bg-emerald-500 transition-all duration-300';
                label.innerText = 'Strong';
                label.className = 'font-bold text-emerald-600';
            }
        }

        // Form Submit Loading Feedback
        document.getElementById('registrationForm')?.addEventListener('submit', function() {
            const btn = document.getElementById('submitBtn');
            const btnText = document.getElementById('btn-submit-text');
            if (btn && btnText) {
                btn.classList.add('opacity-90', 'cursor-wait');
                btnText.textContent = 'Creating your account...';
            }
        });
    </script>
</body>
</html>
