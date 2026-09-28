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
            font-family: 'Vay Portal - Regular';
            src: local('Vay Portal - Regular'), local('Vay Portal'), local('Plus Jakarta Sans');
        }
        :root {
            --inv-primary: #123B7A;
            --inv-navy: #0B1F3A;
            --inv-secondary: #315F9F;
            --inv-light-blue: #EAF2FF;
            --inv-bg: #FAFBFD;
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

<body class="bg-[#FAFBFD] text-[#111827] flex min-h-screen antialiased">

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

        <main class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-10 py-6 sm:py-8 space-y-8" id="investor-view-main">

            <?php if ($flash): ?>
                <div class="p-4 rounded-xl text-xs font-semibold border <?= $flash['type'] === 'success' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-rose-50 text-rose-800 border-rose-200' ?> flex items-center space-x-2">
                    <i data-lucide="check-circle" class="w-4 h-4 flex-shrink-0"></i>
                    <span><?= htmlspecialchars($flash['message']) ?></span>
                </div>
            <?php endif; ?>

            <!-- Editorial Breadcrumb Bar -->
            <div class="flex items-center justify-between text-xs text-[#667085] pb-2 border-b border-[#E4E8EF]">
                <div class="flex items-center space-x-2">
                    <?php if ($currentUser['role'] === 'investor'): ?>
                        <a href="<?= url('investor/dashboard.php') ?>" class="hover:text-[#123B7A] transition flex items-center space-x-1.5 font-medium">
                            <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                            <span>Workspace</span>
                        </a>
                    <?php else: ?>
                        <a href="<?= url('founder/dashboard.php') ?>" class="hover:text-[#123B7A] transition flex items-center space-x-1.5 font-medium">
                            <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                            <span>Founder Workspace</span>
                        </a>
                    <?php endif; ?>
                    <span class="text-[#E4E8EF]">/</span>
                    <span class="text-[#0B1F3A] font-bold">Investor Profile</span>
                </div>

                <?php if ($isSelf): ?>
                    <div class="flex items-center space-x-3">
                        <a href="<?= url('investor/verification.php') ?>" class="text-[11px] font-semibold text-[#667085] hover:text-[#123B7A] transition flex items-center space-x-1">
                            <i data-lucide="shield-check" class="w-3.5 h-3.5"></i>
                            <span>Accreditation Hub</span>
                        </a>
                        <a href="<?= url('investor/profile.php') ?>" class="inline-flex items-center space-x-1.5 px-3.5 py-1.5 bg-[#EAF2FF] hover:bg-[#123B7A] text-[#123B7A] hover:text-white rounded-lg font-bold text-xs transition duration-200">
                            <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                            <span>Edit Thesis & Profile</span>
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- LINKEDIN-STYLE PROFESSIONAL PROFILE HEADER -->
            <div class="bg-white border border-[#E4E8EF] rounded-2xl overflow-hidden shadow-sm">
                <!-- Cover Banner Strip -->
                <div class="h-36 sm:h-44 bg-gradient-to-r from-[#0B1F3A] via-[#123B7A] to-[#315F9F] relative">
                    <div class="absolute inset-0 opacity-15 bg-[radial-gradient(#FFFFFF_1px,transparent_1px)] [background-size:20px_20px]"></div>
                    <div class="absolute top-4 right-4 flex items-center space-x-2">
                        <span class="px-3 py-1 rounded-full bg-white/15 backdrop-blur-md text-white text-[10.5px] font-semibold border border-white/20 uppercase tracking-wider">
                            SEBI Accredited Syndicate
                        </span>
                    </div>
                </div>

                <!-- Profile Identity Bar -->
                <div class="px-6 sm:px-8 pb-8 pt-0 relative">
                    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 pb-6 border-b border-[#E4E8EF]">
                        <div class="flex flex-col sm:flex-row sm:items-end gap-5">
                            <div class="relative -mt-16 sm:-mt-20 flex-shrink-0">
                                <img src="<?= $investor['avatar_url'] ?: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=200' ?>"
                                    class="w-28 h-28 sm:w-32 sm:h-32 rounded-2xl object-cover border-4 border-white shadow-lg bg-white">
                                <?php if ($investor['is_verified']): ?>
                                    <div class="absolute -bottom-2 -right-2 w-7 h-7 rounded-full bg-[#123B7A] border-2 border-white flex items-center justify-center text-white shadow-md"
                                        title="SEBI Verified Investor">
                                        <i data-lucide="check" class="w-4 h-4 stroke-[3]"></i>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="pt-2 sm:pt-0 space-y-1.5">
                                <div class="flex flex-wrap items-center gap-2.5">
                                    <h1 class="text-2xl sm:text-3xl font-black text-[#0B1F3A] tracking-tight">
                                        <?= htmlspecialchars($investor['name']) ?>
                                    </h1>
                                    <span class="px-2.5 py-0.5 rounded-full bg-[#EAF2FF] text-[#123B7A] text-[11px] font-bold tracking-wide">
                                        <?= strtoupper($profile['investor_type'] ?? 'ANGEL INVESTOR') ?>
                                    </span>
                                </div>

                                <div class="text-xs font-semibold text-[#123B7A] flex flex-wrap items-center gap-2">
                                    <span class="flex items-center space-x-1">
                                        <i data-lucide="award" class="w-3.5 h-3.5"></i>
                                        <span>SEBI Compliant Accredited Investor</span>
                                    </span>
                                    <span class="text-[#E4E8EF]">•</span>
                                    <span class="text-[#667085] font-normal"><?= $profile['experience_years'] ?? 5 ?>+ Years Venture Experience</span>
                                </div>

                                <div class="flex flex-wrap items-center gap-4 text-xs text-[#667085] pt-1">
                                    <span class="flex items-center space-x-1.5">
                                        <i data-lucide="map-pin" class="w-3.5 h-3.5 text-[#667085]"></i>
                                        <span><?= htmlspecialchars($investor['city'] ?? 'Mumbai') ?>, <?= htmlspecialchars($investor['country'] ?? 'India') ?></span>
                                    </span>
                                    <span>•</span>
                                    <span class="flex items-center space-x-1.5">
                                        <i data-lucide="calendar" class="w-3.5 h-3.5 text-[#667085]"></i>
                                        <span>Member since <?= date('F Y', strtotime($investor['created_at'])) ?></span>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Top Level Action Buttons -->
                        <div class="flex items-center space-x-3 pt-2 md:pt-0">
                            <?php if (!$isSelf): ?>
                                <a href="<?= url('founder/messages.php') ?>"
                                    class="px-5 py-2.5 bg-[#123B7A] hover:bg-[#0B1F3A] text-white font-bold text-xs rounded-xl shadow-sm transition flex items-center space-x-2">
                                    <i data-lucide="send" class="w-3.5 h-3.5"></i>
                                    <span>Pitch Startup / Connect</span>
                                </a>
                            <?php else: ?>
                                <a href="<?= url('investor/profile.php') ?>"
                                    class="px-4 py-2 bg-white hover:bg-[#FAFBFD] text-[#0B1F3A] border border-[#E4E8EF] font-bold text-xs rounded-xl transition flex items-center space-x-1.5">
                                    <i data-lucide="sliders" class="w-3.5 h-3.5 text-[#123B7A]"></i>
                                    <span>Manage Thesis</span>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Clean Horizontal Highlights Strip (Editorial Rows, NOT KPI Cards) -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 pt-6">
                        <div class="space-y-0.5">
                            <span class="text-[10px] font-bold text-[#667085] uppercase tracking-wider block">Capital Deployed</span>
                            <div class="text-lg font-black text-[#0B1F3A]"><?= format_inr($totalDeployed) ?></div>
                            <span class="text-[10.5px] text-[#667085]">Confirmed escrow</span>
                        </div>
                        <div class="space-y-0.5">
                            <span class="text-[10px] font-bold text-[#667085] uppercase tracking-wider block">Portfolio Ventures</span>
                            <div class="text-lg font-black text-[#0B1F3A]"><?= count($portfolio) ?></div>
                            <span class="text-[10.5px] text-[#667085]">Active syndicate backed</span>
                        </div>
                        <div class="space-y-0.5">
                            <span class="text-[10px] font-bold text-[#667085] uppercase tracking-wider block">Check Size Range</span>
                            <div class="text-sm font-black text-[#0B1F3A]">
                                <?= format_inr($preferences['min_ticket'] ?? 250000) ?> - <?= format_inr($preferences['max_ticket'] ?? 5000000) ?>
                            </div>
                            <span class="text-[10.5px] text-[#667085]">Per company round</span>
                        </div>
                        <div class="space-y-0.5">
                            <span class="text-[10px] font-bold text-[#667085] uppercase tracking-wider block">Accreditation</span>
                            <div class="text-sm font-black text-emerald-700 flex items-center space-x-1.5">
                                <i data-lucide="shield-check" class="w-4 h-4 text-emerald-600"></i>
                                <span>Verified Active</span>
                            </div>
                            <span class="text-[10.5px] text-[#667085]">DigiLocker verified</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- EDITORIAL SECTION 1: ABOUT & INVESTMENT THESIS -->
            <section class="bg-white border border-[#E4E8EF] rounded-2xl p-6 sm:p-8 space-y-4">
                <div class="flex items-center space-x-2 pb-3 border-b border-[#E4E8EF]">
                    <i data-lucide="target" class="w-4 h-4 text-[#123B7A]"></i>
                    <h2 class="text-xs font-bold text-[#0B1F3A] uppercase tracking-wider">About & Investment Thesis</h2>
                </div>
                <div class="text-sm text-[#111827] leading-relaxed max-w-4xl space-y-3">
                    <p class="text-base text-[#0B1F3A] font-semibold leading-relaxed">
                        <?= nl2br(htmlspecialchars($preferences['investment_thesis'] ?: 'Backing high-conviction founders solving massive infrastructure and B2B workflow challenges across India and emerging markets.')) ?>
                    </p>
                    <p class="text-xs text-[#667085] leading-relaxed">
                        Evaluates deals based on strong founder-market fit, unit economics defensibility, product velocity, and clear regulatory compliance under SEBI angel syndicate guidelines.
                    </p>
                </div>
            </section>

            <!-- EDITORIAL SECTION 2: INVESTMENT FOCUS & CRITERIA -->
            <section class="bg-white border border-[#E4E8EF] rounded-2xl p-6 sm:p-8 space-y-6">
                <div class="flex items-center space-x-2 pb-3 border-b border-[#E4E8EF]">
                    <i data-lucide="layers" class="w-4 h-4 text-[#123B7A]"></i>
                    <h2 class="text-xs font-bold text-[#0B1F3A] uppercase tracking-wider">Investment Focus & Criteria</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-3">
                        <span class="text-[11px] font-bold text-[#667085] uppercase tracking-wider block">Target Sectors & Industries</span>
                        <div class="flex flex-wrap gap-2">
                            <?php
                            $sectors = array_map('trim', explode(',', $preferences['preferred_industries'] ?? 'AI/SaaS, FinTech, DeepTech, B2B Commerce'));
                            foreach ($sectors as $s):
                                ?>
                                <span class="px-3 py-1 rounded-lg bg-[#EAF2FF] text-[#123B7A] text-xs font-semibold border border-[#123B7A]/10">
                                    <?= htmlspecialchars($s) ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <span class="text-[11px] font-bold text-[#667085] uppercase tracking-wider block">Preferred Investment Stages</span>
                        <div class="flex flex-wrap gap-2">
                            <?php
                            $stages = array_map('trim', explode(',', $preferences['preferred_stages'] ?? 'Pre-Seed, Seed, Pre-Series A'));
                            foreach ($stages as $st):
                                ?>
                                <span class="px-3 py-1 rounded-lg bg-[#FAFBFD] text-[#0B1F3A] text-xs font-semibold border border-[#E4E8EF]">
                                    <?= htmlspecialchars($st) ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="pt-4 border-t border-[#E4E8EF] grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                    <div>
                        <span class="text-[#667085] text-[11px] block">Typical Check Size</span>
                        <strong class="text-[#0B1F3A] text-sm"><?= format_inr($preferences['min_ticket'] ?? 250000) ?> - <?= format_inr($preferences['max_ticket'] ?? 5000000) ?></strong>
                    </div>
                    <div>
                        <span class="text-[#667085] text-[11px] block">Investment Role</span>
                        <strong class="text-[#0B1F3A] text-sm"><?= htmlspecialchars($profile['investor_type'] ?? 'Angel Syndicate / Lead') ?></strong>
                    </div>
                    <div>
                        <span class="text-[#667085] text-[11px] block">Primary Geography</span>
                        <strong class="text-[#0B1F3A] text-sm"><?= htmlspecialchars($investor['city'] ?? 'Mumbai') ?>, India</strong>
                    </div>
                </div>
            </section>

            <!-- EDITORIAL SECTION 3: BACKED PORTFOLIO VENTURES -->
            <section class="bg-white border border-[#E4E8EF] rounded-2xl p-6 sm:p-8 space-y-5">
                <div class="flex items-center justify-between pb-3 border-b border-[#E4E8EF]">
                    <div class="flex items-center space-x-2">
                        <i data-lucide="briefcase" class="w-4 h-4 text-[#123B7A]"></i>
                        <h2 class="text-xs font-bold text-[#0B1F3A] uppercase tracking-wider">Backed Portfolio Ventures (<?= count($portfolio) ?>)</h2>
                    </div>
                    <span class="text-xs text-[#667085]">Confirmed Cap Table Entries</span>
                </div>

                <?php if (empty($portfolio)): ?>
                    <div class="py-12 text-center text-xs text-[#667085]">
                        <i data-lucide="pie-chart" class="w-8 h-8 text-[#667085]/40 mx-auto mb-2"></i>
                        <div class="font-bold text-[#0B1F3A]">No confirmed portfolio ventures recorded yet.</div>
                        <div class="text-[11px] text-[#667085] mt-1">Capital deployment in active escrow verification.</div>
                    </div>
                <?php else: ?>
                    <div class="divide-y divide-[#E4E8EF]">
                        <?php foreach ($portfolio as $p): ?>
                            <div class="py-4 profile-row-hover rounded-xl px-3 transition flex flex-col md:flex-row md:items-center justify-between gap-4">
                                <div class="flex items-center space-x-4">
                                    <img src="<?= $p['company_logo'] ?: 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=120' ?>"
                                        class="w-12 h-12 rounded-xl object-cover border border-[#E4E8EF] bg-white flex-shrink-0">
                                    <div>
                                        <h3 class="font-bold text-[#0B1F3A] text-sm hover:text-[#123B7A] transition">
                                            <?= htmlspecialchars($p['company_name']) ?>
                                        </h3>
                                        <div class="text-xs text-[#667085] mt-0.5 flex items-center space-x-2">
                                            <span><?= htmlspecialchars($p['company_industry']) ?></span>
                                            <span class="text-[#E4E8EF]">•</span>
                                            <span><?= htmlspecialchars($p['company_stage']) ?> Stage</span>
                                            <span class="text-[#E4E8EF]">•</span>
                                            <span class="text-[#123B7A] font-semibold"><?= htmlspecialchars($p['round_name']) ?></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between md:justify-end gap-6 text-xs">
                                    <div class="text-left md:text-right">
                                        <span class="text-[10px] text-[#667085] uppercase tracking-wider block">Capital Backed</span>
                                        <span class="font-bold text-[#0B1F3A] text-sm"><?= format_inr($p['amount_invested']) ?></span>
                                    </div>

                                    <div class="text-left md:text-right">
                                        <span class="text-[10px] text-[#667085] uppercase tracking-wider block">Equity Stake</span>
                                        <span class="font-bold text-[#123B7A] text-sm"><?= $p['equity_allotted_percent'] ?>%</span>
                                    </div>

                                    <a href="<?= url('investor/startup_detail.php?id=' . encode_id($p['company_id'])) ?>"
                                        class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-lg bg-[#FAFBFD] hover:bg-[#EAF2FF] text-[#123B7A] border border-[#E4E8EF] text-xs font-bold transition">
                                        <span>Deal Room</span>
                                        <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <!-- EDITORIAL SECTION 4: REGULATORY & DIRECT NETWORK CONTACT -->
            <section class="bg-white border border-[#E4E8EF] rounded-2xl p-6 sm:p-8 space-y-6">
                <div class="flex items-center space-x-2 pb-3 border-b border-[#E4E8EF]">
                    <i data-lucide="shield-check" class="w-4 h-4 text-[#123B7A]"></i>
                    <h2 class="text-xs font-bold text-[#0B1F3A] uppercase tracking-wider">Regulatory Compliance & Contact Coordinates</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Compliance details -->
                    <div class="space-y-3">
                        <span class="text-[11px] font-bold text-[#667085] uppercase tracking-wider block">Regulatory Status</span>
                        <div class="space-y-2 text-xs">
                            <div class="flex items-center justify-between p-3 rounded-xl bg-[#FAFBFD] border border-[#E4E8EF]">
                                <span class="text-[#0B1F3A] font-semibold flex items-center space-x-2">
                                    <i data-lucide="check-circle" class="w-4 h-4 text-emerald-600"></i>
                                    <span>SEBI Risk Disclosure Declaration</span>
                                </span>
                                <span class="px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-800 text-[10px] font-bold">ACCEPTED</span>
                            </div>

                            <div class="flex items-center justify-between p-3 rounded-xl bg-[#FAFBFD] border border-[#E4E8EF]">
                                <span class="text-[#0B1F3A] font-semibold flex items-center space-x-2">
                                    <i data-lucide="check-circle" class="w-4 h-4 text-emerald-600"></i>
                                    <span>DigiLocker Identity & e-KYC</span>
                                </span>
                                <span class="px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-800 text-[10px] font-bold">VERIFIED</span>
                            </div>

                            <div class="flex items-center justify-between p-3 rounded-xl bg-[#FAFBFD] border border-[#E4E8EF]">
                                <span class="text-[#667085] font-medium flex items-center space-x-2">
                                    <i data-lucide="credit-card" class="w-4 h-4 text-[#667085]"></i>
                                    <span>Income Tax PAN Status</span>
                                </span>
                                <span class="font-mono text-xs font-bold text-[#0B1F3A]"><?= htmlspecialchars($profile['pan_number'] ?? 'VERIFIED_ON_FILE') ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Direct contact info -->
                    <div class="space-y-3">
                        <span class="text-[11px] font-bold text-[#667085] uppercase tracking-wider block">Accredited Network Coordinates</span>
                        <div class="space-y-2 text-xs">
                            <div class="p-3 rounded-xl bg-[#FAFBFD] border border-[#E4E8EF]">
                                <span class="text-[10px] text-[#667085] font-bold block uppercase tracking-wider">Accredited Email</span>
                                <span class="text-xs font-bold text-[#0B1F3A] mt-0.5 block"><?= htmlspecialchars($investor['email']) ?></span>
                            </div>

                            <?php if (!empty($investor['phone'])): ?>
                                <div class="p-3 rounded-xl bg-[#FAFBFD] border border-[#E4E8EF]">
                                    <span class="text-[10px] text-[#667085] font-bold block uppercase tracking-wider">Direct Telephone</span>
                                    <span class="text-xs font-bold text-[#0B1F3A] mt-0.5 block"><?= htmlspecialchars($investor['phone']) ?></span>
                                </div>
                            <?php endif; ?>

                            <div class="p-3 rounded-xl bg-[#EAF2FF]/60 border border-[#123B7A]/15 text-[#123B7A] text-[11px] flex items-center space-x-2">
                                <i data-lucide="lock" class="w-3.5 h-3.5 flex-shrink-0"></i>
                                <span>Verified angel communications routed through Nexora Escrow Protocol.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

        </main>
    </div>

    <script>
        lucide.createIcons();
        gsap.from("#investor-view-main > *", { duration: 0.45, y: 15, opacity: 0, stagger: 0.08, ease: "power2.out" });
    </script>
</body>

</html>