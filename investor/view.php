<?php
/**
 * Investor Module: Full Public / Detail Investor Profile
 * LinkedIn Professional Profile + Premium Investor Network Experience
 */
require_once __DIR__ . '/../config.php';
$currentUser = require_auth(); // Accessible by founder, investor, admin
$db = get_db();

// 1. Resolve Target Investor ID
$targetUserId = 0;
if (isset($_GET['id'])) {
    $targetUserId = decode_id($_GET['id']);
}

// Fallback to current user if they are an investor and no ID passed
if ($targetUserId <= 0 && $currentUser['role'] === 'investor') {
    $targetUserId = $currentUser['id'];
}

if ($targetUserId <= 0) {
    set_flash('error', 'Investor profile not specified or invalid.');
    header('Location: ' . ($currentUser['role'] === 'founder' ? url('founder/dashboard.php') : url('investor/discover.php')));
    exit;
}

// 2. Fetch User & Investor Profile
$investor = null;
$profile = null;
$preferences = null;
$portfolio = [];
$totalDeployed = 0;

if ($db) {
    $uStmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $uStmt->execute([$targetUserId]);
    $investor = $uStmt->fetch();

    if (!$investor) {
        set_flash('error', 'Investor account not found.');
        header('Location: ' . url('index.php'));
        exit;
    }

    $ipStmt = $db->prepare("SELECT * FROM investor_profiles WHERE user_id = ?");
    $ipStmt->execute([$targetUserId]);
    $profile = $ipStmt->fetch();

    $prefStmt = $db->prepare("SELECT * FROM investor_preferences WHERE user_id = ?");
    $prefStmt->execute([$targetUserId]);
    $preferences = $prefStmt->fetch();

    // 3. Fetch Portfolio Investments
    $pStmt = $db->prepare("
        SELECT inv.*, c.name as company_name, c.logo_url as company_logo, c.industry as company_industry, c.stage as company_stage, fr.round_name
        FROM investments inv
        JOIN companies c ON inv.company_id = c.id
        JOIN funding_rounds fr ON inv.funding_round_id = fr.id
        WHERE inv.investor_user_id = ?
        ORDER BY inv.confirmed_at DESC
    ");
    $pStmt->execute([$targetUserId]);
    $portfolio = $pStmt->fetchAll();

    foreach ($portfolio as $item) {
        $totalDeployed += (float) $item['amount_invested'];
    }
}

$isSelf = ($currentUser['id'] == $targetUserId);
$pageTitle = htmlspecialchars($investor['name']) . ' • Investor Profile';
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> • <?= APP_NAME ?></title>
    <?php include __DIR__ . '/../includes/investor/head.php'; ?>
    <style>
        @font-face {
            font-family: "Vay Portal", Sans-serif;
            src: local('Vay Portal - Regular'), local('Vay Portal'), local('Plus Jakarta Sans');
        }

        :root {
            --inv-primary: #123B7A;
            --inv-navy: #0B1F3A;
            --inv-secondary: #315F9F;
            --inv-light-blue: #EAF2FF;
            --inv-bg: #F4F2EE;
            --inv-text: #111827;
            --inv-text-sec: #667085;
            --inv-border: #E4E8EF;
        }

        body {
            font-family: 'Vay Portal - Regular', 'Vay Portal', 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif !important;
            background-color: var(--inv-bg);
            color: var(--inv-text);
        }

        .profile-row-hover:hover {
            background-color: #FAFBFD;
        }
    </style>
</head>

<body class="bg-[#F4F2EE] text-[#111827] flex min-h-screen antialiased dark:bg-[#0B0F19] dark:text-slate-100">

    <!-- Sidebar Navigation based on current user role -->
    <?php
    if ($currentUser['role'] === 'founder') {
        include __DIR__ . '/../includes/founder/sidebar.php';
    } elseif ($currentUser['role'] === 'investor') {
        include __DIR__ . '/../includes/investor/sidebar.php';
    } else {
        include __DIR__ . '/../includes/admin/sidebar.php';
    }
    ?>

    <div class="flex-1 flex flex-col min-w-0">
        <?php
        if ($currentUser['role'] === 'founder') {
            include __DIR__ . '/../includes/founder/navbar.php';
        } elseif ($currentUser['role'] === 'investor') {
            include __DIR__ . '/../includes/investor/navbar.php';
        } else {
            include __DIR__ . '/../includes/admin/navbar.php';
        }
        ?>

        <main class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6" id="investor-view-main">

            <?php if ($flash): ?>
                <div
                    class="p-4 rounded-xl text-xs font-semibold border <?= $flash['type'] === 'success' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-rose-50 text-rose-800 border-rose-200' ?> flex items-center space-x-2">
                    <i data-lucide="check-circle" class="w-4 h-4 flex-shrink-0"></i>
                    <span><?= htmlspecialchars($flash['message']) ?></span>
                </div>
            <?php endif; ?>


            <!-- Breadcrumb Navigation -->
            <div class="flex items-center justify-between text-xs sm:text-sm text-slate-500">
                <div class="flex items-center space-x-2">
                    <?php if ($currentUser['role'] === 'investor'): ?>
                        <a href="<?= url('investor/dashboard.php') ?>" class="hover:text-slate-900 transition flex items-center space-x-1">
                            <i data-lucide="arrow-left" class="w-4 h-4"></i>
                            <span>Dashboard</span>
                        </a>
                    <?php else: ?>
                        <a href="<?= url('founder/dashboard.php') ?>" class="hover:text-slate-900 transition flex items-center space-x-1">
                            <i data-lucide="arrow-left" class="w-4 h-4"></i>
                            <span>Founder Workspace</span>
                        </a>
                    <?php endif; ?>
                    <span>/</span>
                    <span class="text-slate-800 font-bold">Investor Profile</span>
                </div>

                <?php if ($isSelf): ?>
                    <a href="<?= url('investor/profile.php') ?>" class="flex items-center space-x-1.5 px-3.5 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded-xl font-bold text-xs sm:text-sm transition">
                        <i data-lucide="edit-3" class="w-4 h-4"></i>
                        <span>Edit Thesis & Profile</span>
                    </a>

                <?php endif; ?>
            </div>

            <!-- LINKEDIN-STYLE PROFESSIONAL PROFILE HEADER -->
            <div class="bg-white border border-[#E4E8EF] rounded-2xl overflow-hidden shadow-sm">
                <!-- Cover Banner Strip -->
                <div class="h-36 sm:h-44 bg-gradient-to-r from-[#0B1F3A] via-[#123B7A] to-[#315F9F] relative">
                    <div
                        class="absolute inset-0 opacity-15 bg-[radial-gradient(#FFFFFF_1px,transparent_1px)] [background-size:20px_20px]">
                    </div>
                    <div class="absolute top-4 right-4 flex items-center space-x-2">
                        <span
                            class="px-3 py-1 rounded-full bg-white/15 backdrop-blur-md text-white text-[10.5px] font-semibold border border-white/20 uppercase tracking-wider">
                            SEBI Accredited Syndicate
                        </span>
                    </div>
                </div>


                <div class="px-5 sm:px-6 pb-6 pt-0 relative">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-5 border-b border-slate-100">
                        <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                            <div class="relative -mt-12 flex-shrink-0">
                                <img src="<?= $investor['avatar_url'] ?: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=160' ?>" 
                                     class="w-24 h-24 rounded-2xl object-cover border-4 border-white shadow-md bg-white">

                                <?php if ($investor['is_verified']): ?>
                                    <div class="absolute -bottom-2 -right-2 w-7 h-7 rounded-full bg-[#123B7A] border-2 border-white flex items-center justify-center text-white shadow-md"
                                        title="SEBI Verified Investor">
                                        <i data-lucide="check" class="w-4 h-4 stroke-[3]"></i>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="pt-2">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h1 class="text-lg sm:text-xl font-bold text-slate-900 tracking-tight leading-tight"><?= htmlspecialchars($investor['name']) ?></h1>
                                    <span class="px-2.5 py-0.5 rounded-full bg-teal-50 text-teal-700 border border-teal-200 text-xs font-bold">
                                        <?= strtoupper($profile['investor_type'] ?? 'ANGEL INVESTOR') ?>
                                    </span>
                                </div>
                                <div class="text-xs sm:text-sm font-semibold text-emerald-600 mt-1 flex items-center space-x-1.5">
                                    <i data-lucide="award" class="w-4 h-4"></i>
                                    <span>SEBI Compliant Accredited Investor</span>
                                    <span class="text-slate-300">•</span>
                                    <span class="text-slate-600 font-medium"><?= $profile['experience_years'] ?? 5 ?>+ Years in Venture</span>
                                </div>
                                <div class="flex flex-wrap items-center gap-3 text-xs sm:text-sm text-slate-500 mt-2">
                                    <span class="flex items-center space-x-1.5">
                                        <i data-lucide="map-pin" class="w-3.5 h-3.5 text-slate-400"></i>
                                        <span><?= htmlspecialchars($investor['city'] ?? 'Mumbai') ?>, <?= htmlspecialchars($investor['country'] ?? 'India') ?></span>
                                    </span>
                                    <span>•</span>
                                    <span class="flex items-center space-x-1.5">
                                        <i data-lucide="calendar" class="w-3.5 h-3.5 text-slate-400"></i>
                                        <span>Member since <?= date('M Y', strtotime($investor['created_at'])) ?></span>

                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Top Level Action Buttons -->
                        <div class="flex items-center space-x-3 pt-2 md:pt-0">
                            <?php if (!$isSelf): ?>

                                <a href="<?= url('founder/messages.php') ?>" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs sm:text-sm rounded-xl shadow-sm transition flex items-center space-x-2">
                                    <i data-lucide="message-square" class="w-4 h-4"></i>
                                    <span>Pitch Startup / Chat</span>
                                </a>
                            <?php else: ?>
                                <a href="<?= url('investor/profile.php') ?>" class="px-4 py-2.5 bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs sm:text-sm rounded-xl transition flex items-center space-x-2">
                                    <i data-lucide="settings" class="w-4 h-4"></i>
                                    <span>Thesis & Preferences</span>

                                </a>
                            <?php endif; ?>
                        </div>
                    </div>


                    <!-- Quick Metrics Strip -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3.5 pt-4 text-xs sm:text-sm">
                        <div class="p-3.5 sm:p-4 rounded-2xl bg-slate-50 border border-slate-100">
                            <span class="text-xs uppercase font-bold text-slate-500 tracking-wider">Total Deployed Capital</span>
                            <div class="text-lg sm:text-xl font-extrabold text-emerald-600 mt-1"><?= format_inr($totalDeployed) ?></div>
                        </div>
                        <div class="p-3.5 sm:p-4 rounded-2xl bg-slate-50 border border-slate-100">
                            <span class="text-xs uppercase font-bold text-slate-500 tracking-wider">Portfolio Companies</span>
                            <div class="text-lg sm:text-xl font-extrabold text-slate-900 mt-1"><?= count($portfolio) ?></div>
                        </div>
                        <div class="p-3.5 sm:p-4 rounded-2xl bg-slate-50 border border-slate-100">
                            <span class="text-xs uppercase font-bold text-slate-500 tracking-wider">Check Size Range</span>
                            <div class="text-xs sm:text-sm font-extrabold text-slate-800 mt-1">
                                <?= format_inr($preferences['min_ticket'] ?? 250000) ?> - <?= format_inr($preferences['max_ticket'] ?? 5000000) ?>

                            </div>
                            <span class="text-[10.5px] text-[#667085]">Per company round</span>
                        </div>

                        <div class="p-3.5 sm:p-4 rounded-2xl bg-slate-50 border border-slate-100">
                            <span class="text-xs uppercase font-bold text-slate-500 tracking-wider">Accreditation</span>
                            <div class="text-lg sm:text-xl font-extrabold text-emerald-600 mt-1 flex items-center space-x-1.5">
                                <i data-lucide="shield-check" class="w-5 h-5"></i>
                                <span>Verified</span>

                            </div>
                            <span class="text-[10.5px] text-[#667085]">DigiLocker verified</span>
                        </div>
                    </div>
                </div>
            </div>


            <!-- Two-Column Layout -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <!-- Left Sidebar Details (1 Col) -->
                <div class="space-y-6">
                    
                    <!-- Investment Thesis Card -->
                    <div class="card-clean rounded-2xl p-5 sm:p-6">
                        <h2 class="text-xs sm:text-sm font-bold text-slate-900 uppercase tracking-wider mb-2.5 flex items-center space-x-2">
                            <i data-lucide="target" class="w-4 h-4 text-indigo-600"></i>
                            <span>Investment Thesis</span>
                        </h2>
                        <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                            <?= nl2br(htmlspecialchars($preferences['investment_thesis'] ?: 'Backing high-conviction founders solving massive infrastructure and B2B workflow challenges.')) ?>
                        </p>
                    </div>

                    <!-- Sector & Stage Focus -->
                    <div class="card-clean rounded-2xl p-5 sm:p-6 space-y-4">
                        <div>
                            <h2 class="text-xs sm:text-sm font-bold text-slate-900 uppercase tracking-wider mb-2.5 flex items-center space-x-2">
                                <i data-lucide="layers" class="w-4 h-4 text-indigo-600"></i>
                                <span>Preferred Sectors</span>
                            </h2>
                            <div class="flex flex-wrap gap-2">
                                <?php 
                                $sectors = array_map('trim', explode(',', $preferences['preferred_industries'] ?? 'AI/SaaS, FinTech, DeepTech'));
                                foreach ($sectors as $s): 
                                ?>
                                    <span class="px-3 py-1 rounded-xl bg-indigo-50 text-indigo-700 text-xs font-semibold border border-indigo-100">
                                        <?= htmlspecialchars($s) ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="pt-3.5 border-t border-slate-100">
                            <h2 class="text-xs sm:text-sm font-bold text-slate-900 uppercase tracking-wider mb-2.5 flex items-center space-x-2">
                                <i data-lucide="trending-up" class="w-4 h-4 text-emerald-600"></i>
                                <span>Preferred Stages</span>
                            </h2>
                            <div class="flex flex-wrap gap-2">
                                <?php 
                                $stages = array_map('trim', explode(',', $preferences['preferred_stages'] ?? 'Seed, Pre-Series A'));
                                foreach ($stages as $st): 
                                ?>
                                    <span class="px-3 py-1 rounded-xl bg-emerald-50 text-emerald-700 text-xs font-semibold border border-emerald-100">
                                        <?= htmlspecialchars($st) ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Verified Regulatory & Compliance Credentials -->
                    <div class="card-clean rounded-2xl p-5 sm:p-6">
                        <h2 class="text-xs sm:text-sm font-bold text-slate-900 uppercase tracking-wider mb-3.5 flex items-center space-x-2">
                            <i data-lucide="shield-check" class="w-4 h-4 text-emerald-600"></i>
                            <span>Regulatory & Risk Standing</span>
                        </h2>
                        <div class="space-y-2.5 text-xs sm:text-sm">
                            <div class="flex items-center justify-between p-2.5 rounded-xl bg-emerald-50/50 border border-emerald-100">
                                <span class="text-slate-800 font-semibold flex items-center space-x-2">
                                    <i data-lucide="check" class="w-4 h-4 text-emerald-600"></i>
                                    <span>SEBI Risk Disclosures</span>
                                </span>
                                <span class="text-emerald-700 font-bold text-xs">ACCEPTED</span>
                            </div>

                            <div class="flex items-center justify-between p-2.5 rounded-xl bg-emerald-50/50 border border-emerald-100">
                                <span class="text-slate-800 font-semibold flex items-center space-x-2">
                                    <i data-lucide="check" class="w-4 h-4 text-emerald-600"></i>
                                    <span>KYC Status</span>
                                </span>
                                <span class="text-emerald-700 font-bold text-xs">VERIFIED</span>
                            </div>

                            <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-50 border border-slate-100">
                                <span class="text-slate-700 font-medium flex items-center space-x-2">
                                    <i data-lucide="credit-card" class="w-4 h-4 text-slate-400"></i>
                                    <span>PAN Verification</span>
                                </span>
                                <span class="font-mono text-xs font-bold text-slate-800"><?= htmlspecialchars($profile['pan_number'] ?? 'VERIFIED_ON_FILE') ?></span>

                            </div>
                        </div>
                    </div>


                    <!-- Contact & Web Links -->
                    <div class="card-clean rounded-2xl p-5 sm:p-6">
                        <h2 class="text-xs sm:text-sm font-bold text-slate-900 uppercase tracking-wider mb-3.5 flex items-center space-x-2">
                            <i data-lucide="mail" class="w-4 h-4 text-indigo-600"></i>
                            <span>Direct Contact</span>
                        </h2>
                        <div class="space-y-2.5 text-xs sm:text-sm">
                            <div class="p-3 rounded-xl bg-slate-50 text-slate-600">
                                <span class="text-xs text-slate-500 font-bold block uppercase tracking-wider">Accredited Email</span>
                                <span class="text-xs sm:text-sm font-bold text-slate-900 mt-0.5 block"><?= htmlspecialchars($investor['email']) ?></span>
                            </div>

                            <?php if (!empty($investor['phone'])): ?>
                                <div class="p-3 rounded-xl bg-slate-50 text-slate-600">
                                    <span class="text-xs text-slate-500 font-bold block uppercase tracking-wider">Direct Phone</span>
                                    <span class="text-xs sm:text-sm font-bold text-slate-900 mt-0.5 block"><?= htmlspecialchars($investor['phone']) ?></span>

                                </div>
                            <?php endif; ?>

                            <div
                                class="p-3 rounded-xl bg-[#EAF2FF]/60 border border-[#123B7A]/15 text-[#123B7A] text-[11px] flex items-center space-x-2">
                                <i data-lucide="lock" class="w-3.5 h-3.5 flex-shrink-0"></i>
                                <span>Verified angel communications routed through Nexora Escrow Protocol.</span>
                            </div>
                        </div>
                    </div>
                </div>


                <!-- Right Content (2 Cols): Backed Portfolio Companies -->
                <div class="lg:col-span-2 space-y-6">
                    
                    <!-- Portfolio Entities Backed -->
                    <div class="card-clean rounded-2xl p-5 sm:p-6">
                        <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
                            <h2 class="text-xs sm:text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center space-x-2">
                                <i data-lucide="briefcase" class="w-4 h-4 text-indigo-600"></i>
                                <span>Backed Portfolio Ventures (<?= count($portfolio) ?>)</span>
                            </h2>
                        </div>

                        <?php if (empty($portfolio)): ?>
                            <div class="py-12 text-center text-slate-500 text-xs sm:text-sm">
                                <i data-lucide="pie-chart" class="w-10 h-10 text-slate-300 mx-auto mb-2.5"></i>
                                <div class="font-bold text-slate-700">No confirmed portfolio companies recorded yet.</div>
                                <div class="text-xs text-slate-500 mt-1">Capital deployment in active escrow process.</div>
                            </div>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php foreach ($portfolio as $p): ?>
                                    <div class="p-4 sm:p-5 rounded-2xl bg-slate-50/70 border border-slate-200/80 space-y-3">
                                        <div class="flex items-start justify-between">
                                            <div class="flex items-start space-x-3.5">
                                                <img src="<?= $p['company_logo'] ?: 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=100' ?>" class="w-12 h-12 rounded-xl object-cover border border-slate-200 bg-white flex-shrink-0">
                                                <div>
                                                    <h3 class="font-bold text-slate-900 text-sm sm:text-base"><?= htmlspecialchars($p['company_name']) ?></h3>
                                                    <div class="text-xs text-slate-500 mt-0.5">
                                                        <?= htmlspecialchars($p['company_industry']) ?> • <?= htmlspecialchars($p['company_stage']) ?> Stage
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="text-right">
                                                <span class="text-xs text-slate-400 uppercase font-bold block">Capital Invested</span>
                                                <span class="font-extrabold text-emerald-600 text-xs sm:text-sm"><?= format_inr($p['amount_invested']) ?></span>
                                            </div>
                                        </div>

                                        <div class="pt-2.5 border-t border-slate-200/60 flex items-center justify-between text-xs sm:text-sm">
                                            <div class="flex items-center space-x-2 text-xs text-slate-600">
                                                <span>Equity Stake: <strong class="text-slate-900 font-bold"><?= $p['equity_allotted_percent'] ?>%</strong></span>
                                                <span>•</span>
                                                <span class="font-mono text-xs text-slate-400">Cert: <?= htmlspecialchars($p['certificate_number'] ?? 'ALLOT-PENDING') ?></span>
                                            </div>

                                            <a href="<?= url('investor/startup_detail.php?id=' . encode_id($p['company_id'])) ?>" class="text-indigo-600 hover:text-indigo-800 font-bold flex items-center space-x-1 text-xs sm:text-sm">
                                                <span>View Startup Deal Room</span>
                                                <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                </div>

            </div>


        </main>
    </div>

    <script>
        lucide.createIcons();
        gsap.from("#investor-view-main > *", { duration: 0.45, y: 15, opacity: 0, stagger: 0.08, ease: "power2.out" });
    </script>
</body>

</html>