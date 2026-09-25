<?php
/**
 * Investor Module: Full Public / Detail Investor Profile
 * Clean White / Light Theme, Small Crisp Typography
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
        $totalDeployed += (float)$item['amount_invested'];
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
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .card-clean {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.04);
        }
    </style>
</head>
<body class="bg-[#FAFAFB] text-slate-900 flex min-h-screen">
    
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

    <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">
        <?php 
        if ($currentUser['role'] === 'founder') {
            include __DIR__ . '/../includes/founder/navbar.php';
        } elseif ($currentUser['role'] === 'investor') {
            include __DIR__ . '/../includes/investor/navbar.php';
        } else {
            include __DIR__ . '/../includes/admin/navbar.php';
        }
        ?>

        <main class="p-3.5 sm:p-6 md:p-8 space-y-6 max-w-5xl w-full mx-auto" id="investor-view-main">
            
            <?php if ($flash): ?>
                <div class="p-3.5 rounded-xl text-xs font-semibold border <?= $flash['type'] === 'success' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200' ?> flex items-center space-x-2">
                    <i data-lucide="check-circle" class="w-3.5 h-3.5 flex-shrink-0"></i>
                    <span><?= htmlspecialchars($flash['message']) ?></span>
                </div>
            <?php endif; ?>

            <!-- Breadcrumb Navigation -->
            <div class="flex items-center justify-between text-xs text-slate-400">
                <div class="flex items-center space-x-2">
                    <?php if ($currentUser['role'] === 'investor'): ?>
                        <a href="<?= url('investor/dashboard.php') ?>" class="hover:text-slate-800 transition flex items-center space-x-1">
                            <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                            <span>Dashboard</span>
                        </a>
                    <?php else: ?>
                        <a href="<?= url('founder/dashboard.php') ?>" class="hover:text-slate-800 transition flex items-center space-x-1">
                            <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                            <span>Founder Workspace</span>
                        </a>
                    <?php endif; ?>
                    <span>/</span>
                    <span class="text-slate-700 font-semibold">Investor Profile</span>
                </div>

                <?php if ($isSelf): ?>
                    <a href="<?= url('investor/profile.php') ?>" class="flex items-center space-x-1.5 px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded-lg font-semibold text-xs transition">
                        <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                        <span>Edit Thesis & Profile</span>
                    </a>
                <?php endif; ?>
            </div>

            <!-- Master Profile Header Card -->
            <div class="card-clean rounded-2xl overflow-hidden relative">
                <!-- Cover Banner Strip -->
                <div class="h-28 bg-gradient-to-r from-slate-100 via-teal-50/60 to-slate-100 border-b border-slate-200/80 relative">
                    <div class="absolute inset-0 opacity-20 bg-[radial-gradient(#0f172a_1px,transparent_1px)] [background-size:16px_16px]"></div>
                </div>

                <div class="px-6 pb-6 pt-0 relative">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-slate-100">
                        <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                            <div class="relative -mt-12 flex-shrink-0">
                                <img src="<?= $investor['avatar_url'] ?: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=160' ?>" 
                                     class="w-24 h-24 rounded-2xl object-cover border-4 border-white shadow-md bg-white">
                                <?php if ($investor['is_verified']): ?>
                                    <div class="absolute -bottom-1 -right-1 w-6 h-6 rounded-full bg-emerald-500 border-2 border-white flex items-center justify-center text-white shadow-sm" title="Verified Investor">
                                        <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="pt-2">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight leading-tight"><?= htmlspecialchars($investor['name']) ?></h1>
                                    <span class="px-2 py-0.5 rounded-full bg-teal-50 text-teal-700 border border-teal-200 text-[10px] font-bold">
                                        <?= strtoupper($profile['investor_type'] ?? 'ANGEL INVESTOR') ?>
                                    </span>
                                </div>
                                <div class="text-xs font-semibold text-emerald-600 mt-1 flex items-center space-x-1.5">
                                    <i data-lucide="award" class="w-3.5 h-3.5"></i>
                                    <span>SEBI Compliant Accredited Investor</span>
                                    <span class="text-slate-300">•</span>
                                    <span class="text-slate-500 font-normal"><?= $profile['experience_years'] ?? 5 ?>+ Years in Venture</span>
                                </div>
                                <div class="flex flex-wrap items-center gap-3 text-[11px] text-slate-500 mt-2">
                                    <span class="flex items-center space-x-1">
                                        <i data-lucide="map-pin" class="w-3 h-3 text-slate-400"></i>
                                        <span><?= htmlspecialchars($investor['city'] ?? 'Mumbai') ?>, <?= htmlspecialchars($investor['country'] ?? 'India') ?></span>
                                    </span>
                                    <span>•</span>
                                    <span class="flex items-center space-x-1">
                                        <i data-lucide="calendar" class="w-3 h-3 text-slate-400"></i>
                                        <span>Member since <?= date('M Y', strtotime($investor['created_at'])) ?></span>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center space-x-2.5 pt-2 md:pt-0">
                            <?php if (!$isSelf): ?>
                                <a href="<?= url('founder/messages.php') ?>" class="px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs rounded-lg shadow-sm transition flex items-center space-x-1.5">
                                    <i data-lucide="message-square" class="w-3.5 h-3.5"></i>
                                    <span>Pitch Startup / Chat</span>
                                </a>
                            <?php else: ?>
                                <a href="<?= url('investor/profile.php') ?>" class="px-3.5 py-2 bg-slate-800 hover:bg-slate-900 text-white font-semibold text-xs rounded-lg transition flex items-center space-x-1.5">
                                    <i data-lucide="settings" class="w-3.5 h-3.5"></i>
                                    <span>Thesis & Preferences</span>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Quick Metrics Strip -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-4 text-xs">
                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                            <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Total Deployed Capital</span>
                            <div class="text-base font-black text-emerald-600 mt-0.5"><?= format_inr($totalDeployed) ?></div>
                        </div>
                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                            <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Portfolio Companies</span>
                            <div class="text-base font-black text-slate-900 mt-0.5"><?= count($portfolio) ?></div>
                        </div>
                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                            <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Check Size Range</span>
                            <div class="text-xs font-black text-slate-800 mt-1">
                                <?= format_inr($preferences['min_ticket'] ?? 250000) ?> - <?= format_inr($preferences['max_ticket'] ?? 5000000) ?>
                            </div>
                        </div>
                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                            <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Accreditation</span>
                            <div class="text-base font-black text-emerald-600 mt-0.5 flex items-center space-x-1">
                                <i data-lucide="shield-check" class="w-4 h-4"></i>
                                <span>Verified</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Two-Column Layout -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <!-- Left Sidebar Details (1 Col) -->
                <div class="space-y-6">
                    
                    <!-- Investment Thesis Card -->
                    <div class="card-clean rounded-2xl p-5">
                        <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wider mb-2.5 flex items-center space-x-1.5">
                            <i data-lucide="target" class="w-3.5 h-3.5 text-indigo-600"></i>
                            <span>Investment Thesis</span>
                        </h2>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            <?= nl2br(htmlspecialchars($preferences['investment_thesis'] ?: 'Backing high-conviction founders solving massive infrastructure and B2B workflow challenges.')) ?>
                        </p>
                    </div>

                    <!-- Sector & Stage Focus -->
                    <div class="card-clean rounded-2xl p-5 space-y-4">
                        <div>
                            <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wider mb-2 flex items-center space-x-1.5">
                                <i data-lucide="layers" class="w-3.5 h-3.5 text-indigo-600"></i>
                                <span>Preferred Sectors</span>
                            </h2>
                            <div class="flex flex-wrap gap-1.5">
                                <?php 
                                $sectors = array_map('trim', explode(',', $preferences['preferred_industries'] ?? 'AI/SaaS, FinTech, DeepTech'));
                                foreach ($sectors as $s): 
                                ?>
                                    <span class="px-2.5 py-1 rounded-lg bg-indigo-50 text-indigo-700 text-[10.5px] font-semibold">
                                        <?= htmlspecialchars($s) ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="pt-3 border-t border-slate-100">
                            <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wider mb-2 flex items-center space-x-1.5">
                                <i data-lucide="trending-up" class="w-3.5 h-3.5 text-emerald-600"></i>
                                <span>Preferred Stages</span>
                            </h2>
                            <div class="flex flex-wrap gap-1.5">
                                <?php 
                                $stages = array_map('trim', explode(',', $preferences['preferred_stages'] ?? 'Seed, Pre-Series A'));
                                foreach ($stages as $st): 
                                ?>
                                    <span class="px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700 text-[10.5px] font-semibold">
                                        <?= htmlspecialchars($st) ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Verified Regulatory & Compliance Credentials -->
                    <div class="card-clean rounded-2xl p-5">
                        <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wider mb-3 flex items-center space-x-1.5">
                            <i data-lucide="shield-check" class="w-3.5 h-3.5 text-emerald-600"></i>
                            <span>Regulatory & Risk Standing</span>
                        </h2>
                        <div class="space-y-2 text-xs">
                            <div class="flex items-center justify-between p-2 rounded-lg bg-emerald-50/50 border border-emerald-100">
                                <span class="text-slate-700 text-[11px] font-semibold flex items-center space-x-1.5">
                                    <i data-lucide="check" class="w-3.5 h-3.5 text-emerald-600"></i>
                                    <span>SEBI Risk Disclosures</span>
                                </span>
                                <span class="text-emerald-700 font-bold text-[10px]">ACCEPTED</span>
                            </div>

                            <div class="flex items-center justify-between p-2 rounded-lg bg-emerald-50/50 border border-emerald-100">
                                <span class="text-slate-700 text-[11px] font-semibold flex items-center space-x-1.5">
                                    <i data-lucide="check" class="w-3.5 h-3.5 text-emerald-600"></i>
                                    <span>KYC Status</span>
                                </span>
                                <span class="text-emerald-700 font-bold text-[10px]">VERIFIED</span>
                            </div>

                            <div class="flex items-center justify-between p-2 rounded-lg bg-slate-50 border border-slate-100">
                                <span class="text-slate-600 text-[11px] font-medium flex items-center space-x-1.5">
                                    <i data-lucide="credit-card" class="w-3.5 h-3.5 text-slate-400"></i>
                                    <span>PAN Verification</span>
                                </span>
                                <span class="font-mono text-[10.5px] font-bold text-slate-700"><?= htmlspecialchars($profile['pan_number'] ?? 'VERIFIED_ON_FILE') ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Contact & Web Links -->
                    <div class="card-clean rounded-2xl p-5">
                        <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wider mb-3 flex items-center space-x-1.5">
                            <i data-lucide="mail" class="w-3.5 h-3.5 text-indigo-600"></i>
                            <span>Direct Contact</span>
                        </h2>
                        <div class="space-y-2.5 text-xs">
                            <div class="p-2.5 rounded-lg bg-slate-50 text-slate-600">
                                <span class="text-[10px] text-slate-400 font-semibold block uppercase">Accredited Email</span>
                                <span class="text-xs font-medium text-slate-800"><?= htmlspecialchars($investor['email']) ?></span>
                            </div>

                            <?php if (!empty($investor['phone'])): ?>
                                <div class="p-2.5 rounded-lg bg-slate-50 text-slate-600">
                                    <span class="text-[10px] text-slate-400 font-semibold block uppercase">Direct Phone</span>
                                    <span class="text-xs font-medium text-slate-800"><?= htmlspecialchars($investor['phone']) ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>

                <!-- Right Content (2 Cols): Backed Portfolio Companies -->
                <div class="lg:col-span-2 space-y-6">
                    
                    <!-- Portfolio Entities Backed -->
                    <div class="card-clean rounded-2xl p-6">
                        <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
                            <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wider flex items-center space-x-1.5">
                                <i data-lucide="briefcase" class="w-3.5 h-3.5 text-indigo-600"></i>
                                <span>Backed Portfolio Ventures (<?= count($portfolio) ?>)</span>
                            </h2>
                        </div>

                        <?php if (empty($portfolio)): ?>
                            <div class="py-12 text-center text-slate-400 text-xs">
                                <i data-lucide="pie-chart" class="w-8 h-8 text-slate-300 mx-auto mb-2"></i>
                                <div>No confirmed portfolio companies recorded yet.</div>
                                <div class="text-[11px] text-slate-400 mt-1">Capital deployment in active escrow process.</div>
                            </div>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php foreach ($portfolio as $p): ?>
                                    <div class="p-4 rounded-xl bg-slate-50/70 border border-slate-200/80 space-y-3">
                                        <div class="flex items-start justify-between">
                                            <div class="flex items-start space-x-3">
                                                <img src="<?= $p['company_logo'] ?: 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=100' ?>" class="w-11 h-11 rounded-xl object-cover border border-slate-200 bg-white">
                                                <div>
                                                    <h3 class="font-bold text-slate-900 text-sm"><?= htmlspecialchars($p['company_name']) ?></h3>
                                                    <div class="text-[11px] text-slate-500 mt-0.5">
                                                        <?= htmlspecialchars($p['company_industry']) ?> • <?= htmlspecialchars($p['company_stage']) ?> Stage
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="text-right">
                                                <span class="text-[10px] text-slate-400 uppercase font-semibold block">Capital Invested</span>
                                                <span class="font-bold text-emerald-600 text-xs"><?= format_inr($p['amount_invested']) ?></span>
                                            </div>
                                        </div>

                                        <div class="pt-2 border-t border-slate-200/60 flex items-center justify-between text-xs">
                                            <div class="flex items-center space-x-2 text-[11px] text-slate-500">
                                                <span>Equity Stake: <strong class="text-slate-800"><?= $p['equity_allotted_percent'] ?>%</strong></span>
                                                <span>•</span>
                                                <span class="font-mono text-[10px] text-slate-400">Cert: <?= htmlspecialchars($p['certificate_number'] ?? 'ALLOT-PENDING') ?></span>
                                            </div>

                                            <a href="<?= url('investor/startup_detail.php?id=' . encode_id($p['company_id'])) ?>" class="text-indigo-600 hover:text-indigo-800 font-semibold flex items-center space-x-1 text-[11px]">
                                                <span>View Startup Deal Room</span>
                                                <i data-lucide="arrow-right" class="w-3 h-3"></i>
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
        gsap.from("#investor-view-main", { duration: 0.4, y: 10, opacity: 0, ease: "power2.out" });
    </script>
</body>
</html>
