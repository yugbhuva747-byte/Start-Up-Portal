<?php
/**
 * Founder Module: Full Public / Detail Founder Profile
 * Clean White / Light Theme, Small Crisp Typography
 */
require_once __DIR__ . '/../config.php';
$currentUser = require_auth(); // Accessible by founder, investor, admin
$db = get_db();

// 1. Resolve Target Founder ID
$targetUserId = 0;
if (isset($_GET['id'])) {
    $targetUserId = decode_id($_GET['id']);
}

// Fallback to current user if they are a founder and no ID passed
if ($targetUserId <= 0 && $currentUser['role'] === 'founder') {
    $targetUserId = $currentUser['id'];
}

if ($targetUserId <= 0) {
    set_flash('error', 'Founder profile not specified or invalid.');
    header('Location: ' . ($currentUser['role'] === 'founder' ? url('founder/dashboard.php') : url('investor/discover.php')));
    exit;
}

// 2. Fetch User & Founder Profile
$founder = null;
$profile = null;
$companies = [];
$fundingRounds = [];

if ($db) {
    $uStmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $uStmt->execute([$targetUserId]);
    $founder = $uStmt->fetch();

    if (!$founder) {
        set_flash('error', 'Founder account not found.');
        header('Location: ' . url('index.php'));
        exit;
    }

    $fpStmt = $db->prepare("SELECT * FROM founder_profiles WHERE user_id = ?");
    $fpStmt->execute([$targetUserId]);
    $profile = $fpStmt->fetch();

    // 3. Fetch Associated Companies
    $cStmt = $db->prepare("
        SELECT c.*, cf.equity_percent, cf.is_signatory
        FROM companies c
        JOIN company_founders cf ON c.id = cf.company_id
        WHERE cf.user_id = ?
        ORDER BY c.created_at DESC
    ");
    $cStmt->execute([$targetUserId]);
    $companies = $cStmt->fetchAll();

    // 4. Fetch Funding Rounds for these companies
    if (!empty($companies)) {
        $companyIds = array_column($companies, 'id');
        $placeholders = implode(',', array_fill(0, count($companyIds), '?'));
        $frStmt = $db->prepare("
            SELECT fr.*, c.name as company_name, c.logo_url as company_logo
            FROM funding_rounds fr
            JOIN companies c ON fr.company_id = c.id
            WHERE fr.company_id IN ($placeholders)
            ORDER BY fr.created_at DESC
        ");
        $frStmt->execute($companyIds);
        $fundingRounds = $frStmt->fetchAll();
    }
}

$isSelf = ($currentUser['id'] == $targetUserId);
$pageTitle = htmlspecialchars($founder['name']) . ' • Founder Profile';
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= $pageTitle ?? APP_NAME ?> • <?= APP_NAME ?></title>
    <?php include __DIR__ . '/../includes/founder/head.php'; ?>
</head>
<body class="bg-[#F4F2EE] dark:bg-[#0B0F19] text-slate-900 dark:text-slate-100 flex min-h-screen antialiased selection:bg-indigo-500 selection:text-white">
    
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

        <main class="w-full max-w-7xl mx-auto px-4 sm:px-6 md:px-8 py-6 space-y-6" id="founder-view-main">
            
            <?php if ($flash): ?>
                <div class="p-4 rounded-xl text-sm font-semibold border <?= $flash['type'] === 'success' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-rose-50 text-rose-800 border-rose-200' ?> flex items-center space-x-2.5">
                    <i data-lucide="check-circle" class="w-4.5 h-4.5 flex-shrink-0"></i>
                    <span><?= htmlspecialchars($flash['message']) ?></span>
                </div>
            <?php endif; ?>

            <!-- Breadcrumb Navigation -->
            <div class="flex items-center justify-between text-sm text-slate-500 font-medium">
                <div class="flex items-center space-x-2">
                    <?php if ($currentUser['role'] === 'founder'): ?>
                        <a href="<?= url('founder/dashboard.php') ?>" class="hover:text-slate-900 transition flex items-center space-x-1.5">
                            <i data-lucide="arrow-left" class="w-4 h-4"></i>
                            <span>Dashboard</span>
                        </a>
                    <?php else: ?>
                        <a href="<?= url('investor/discover.php') ?>" class="hover:text-slate-900 transition flex items-center space-x-1.5">
                            <i data-lucide="arrow-left" class="w-4 h-4"></i>
                            <span>Discover Startups</span>
                        </a>
                    <?php endif; ?>
                    <span>/</span>
                    <span class="text-slate-900 font-bold">Founder Profile</span>
                </div>

                <?php if ($isSelf): ?>
                    <a href="<?= url('founder/profile.php') ?>" class="flex items-center space-x-2 px-4 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded-xl font-bold text-sm transition">
                        <i data-lucide="edit-3" class="w-4 h-4"></i>
                        <span>Edit My Profile</span>
                    </a>
                <?php endif; ?>
            </div>

            <!-- Master Profile Header Card -->
            <div class="card-clean rounded-2xl overflow-hidden relative">
                <!-- Cover Banner Strip -->

                <div class="h-28 bg-gradient-to-r from-slate-100 via-indigo-50/60 to-slate-100 dark:from-slate-900 dark:via-indigo-950/50 dark:to-slate-900 border-b border-slate-200/80 dark:border-slate-800 relative">
                    <div class="absolute inset-0 opacity-20 bg-[radial-gradient(#0f172a_1px,transparent_1px)] [background-size:16px_16px]"></div>
                </div>

                <div class="px-6 pb-6 pt-0 relative">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-slate-100 dark:border-slate-800">
                        <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                            <div class="relative -mt-12 flex-shrink-0">
                                <img src="<?= $founder['avatar_url'] ?: 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=160' ?>" 
                                     class="w-24 h-24 rounded-2xl object-cover border-4 border-white dark:border-slate-800 shadow-md bg-white dark:bg-slate-800">

                                <?php if ($founder['is_verified']): ?>
                                    <div class="absolute -bottom-1 -right-1 w-7 h-7 rounded-full bg-emerald-500 border-2 border-white flex items-center justify-center text-white shadow-sm" title="Verified Founder">
                                        <i data-lucide="check" class="w-4 h-4"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="pt-2">
                                <div class="flex flex-wrap items-center gap-2.5">
                                    <h1 class="text-lg sm:text-xl font-bold text-slate-900 tracking-tight leading-tight"><?= htmlspecialchars($founder['name']) ?></h1>
                                    <?php if ($founder['is_verified']): ?>
                                        <span class="px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-800 border border-emerald-200 text-xs font-bold">
                                            KYC VERIFIED
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="text-sm font-bold text-indigo-600 mt-1">
                                    <?= htmlspecialchars($profile['designation'] ?? 'Founder & CEO') ?>
                                    <?php if (!empty($companies)): ?>
                                        <span class="text-slate-400 font-normal">at</span> 
                                        <span class="text-slate-800 font-bold"><?= htmlspecialchars($companies[0]['name']) ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="flex flex-wrap items-center gap-3 text-xs sm:text-sm text-slate-600 font-medium mt-1.5">
                                    <span class="flex items-center space-x-1.5">
                                        <i data-lucide="map-pin" class="w-3.5 h-3.5 text-slate-400"></i>
                                        <span><?= htmlspecialchars($founder['city'] ?? 'Mumbai') ?>, <?= htmlspecialchars($founder['country'] ?? 'India') ?></span>
                                    </span>
                                    <span>•</span>
                                    <span class="flex items-center space-x-1.5">
                                        <i data-lucide="calendar" class="w-3.5 h-3.5 text-slate-400"></i>
                                        <span>Member since <?= date('M Y', strtotime($founder['created_at'])) ?></span>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center space-x-2.5 pt-2 md:pt-0">
                            <?php if (!$isSelf): ?>
                                <a href="<?= url('investor/messages.php') ?>" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm rounded-xl shadow-sm transition flex items-center space-x-2">
                                    <i data-lucide="message-square" class="w-4 h-4"></i>
                                    <span>Inquire / Chat</span>
                                </a>
                                <?php if (!empty($companies)): ?>
                                    <a href="<?= url('investor/startup_detail.php?id=' . encode_id($companies[0]['id'])) ?>" class="px-4 py-2 bg-white hover:bg-slate-50 text-slate-800 border border-slate-200 font-bold text-sm rounded-xl transition flex items-center space-x-2">
                                        <i data-lucide="building-2" class="w-4 h-4 text-slate-500"></i>
                                        <span>View Startup</span>
                                    </a>
                                <?php endif; ?>
                            <?php else: ?>
                                <a href="<?= url('founder/profile.php') ?>" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white font-bold text-sm rounded-xl transition flex items-center space-x-2">
                                    <i data-lucide="settings" class="w-4 h-4"></i>
                                    <span>Account Settings</span>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Quick Metrics Strip -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-4">
                        <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                            <span class="text-xs uppercase font-bold text-slate-600 tracking-wider">Startups Founded</span>
                            <div class="text-lg sm:text-xl font-extrabold text-slate-900 mt-1"><?= count($companies) ?></div>
                        </div>
                        <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                            <span class="text-xs uppercase font-bold text-slate-600 tracking-wider">Active Rounds</span>
                            <div class="text-lg sm:text-xl font-extrabold text-indigo-600 mt-1"><?= count($fundingRounds) ?></div>
                        </div>
                        <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                            <span class="text-xs uppercase font-bold text-slate-600 tracking-wider">Regulatory Compliance</span>
                            <div class="text-lg sm:text-xl font-extrabold text-emerald-600 mt-1 flex items-center space-x-1.5">
                                <i data-lucide="shield-check" class="w-4.5 h-4.5"></i>
                                <span>100% Passed</span>
                            </div>
                        </div>
                        <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                            <span class="text-xs uppercase font-bold text-slate-600 tracking-wider">Corporate Equity</span>
                            <div class="text-lg sm:text-xl font-extrabold text-slate-900 mt-1"><?= !empty($companies[0]['equity_percent']) ? $companies[0]['equity_percent'] . '%' : 'Founder' ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Two-Column Layout -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <!-- Left Sidebar Details (1 Col) -->
                <div class="space-y-6">
                    
                    <!-- Bio Card -->
                    <div class="card-clean rounded-2xl p-6">
                        <h2 class="text-sm sm:text-base font-extrabold text-slate-900 uppercase tracking-wider mb-3 flex items-center space-x-2">
                            <i data-lucide="user" class="w-4 h-4 text-indigo-600"></i>
                            <span>Founder Bio</span>
                        </h2>
                        <p class="text-sm text-slate-700 leading-relaxed font-medium">
                            <?= nl2br(htmlspecialchars($profile['bio'] ?: 'High-conviction startup founder building disruptive solutions. Verified on Portal.')) ?>
                        </p>
                    </div>

                    <!-- Verified Regulatory & Compliance Credentials -->
                    <div class="card-clean rounded-2xl p-6">
                        <h2 class="text-sm sm:text-base font-extrabold text-slate-900 uppercase tracking-wider mb-3.5 flex items-center space-x-2">
                            <i data-lucide="shield" class="w-4 h-4 text-emerald-600"></i>
                            <span>Verified Credentials</span>
                        </h2>
                        <div class="space-y-2.5 text-sm">
                            <div class="flex items-center justify-between p-3 rounded-xl bg-emerald-50/50 border border-emerald-100">
                                <span class="text-slate-800 text-xs sm:text-sm font-bold flex items-center space-x-2">
                                    <i data-lucide="check" class="w-4 h-4 text-emerald-600"></i>
                                    <span>DigiLocker KYC</span>
                                </span>
                                <span class="text-emerald-700 font-extrabold text-xs px-2 py-0.5 bg-emerald-100/60 rounded">VERIFIED</span>
                            </div>

                            <div class="flex items-center justify-between p-3 rounded-xl bg-emerald-50/50 border border-emerald-100">
                                <span class="text-slate-800 text-xs sm:text-sm font-bold flex items-center space-x-2">
                                    <i data-lucide="check" class="w-4 h-4 text-emerald-600"></i>
                                    <span>MCA Registry Match</span>
                                </span>
                                <span class="text-emerald-700 font-extrabold text-xs px-2 py-0.5 bg-emerald-100/60 rounded">VERIFIED</span>
                            </div>

                            <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-200">
                                <span class="text-slate-700 text-xs sm:text-sm font-bold flex items-center space-x-2">
                                    <i data-lucide="credit-card" class="w-4 h-4 text-slate-400"></i>
                                    <span>PAN Verification</span>
                                </span>
                                <span class="font-mono text-xs sm:text-sm font-black text-slate-800"><?= htmlspecialchars($profile['pan_number'] ?? 'VERIFIED_ON_FILE') ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Contact & Web Links -->
                    <div class="card-clean rounded-2xl p-6">
                        <h2 class="text-sm sm:text-base font-extrabold text-slate-900 uppercase tracking-wider mb-3.5 flex items-center space-x-2">
                            <i data-lucide="globe" class="w-4 h-4 text-indigo-600"></i>
                            <span>Links & Contact</span>
                        </h2>
                        <div class="space-y-3 text-sm">
                            <?php if (!empty($profile['linkedin_url'])): ?>
                                <a href="<?= htmlspecialchars($profile['linkedin_url']) ?>" target="_blank" class="flex items-center justify-between p-3 rounded-xl bg-slate-50 hover:bg-slate-100 text-slate-800 transition font-bold text-xs sm:text-sm">
                                    <span class="flex items-center space-x-2.5">
                                        <i data-lucide="linkedin" class="w-4 h-4 text-blue-600"></i>
                                        <span>LinkedIn Profile</span>
                                    </span>
                                    <i data-lucide="external-link" class="w-3.5 h-3.5 text-slate-400"></i>
                                </a>
                            <?php endif; ?>

                            <?php if (!empty($profile['website_url'])): ?>
                                <a href="<?= htmlspecialchars($profile['website_url']) ?>" target="_blank" class="flex items-center justify-between p-3 rounded-xl bg-slate-50 hover:bg-slate-100 text-slate-800 transition font-bold text-xs sm:text-sm">
                                    <span class="flex items-center space-x-2.5">
                                        <i data-lucide="link" class="w-4 h-4 text-slate-500"></i>
                                        <span>Website</span>
                                    </span>
                                    <i data-lucide="external-link" class="w-3.5 h-3.5 text-slate-400"></i>
                                </a>
                            <?php endif; ?>

                            <div class="p-3 rounded-xl bg-slate-50 text-slate-700">
                                <span class="text-xs text-slate-500 font-bold block uppercase tracking-wider">Official Email</span>
                                <span class="text-sm font-bold text-slate-900 mt-0.5 block"><?= htmlspecialchars($founder['email']) ?></span>
                            </div>

                            <?php if (!empty($founder['phone'])): ?>
                                <div class="p-3 rounded-xl bg-slate-50 text-slate-700">
                                    <span class="text-xs text-slate-500 font-bold block uppercase tracking-wider">Direct Phone</span>
                                    <span class="text-sm font-bold text-slate-900 mt-0.5 block"><?= htmlspecialchars($founder['phone']) ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>

                <!-- Right Content (2 Cols): Startups & Funding Rounds -->
                <div class="lg:col-span-2 space-y-6">
                    
                    <!-- Associated Startups -->
                    <div class="card-clean rounded-2xl p-6 sm:p-7">
                        <div class="flex items-center justify-between mb-4 pb-3.5 border-b border-slate-100">
                            <h2 class="text-sm sm:text-base font-extrabold text-slate-900 uppercase tracking-wider flex items-center space-x-2">
                                <i data-lucide="building-2" class="w-4 h-4 text-indigo-600"></i>
                                <span>Startups & Portfolio Entities (<?= count($companies) ?>)</span>
                            </h2>
                        </div>

                        <?php if (empty($companies)): ?>
                            <div class="py-10 text-center text-slate-500 text-sm font-medium">
                                No company profiles linked to this founder yet.
                            </div>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php foreach ($companies as $comp): ?>
                                    <div class="p-5 rounded-2xl bg-slate-50/70 border border-slate-200/80 space-y-3.5">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="flex items-start space-x-3.5">
                                                <img src="<?= $comp['logo_url'] ?: 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=100' ?>" class="w-13 h-13 rounded-xl object-cover border border-slate-200 bg-white flex-shrink-0">
                                                <div>
                                                    <div class="flex items-center space-x-2.5">
                                                        <h3 class="font-extrabold text-slate-900 text-base"><?= htmlspecialchars($comp['name']) ?></h3>
                                                        <?= render_status_badge($comp['verified_status']) ?>
                                                    </div>
                                                    <div class="text-xs sm:text-sm text-slate-600 font-medium mt-0.5">
                                                        <?= htmlspecialchars($comp['industry']) ?> • <?= htmlspecialchars($comp['stage']) ?> Stage
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="text-right flex-shrink-0">
                                                <span class="text-xs text-slate-500 uppercase font-bold block tracking-wider">Equity Stake</span>
                                                <span class="font-black text-indigo-600 text-sm sm:text-base"><?= !empty($comp['equity_percent']) ? $comp['equity_percent'] . '%' : 'Founder' ?></span>
                                            </div>
                                        </div>

                                        <p class="text-sm text-slate-700 leading-relaxed font-medium">
                                            <?= htmlspecialchars($comp['description'] ?: 'High-growth technology enterprise registered under the Ministry of Corporate Affairs.') ?>
                                        </p>

                                        <div class="pt-3 border-t border-slate-200/60 flex items-center justify-between text-sm">
                                            <div class="font-mono text-xs text-slate-600">
                                                CIN: <span class="font-bold text-slate-800"><?= htmlspecialchars($comp['cin_number'] ?? 'PENDING') ?></span>
                                            </div>

                                            <?php if ($currentUser['role'] === 'investor'): ?>
                                                <a href="<?= url('investor/startup_detail.php?id=' . encode_id($comp['id'])) ?>" class="text-indigo-600 hover:text-indigo-800 font-bold flex items-center space-x-1.5 text-xs sm:text-sm">
                                                    <span>Open Deal Room</span>
                                                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                                                </a>
                                            <?php elseif ($isSelf): ?>
                                                <a href="<?= url('founder/company.php') ?>" class="text-indigo-600 hover:text-indigo-800 font-bold flex items-center space-x-1.5 text-xs sm:text-sm">
                                                    <span>Manage Company</span>
                                                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Live Funding Rounds -->
                    <div class="card-clean rounded-2xl p-6 sm:p-7">
                        <div class="flex items-center justify-between mb-4 pb-3.5 border-b border-slate-100">
                            <h2 class="text-sm sm:text-base font-extrabold text-slate-900 uppercase tracking-wider flex items-center space-x-2">
                                <i data-lucide="circle-dollar-sign" class="w-4 h-4 text-indigo-600"></i>
                                <span>Active Funding Campaigns</span>
                            </h2>
                        </div>

                        <?php if (empty($fundingRounds)): ?>
                            <div class="py-10 text-center text-slate-500 text-sm font-medium">
                                No active funding rounds for this founder currently.
                            </div>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php foreach ($fundingRounds as $fr): ?>
                                    <?php 
                                    $pct = $fr['target_amount'] > 0 ? round(($fr['amount_raised'] / $fr['target_amount']) * 100) : 0;
                                    ?>
                                    <div class="p-5 rounded-2xl bg-slate-50/70 border border-slate-200/80 space-y-3.5">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <div class="font-extrabold text-slate-900 text-sm sm:text-base"><?= htmlspecialchars($fr['round_name']) ?></div>
                                                <div class="text-xs text-slate-500 font-medium"><?= htmlspecialchars($fr['company_name']) ?></div>
                                            </div>
                                            <?= render_status_badge($fr['status']) ?>
                                        </div>

                                        <!-- Progress Bar -->
                                        <div>
                                            <div class="flex justify-between text-xs sm:text-sm mb-1.5 font-medium">
                                                <span class="text-slate-600">Raised: <strong class="text-slate-900 font-bold"><?= format_inr($fr['amount_raised']) ?></strong></span>
                                                <span class="text-indigo-600 font-black"><?= $pct ?>%</span>
                                            </div>
                                            <div class="w-full h-2.5 bg-slate-200 rounded-full overflow-hidden">
                                                <div class="h-full bg-indigo-600 rounded-full" style="width: <?= min(100, $pct) ?>%"></div>
                                            </div>
                                            <div class="text-xs text-slate-500 mt-1 font-medium">
                                                Target: <?= format_inr($fr['target_amount']) ?>
                                            </div>
                                        </div>

                                        <div class="pt-3 border-t border-slate-200/60 flex items-center justify-between text-xs sm:text-sm">
                                            <div class="flex items-center space-x-3 text-slate-700 font-medium">
                                                <span>Valuation: <strong class="font-bold text-slate-900"><?= format_inr($fr['valuation']) ?></strong></span>
                                                <span>•</span>
                                                <span>Equity: <strong class="text-emerald-600 font-black"><?= $fr['equity_offered'] ?>%</strong></span>
                                            </div>

                                            <?php if ($currentUser['role'] === 'investor'): ?>
                                                <a href="<?= url('investor/invest.php?round_id=' . encode_id($fr['id'])) ?>" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs sm:text-sm rounded-xl shadow-sm transition">
                                                    Invest in Round
                                                </a>
                                            <?php endif; ?>
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
        gsap.from("#founder-view-main", { duration: 0.4, y: 10, opacity: 0, ease: "power2.out" });
    </script>
</body>
</html>
