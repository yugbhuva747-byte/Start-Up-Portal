<?php
/**
 * Authentication: Multi-Role Registration
 * Clean White Theme, Small Crisp Typography, Professional Look
 */
require_once __DIR__ . '/../config.php';

if (auth_check()) {
    header('Location: ' . url('index.php'));
    exit;
}

$error = '';
$selectedRole = $_GET['role'] ?? 'founder';
if (!in_array($selectedRole, ['founder', 'investor'])) {
    $selectedRole = 'founder';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirmation'] ?? '';
        $role = $_POST['role'] ?? 'founder';
        $city = trim($_POST['city'] ?? 'Bengaluru');
        $country = trim($_POST['country'] ?? 'India');
        $acceptTerms = isset($_POST['terms']);

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
                $checkStmt = $db->prepare("SELECT id FROM users WHERE email = ?");
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
                        $fpStmt = $db->prepare("INSERT INTO founder_profiles (user_id, designation) VALUES (?, 'Founder & CEO')");
                        $fpStmt->execute([$userId]);
                    } elseif ($role === 'investor') {
                        $ipStmt = $db->prepare("INSERT INTO investor_profiles (user_id, investor_type, risk_disclosure_accepted) VALUES (?, 'Angel Investor', 1)");
                        $ipStmt->execute([$userId]);
                        
                        $prefStmt = $db->prepare("INSERT INTO investor_preferences (user_id) VALUES (?)");
                        $prefStmt->execute([$userId]);
                    }

                    $_SESSION['user_id'] = $userId;
                    $_SESSION['user_role'] = $role;
                    $_SESSION['user_name'] = $name;

                    log_audit($userId, 'USER_REGISTERED', 'users', $userId, "User registered as {$role}");
                    header('Location: ' . url('auth/onboarding.php'));
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
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
        }
        .custom-glow {
            box-shadow: 0 0 50px -10px rgba(99, 102, 241, 0.25);
        }
        .glass-panel {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .mesh-bg {
            background-color: #0b0f19;
            background-image: 
                radial-gradient(at 0% 0%, rgba(99, 102, 241, 0.18) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(79, 70, 229, 0.15) 0px, transparent 50%),
                radial-gradient(at 50% 50%, rgba(30, 27, 75, 0.3) 0px, transparent 50%);
        }
        .dot-pattern {
            background-image: radial-gradient(rgba(255, 255, 255, 0.12) 1px, transparent 1px);
            background-size: 24px 24px;
        }
    </style>
</head>
<body class="bg-slate-900 text-slate-900 min-h-screen selection:bg-indigo-500 selection:text-white">

    <div class="min-h-screen flex flex-col lg:flex-row">
        
        <!-- ==============================================
             LEFT SIDE: INFORMATION & BRANDING (UNIQUE HIGHLIGHTS)
             ============================================== -->
        <div class="lg:w-5/12 xl:w-5/12 mesh-bg text-white p-8 lg:p-12 xl:p-16 flex flex-col justify-between relative overflow-hidden border-b lg:border-b-0 lg:border-r border-slate-800">
            
            <!-- Subtle background ambient dot grid -->
            <div class="absolute inset-0 dot-pattern opacity-40 pointer-events-none"></div>
            
            <!-- Top Ambient Glow Orb -->
            <div class="absolute -top-24 -left-24 w-96 h-96 bg-indigo-600/20 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute bottom-10 right-0 w-80 h-80 bg-violet-600/15 rounded-full blur-3xl pointer-events-none"></div>

            <div class="relative z-10">
                <!-- Portal Brand Logo & Return Link -->
                <div class="flex items-center justify-between mb-12">
                    <a href="<?= url('index.php') ?>" class="inline-flex items-center space-x-3 group">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-600 via-indigo-500 to-violet-500 flex items-center justify-center text-white shadow-lg shadow-indigo-500/30 group-hover:scale-105 transition-transform duration-200">
                            <i data-lucide="zap" class="w-5 h-5 text-white"></i>
                        </div>
                        <div>
                            <span class="text-base font-extrabold tracking-tight text-white flex items-center gap-1.5">
                                STARTUP <span class="text-indigo-400 font-black">×</span> INVESTOR
                            </span>
                            <span class="block text-[9px] tracking-widest text-slate-400 uppercase font-semibold">Venture Network</span>
                        </div>
                    </a>

                    <a href="<?= url('index.php') ?>" class="hidden sm:inline-flex items-center text-xs text-slate-400 hover:text-white transition group space-x-1.5">
                        <i data-lucide="arrow-left" class="w-3.5 h-3.5 group-hover:-translate-x-0.5 transition-transform"></i>
                        <span>Back to home</span>
                    </a>
                </div>

                <!-- Dynamic Category Tag -->
                <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full glass-panel text-[11px] font-semibold text-indigo-300 mb-6" id="info-badge">
                    <span class="w-2 h-2 rounded-full bg-indigo-400 animate-pulse"></span>
                    <span id="badge-text"><?= $selectedRole === 'investor' ? 'ACCREDITED CAPITAL PIPELINE' : 'FOUNDER CAPITAL ACCELERATOR' ?></span>
                </div>

                <!-- Big Dynamic Headline -->
                <h1 class="text-2xl sm:text-3xl xl:text-4xl font-extrabold text-white tracking-tight leading-snug mb-4" id="info-title">
                    <?= $selectedRole === 'investor' 
                        ? 'Discover high-conviction startups vetted for rapid scale.' 
                        : 'Connect directly with verified angel syndicates and institutional capital.' ?>
                </h1>

                <!-- Subtitle -->
                <p class="text-slate-300 text-sm leading-relaxed mb-10 max-w-lg" id="info-subtitle">
                    <?= $selectedRole === 'investor'
                        ? 'Access institutional-grade private deals with audited cap tables, verified traction metrics, and seamless co-investment syndication.'
                        : 'Raise your Seed or Pre-Series A with zero broker fees. Share your confidential data room with accredited investors ready to deploy.' ?>
                </p>

                <!-- Information Feature Cards (with Theme Color Icons) -->
                <div class="space-y-4 mb-10" id="info-features">
                    <!-- Feature 1 -->
                    <div class="flex items-start space-x-4 p-3.5 rounded-xl glass-panel hover:bg-white/10 transition border border-white/5">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500/20 to-indigo-600/30 border border-indigo-500/30 flex items-center justify-center text-indigo-400 flex-shrink-0 mt-0.5" id="feat-icon-1">
                            <i data-lucide="<?= $selectedRole === 'investor' ? 'layers' : 'rocket' ?>" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h3 class="text-xs font-bold text-white tracking-wide uppercase mb-1" id="feat-title-1">
                                <?= $selectedRole === 'investor' ? 'Pre-Vetted Deal Flow' : 'Direct Angel & VC Access' ?>
                            </h3>
                            <p class="text-xs text-slate-300 leading-normal" id="feat-desc-1">
                                <?= $selectedRole === 'investor' 
                                    ? 'Filter seed rounds by industry, ARR, and valuation with MCA-verified founder identity verification.' 
                                    : 'Pitch directly to 500+ active angel investors and syndicates without cold email fatigue.' ?>
                            </p>
                        </div>
                    </div>

                    <!-- Feature 2 -->
                    <div class="flex items-start space-x-4 p-3.5 rounded-xl glass-panel hover:bg-white/10 transition border border-white/5">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-violet-500/20 to-violet-600/30 border border-violet-500/30 flex items-center justify-center text-violet-400 flex-shrink-0 mt-0.5" id="feat-icon-2">
                            <i data-lucide="<?= $selectedRole === 'investor' ? 'shield-check' : 'file-lock-2' ?>" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h3 class="text-xs font-bold text-white tracking-wide uppercase mb-1" id="feat-title-2">
                                <?= $selectedRole === 'investor' ? 'Secure Due Diligence Rooms' : 'Confidential Data Rooms' ?>
                            </h3>
                            <p class="text-xs text-slate-300 leading-normal" id="feat-desc-2">
                                <?= $selectedRole === 'investor' 
                                    ? 'Instant access to verified pitch decks, financial models, cap tables, and compliance certificates.' 
                                    : 'Control who views your deck, monitor viewer analytics, and protect proprietary IP with automated NDAs.' ?>
                            </p>
                        </div>
                    </div>

                    <!-- Feature 3 -->
                    <div class="flex items-start space-x-4 p-3.5 rounded-xl glass-panel hover:bg-white/10 transition border border-white/5">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-500/20 to-emerald-600/30 border border-emerald-500/30 flex items-center justify-center text-emerald-400 flex-shrink-0 mt-0.5" id="feat-icon-3">
                            <i data-lucide="<?= $selectedRole === 'investor' ? 'trending-up' : 'banknote' ?>" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h3 class="text-xs font-bold text-white tracking-wide uppercase mb-1" id="feat-title-3">
                                <?= $selectedRole === 'investor' ? 'Syndication & Portfolio Tracking' : 'Standardized Term Sheets' ?>
                            </h3>
                            <p class="text-xs text-slate-300 leading-normal" id="feat-desc-3">
                                <?= $selectedRole === 'investor' 
                                    ? 'Co-invest starting from ₹2 Lakhs, receive digital share certificates, and track portfolio IRR in real time.' 
                                    : 'Close rounds faster with standardized SAFE notes, convertible debentures, and milestone-linked escrow.' ?>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Testimonial / Social Proof Snippet -->
                <div class="p-4 rounded-2xl glass-panel border border-indigo-500/20 relative" id="testimonial-card">
                    <div class="flex items-center space-x-3 mb-2">
                        <div class="w-8 h-8 rounded-full bg-gradient-to-r from-indigo-500 to-pink-500 flex items-center justify-center font-bold text-xs text-white uppercase shadow-sm" id="test-avatar">
                            AS
                        </div>
                        <div>
                            <div class="text-xs font-bold text-white" id="test-name">Aarav Sharma</div>
                            <div class="text-[10px] text-slate-400" id="test-role">Founder & CEO, TechPulse AI (Raised ₹4.5 Cr)</div>
                        </div>
                        <div class="ml-auto flex items-center text-amber-400 text-xs gap-0.5">
                            <i data-lucide="star" class="w-3 h-3 fill-amber-400"></i>
                            <i data-lucide="star" class="w-3 h-3 fill-amber-400"></i>
                            <i data-lucide="star" class="w-3 h-3 fill-amber-400"></i>
                            <i data-lucide="star" class="w-3 h-3 fill-amber-400"></i>
                            <i data-lucide="star" class="w-3 h-3 fill-amber-400"></i>
                        </div>
                    </div>
                    <p class="text-xs text-slate-300 italic leading-relaxed" id="test-quote">
                        "Closing our seed round in 3 weeks was only possible because of verified angel matching. The portal eliminated months of cold networking."
                    </p>
                </div>
            </div>

            <!-- Bottom Proof & Trust Bar -->
            <div class="relative z-10 pt-8 mt-8 border-t border-slate-800/80">
                <div class="grid grid-cols-3 gap-4 text-center">
                    <div>
                        <div class="text-lg xl:text-xl font-black text-white tracking-tight">$12M+</div>
                        <div class="text-[10px] uppercase font-semibold text-slate-400 tracking-wider">Capital Raised</div>
                    </div>
                    <div class="border-x border-slate-800">
                        <div class="text-lg xl:text-xl font-black text-white tracking-tight">500+</div>
                        <div class="text-[10px] uppercase font-semibold text-slate-400 tracking-wider">Accredited Angels</div>
                    </div>
                    <div>
                        <div class="text-lg xl:text-xl font-black text-white tracking-tight">100%</div>
                        <div class="text-[10px] uppercase font-semibold text-slate-400 tracking-wider">Verified Vetting</div>
                    </div>
                </div>

                <div class="mt-4 flex items-center justify-center space-x-2 text-[10px] text-slate-400">
                    <i data-lucide="shield-check" class="w-3.5 h-3.5 text-emerald-400"></i>
                    <span>SEBI & MCA Compliant Governance • 256-Bit SSL Encryption</span>
                </div>
            </div>

        </div>


        <!-- ==============================================
             RIGHT SIDE: REGISTRATION FORM
             ============================================== -->
        <div class="lg:w-7/12 xl:w-7/12 bg-white flex flex-col justify-between p-6 sm:p-10 lg:p-12 xl:p-16">
            
            <!-- Mobile Top Bar with Home Link & Sign In -->
            <div class="flex items-center justify-between pb-6 mb-4 border-b border-slate-100">
                <div class="flex items-center space-x-2 lg:hidden">
                    <div class="w-7 h-7 rounded-lg bg-indigo-600 flex items-center justify-center text-white">
                        <i data-lucide="zap" class="w-4 h-4"></i>
                    </div>
                    <span class="text-xs font-extrabold text-slate-900 tracking-tight">STARTUP <span class="text-indigo-600">×</span> INVESTOR</span>
                </div>
                <div class="text-right ml-auto">
                    <span class="text-xs text-slate-500">Already have an account?</span>
                    <a href="<?= url('auth/login.php') ?>" class="ml-1 text-xs font-bold text-indigo-600 hover:text-indigo-800 transition inline-flex items-center space-x-1">
                        <span>Sign In</span>
                        <i data-lucide="arrow-right" class="w-3 h-3"></i>
                    </a>
                </div>
            </div>

            <!-- Form Content Wrapper -->
            <div class="max-w-xl w-full mx-auto my-auto py-2">
                
                <div class="mb-6">
                    <div class="inline-flex items-center space-x-1.5 px-2.5 py-1 rounded-md bg-indigo-50 border border-indigo-100 text-indigo-700 text-[11px] font-bold mb-2">
                        <i data-lucide="sparkles" class="w-3 h-3 text-indigo-600"></i>
                        <span>Start Your Venture Journey</span>
                    </div>
                    <h2 class="text-2xl font-black text-slate-900 tracking-tight">Create Your Account</h2>
                    <p class="text-xs text-slate-500 mt-1">Select your portal role below to unlock your customized venture experience.</p>
                </div>

                <!-- Role Selector Tabs -->
                <div class="mb-6">
                    <label class="block text-[10.5px] font-bold uppercase tracking-wider text-slate-500 mb-2">Select Your Role *</label>
                    <div class="grid grid-cols-2 gap-3 p-1.5 bg-slate-100/90 rounded-2xl border border-slate-200">
                        <button type="button" onclick="selectRole('founder')" id="role-btn-founder"
                                class="py-3 px-4 rounded-xl text-xs font-bold flex items-center justify-center space-x-2.5 transition-all duration-200 <?= $selectedRole === 'founder' ? 'bg-white text-indigo-600 shadow-md shadow-indigo-500/10 ring-1 ring-slate-200/80' : 'text-slate-500 hover:text-slate-900 hover:bg-white/50' ?>">
                            <div class="w-7 h-7 rounded-lg <?= $selectedRole === 'founder' ? 'bg-indigo-50 text-indigo-600' : 'bg-slate-200/70 text-slate-500' ?> flex items-center justify-center transition-colors">
                                <i data-lucide="rocket" class="w-4 h-4"></i>
                            </div>
                            <div class="text-left">
                                <div class="font-bold leading-tight">Startup Founder</div>
                                <div class="text-[9.5px] font-medium opacity-80">Raising Early Capital</div>
                            </div>
                        </button>

                        <button type="button" onclick="selectRole('investor')" id="role-btn-investor"
                                class="py-3 px-4 rounded-xl text-xs font-bold flex items-center justify-center space-x-2.5 transition-all duration-200 <?= $selectedRole === 'investor' ? 'bg-white text-indigo-600 shadow-md shadow-indigo-500/10 ring-1 ring-slate-200/80' : 'text-slate-500 hover:text-slate-900 hover:bg-white/50' ?>">
                            <div class="w-7 h-7 rounded-lg <?= $selectedRole === 'investor' ? 'bg-indigo-50 text-indigo-600' : 'bg-slate-200/70 text-slate-500' ?> flex items-center justify-center transition-colors">
                                <i data-lucide="trending-up" class="w-4 h-4"></i>
                            </div>
                            <div class="text-left">
                                <div class="font-bold leading-tight">Angel / Investor</div>
                                <div class="text-[9.5px] font-medium opacity-80">Deploying Capital</div>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Error Notification -->
                <?php if (!empty($error)): ?>
                    <div class="mb-5 p-3.5 rounded-xl text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200 flex items-center space-x-2.5 animate-pulse">
                        <i data-lucide="alert-circle" class="w-4 h-4 flex-shrink-0 text-rose-600"></i>
                        <span><?= htmlspecialchars($error) ?></span>
                    </div>
                <?php endif; ?>

                <!-- The Registration Form -->
                <form action="<?= url('auth/register.php') ?>" method="POST" class="space-y-4" id="registrationForm">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="role" id="form-role" value="<?= htmlspecialchars($selectedRole) ?>">

                    <!-- Full Name -->
                    <div>
                        <label class="block text-[10.5px] font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            Full Legal Name <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <i data-lucide="user" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                            <input type="text" name="name" required placeholder="e.g. Aarav Sharma"
                                   value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                                   class="w-full pl-10 pr-3.5 py-2.5 bg-slate-50/70 border border-slate-200 focus:bg-white focus:border-indigo-600 focus:ring-4 focus:ring-indigo-600/10 rounded-xl text-xs text-slate-900 placeholder-slate-400 outline-none transition duration-150">
                        </div>
                    </div>

                    <!-- Email & Phone (Grid) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                        <div>
                            <label class="block text-[10.5px] font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                Work Email <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <i data-lucide="mail" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                                <input type="email" name="email" required placeholder="name@domain.com"
                                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                       class="w-full pl-10 pr-3.5 py-2.5 bg-slate-50/70 border border-slate-200 focus:bg-white focus:border-indigo-600 focus:ring-4 focus:ring-indigo-600/10 rounded-xl text-xs text-slate-900 placeholder-slate-400 outline-none transition duration-150">
                            </div>
                        </div>

                        <div>
                            <label class="block text-[10.5px] font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                Phone Number
                            </label>
                            <div class="relative">
                                <i data-lucide="phone" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                                <input type="tel" name="phone" placeholder="+91 98765 43210"
                                       value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
                                       class="w-full pl-10 pr-3.5 py-2.5 bg-slate-50/70 border border-slate-200 focus:bg-white focus:border-indigo-600 focus:ring-4 focus:ring-indigo-600/10 rounded-xl text-xs text-slate-900 placeholder-slate-400 outline-none transition duration-150">
                            </div>
                        </div>
                    </div>

                    <!-- Password & Confirmation (Grid) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                        <div>
                            <label class="block text-[10.5px] font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                Password <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <i data-lucide="lock" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                                <input type="password" id="password" name="password" required placeholder="Min 6 characters"
                                       oninput="checkPasswordStrength(this.value)"
                                       class="w-full pl-10 pr-9 py-2.5 bg-slate-50/70 border border-slate-200 focus:bg-white focus:border-indigo-600 focus:ring-4 focus:ring-indigo-600/10 rounded-xl text-xs text-slate-900 placeholder-slate-400 outline-none transition duration-150">
                                <button type="button" onclick="togglePasswordVisibility('password', 'pwd-icon-1')" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                                    <i data-lucide="eye" id="pwd-icon-1" class="w-3.5 h-3.5"></i>
                                </button>
                            </div>
                        </div>

                        <div>
                            <label class="block text-[10.5px] font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                Confirm Password <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <i data-lucide="lock-check" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                                <input type="password" id="password_confirmation" name="password_confirmation" required placeholder="Repeat password"
                                       class="w-full pl-10 pr-9 py-2.5 bg-slate-50/70 border border-slate-200 focus:bg-white focus:border-indigo-600 focus:ring-4 focus:ring-indigo-600/10 rounded-xl text-xs text-slate-900 placeholder-slate-400 outline-none transition duration-150">
                                <button type="button" onclick="togglePasswordVisibility('password_confirmation', 'pwd-icon-2')" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                                    <i data-lucide="eye" id="pwd-icon-2" class="w-3.5 h-3.5"></i>
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

                    <!-- City & Country (Grid) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                        <div>
                            <label class="block text-[10.5px] font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                City
                            </label>
                            <div class="relative">
                                <i data-lucide="map-pin" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                                <input type="text" name="city" value="<?= htmlspecialchars($_POST['city'] ?? 'Bengaluru') ?>" required
                                       class="w-full pl-10 pr-3.5 py-2.5 bg-slate-50/70 border border-slate-200 focus:bg-white focus:border-indigo-600 focus:ring-4 focus:ring-indigo-600/10 rounded-xl text-xs text-slate-900 outline-none transition duration-150">
                            </div>
                        </div>

                        <div>
                            <label class="block text-[10.5px] font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                Country
                            </label>
                            <div class="relative">
                                <i data-lucide="globe" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                                <input type="text" name="country" value="<?= htmlspecialchars($_POST['country'] ?? 'India') ?>" required
                                       class="w-full pl-10 pr-3.5 py-2.5 bg-slate-50/70 border border-slate-200 focus:bg-white focus:border-indigo-600 focus:ring-4 focus:ring-indigo-600/10 rounded-xl text-xs text-slate-900 outline-none transition duration-150">
                            </div>
                        </div>
                    </div>

                    <!-- Terms & Conditions Checkbox -->
                    <div class="pt-1">
                        <label class="flex items-start space-x-2.5 cursor-pointer group">
                            <input type="checkbox" name="terms" required <?= isset($_POST['terms']) ? 'checked' : '' ?>
                                   class="mt-0.5 w-4 h-4 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500 cursor-pointer">
                            <span class="text-[11px] text-slate-600 leading-snug group-hover:text-slate-800 transition">
                                I agree to the <a href="<?= url('legal/terms.php') ?>" target="_blank" class="text-indigo-600 hover:text-indigo-800 font-semibold underline decoration-indigo-200 underline-offset-2">Terms of Service</a>, <a href="<?= url('legal/privacy.php') ?>" target="_blank" class="text-indigo-600 hover:text-indigo-800 font-semibold underline decoration-indigo-200 underline-offset-2">Privacy Policy</a>, and understand the startup investment risk disclosures.
                            </span>
                        </label>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-2">
                        <button type="submit" 
                                class="w-full py-3 px-6 bg-gradient-to-r from-indigo-600 via-indigo-600 to-indigo-700 hover:from-indigo-500 hover:to-indigo-600 active:scale-[0.99] text-white text-xs font-bold rounded-xl shadow-lg shadow-indigo-600/25 transition-all duration-200 flex items-center justify-center space-x-2 group">
                            <span id="btn-submit-text">Complete <?= $selectedRole === 'investor' ? 'Investor' : 'Founder' ?> Registration</span>
                            <i data-lucide="arrow-right" class="w-4 h-4 group-hover:translate-x-1 transition-transform"></i>
                        </button>
                    </div>
                </form>

                <!-- Security Guarantee Pill -->
                <div class="mt-6 flex flex-wrap items-center justify-center gap-y-2 gap-x-4 text-[10.5px] text-slate-400">
                    <span class="flex items-center space-x-1">
                        <i data-lucide="lock" class="w-3 h-3 text-slate-400"></i>
                        <span>256-bit SSL encrypted</span>
                    </span>
                    <span class="text-slate-300">•</span>
                    <span class="flex items-center space-x-1">
                        <i data-lucide="check-circle-2" class="w-3 h-3 text-emerald-500"></i>
                        <span>DigiLocker verified</span>
                    </span>
                    <span class="text-slate-300">•</span>
                    <span class="flex items-center space-x-1">
                        <i data-lucide="shield" class="w-3 h-3 text-indigo-500"></i>
                        <span>Zero spam policy</span>
                    </span>
                </div>

            </div>

            <!-- Bottom Copyright -->
            <div class="pt-6 text-center text-[11px] text-slate-400 border-t border-slate-100">
                &copy; <?= date('Y') ?> <?= APP_NAME ?> Inc. All rights reserved.
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
                features: [
                    {
                        icon: "rocket",
                        colorClass: "from-indigo-500/20 to-indigo-600/30 border-indigo-500/30 text-indigo-400",
                        title: "Direct Angel & VC Access",
                        desc: "Pitch directly to 500+ active angel investors and syndicates without cold email fatigue."
                    },
                    {
                        icon: "file-lock-2",
                        colorClass: "from-violet-500/20 to-violet-600/30 border-violet-500/30 text-violet-400",
                        title: "Confidential Data Rooms",
                        desc: "Control who views your deck, monitor viewer analytics, and protect proprietary IP with automated NDAs."
                    },
                    {
                        icon: "banknote",
                        colorClass: "from-emerald-500/20 to-emerald-600/30 border-emerald-500/30 text-emerald-400",
                        title: "Standardized Term Sheets",
                        desc: "Close rounds faster with standardized SAFE notes, convertible debentures, and milestone-linked escrow."
                    }
                ],
                testimonial: {
                    avatar: "AS",
                    name: "Aarav Sharma",
                    role: "Founder & CEO, TechPulse AI (Raised ₹4.5 Cr)",
                    quote: "“Closing our seed round in 3 weeks was only possible because of verified angel matching. The portal eliminated months of cold networking.”"
                }
            },
            investor: {
                badge: "ACCREDITED CAPITAL PIPELINE",
                title: "Discover high-conviction startups vetted for rapid scale.",
                subtitle: "Access institutional-grade private deals with audited cap tables, verified traction metrics, and seamless co-investment syndication.",
                btnText: "Complete Investor Registration",
                features: [
                    {
                        icon: "layers",
                        colorClass: "from-indigo-500/20 to-indigo-600/30 border-indigo-500/30 text-indigo-400",
                        title: "Pre-Vetted Deal Flow",
                        desc: "Filter seed rounds by industry, ARR, and valuation with MCA-verified founder identity verification."
                    },
                    {
                        icon: "shield-check",
                        colorClass: "from-violet-500/20 to-violet-600/30 border-violet-500/30 text-violet-400",
                        title: "Secure Due Diligence Rooms",
                        desc: "Instant access to verified pitch decks, financial models, cap tables, and compliance certificates."
                    },
                    {
                        icon: "trending-up",
                        colorClass: "from-emerald-500/20 to-emerald-600/30 border-emerald-500/30 text-emerald-400",
                        title: "Syndication & Portfolio Tracking",
                        desc: "Co-invest starting from ₹2 Lakhs, receive digital share certificates, and track portfolio IRR in real time."
                    }
                ],
                testimonial: {
                    avatar: "VS",
                    name: "Vikram Singhania",
                    role: "Lead Angel Syndicate Partner (32 Deals Closed)",
                    quote: "“The diligence depth, verified MCA records, and clean cap table documentation makes this our primary early-stage syndication portal.”"
                }
            }
        };

        function selectRole(role) {
            document.getElementById('form-role').value = role;
            
            const btnFounder = document.getElementById('role-btn-founder');
            const btnInvestor = document.getElementById('role-btn-investor');
            const iconContainerF = btnFounder.querySelector('div');
            const iconContainerI = btnInvestor.querySelector('div');

            if (role === 'founder') {
                btnFounder.className = "py-3 px-4 rounded-xl text-xs font-bold flex items-center justify-center space-x-2.5 transition-all duration-200 bg-white text-indigo-600 shadow-md shadow-indigo-500/10 ring-1 ring-slate-200/80";
                btnInvestor.className = "py-3 px-4 rounded-xl text-xs font-bold flex items-center justify-center space-x-2.5 transition-all duration-200 text-slate-500 hover:text-slate-900 hover:bg-white/50";
                iconContainerF.className = "w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center transition-colors";
                iconContainerI.className = "w-7 h-7 rounded-lg bg-slate-200/70 text-slate-500 flex items-center justify-center transition-colors";
            } else {
                btnInvestor.className = "py-3 px-4 rounded-xl text-xs font-bold flex items-center justify-center space-x-2.5 transition-all duration-200 bg-white text-indigo-600 shadow-md shadow-indigo-500/10 ring-1 ring-slate-200/80";
                btnFounder.className = "py-3 px-4 rounded-xl text-xs font-bold flex items-center justify-center space-x-2.5 transition-all duration-200 text-slate-500 hover:text-slate-900 hover:bg-white/50";
                iconContainerI.className = "w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center transition-colors";
                iconContainerF.className = "w-7 h-7 rounded-lg bg-slate-200/70 text-slate-500 flex items-center justify-center transition-colors";
            }

            // Animate transition on Left Information Column
            const data = roleData[role];
            if (window.gsap) {
                gsap.to(["#info-badge", "#info-title", "#info-subtitle", "#info-features", "#testimonial-card"], {
                    opacity: 0,
                    y: -5,
                    duration: 0.15,
                    onComplete: () => {
                        updateLeftPanel(data);
                        gsap.to(["#info-badge", "#info-title", "#info-subtitle", "#info-features", "#testimonial-card"], {
                            opacity: 1,
                            y: 0,
                            duration: 0.25,
                            stagger: 0.04
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

            // Update 3 features
            for (let i = 0; i < 3; i++) {
                const feat = data.features[i];
                document.getElementById(`feat-title-${i+1}`).innerText = feat.title;
                document.getElementById(`feat-desc-${i+1}`).innerText = feat.desc;
                const iconBox = document.getElementById(`feat-icon-${i+1}`);
                iconBox.innerHTML = `<i data-lucide="${feat.icon}" class="w-5 h-5"></i>`;
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
    </script>
</body>
</html>

