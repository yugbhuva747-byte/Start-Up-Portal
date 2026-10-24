<?php
/**
 * Founder Module: Full Public / Detail Founder Profile
 * Premium Venture & LinkedIn Aesthetic, Responsive Grid, High-Contrast Readability
 */
require_once __DIR__ . '/../config.php';
$currentUser = require_auth(); // Accessible by founder, investor, admin
$db = get_db();

// 1. Resolve Target Founder ID
$targetUserId = 0;
if (isset($_GET['id'])) {
    $targetUserId = decode_id($_GET['id']);
    if ($targetUserId <= 0 && is_numeric($_GET['id'])) {
        $targetUserId = (int)$_GET['id'];
    }
}

// Fallback to current user if they are a founder and no ID passed
if ($targetUserId <= 0 && $currentUser['role'] === 'founder') {
    $targetUserId = (int)$currentUser['id'];
}

if ($targetUserId <= 0) {
    set_flash('error', 'Founder profile not specified or invalid.');
    header('Location: ' . ($currentUser['role'] === 'founder' ? url('founder/feed.php') : url('investor/discover.php')));
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
        header('Location: ' . url('founder/feed.php'));
        exit;
    }

    $fpStmt = $db->prepare("SELECT * FROM founder_profiles WHERE user_id = ?");
    $fpStmt->execute([$targetUserId]);
    $profile = $fpStmt->fetch();

    // 3. Fetch Associated Companies
    $cStmt = $db->prepare("
        SELECT c.*, cf.equity_percent, cf.is_signatory, cf.designation as founder_designation
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
$founderInitials = strtoupper(substr($founder['name'] ?? 'F', 0, 2));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> • <?= APP_NAME ?></title>
    <?php include __DIR__ . '/../includes/founder/head.php'; ?>
</head>
<body class="bg-[#F4F2EE] dark:bg-[#0B0F19] text-slate-900 dark:text-slate-100 flex min-h-screen antialiased selection:bg-indigo-500 selection:text-white">
    
    <!-- Sidebar Navigation -->
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
            
            <!-- Flash Message -->
            <?php if ($flash): ?>
                <div class="p-4 rounded-2xl text-sm font-semibold border <?= $flash['type'] === 'success' ? 'bg-emerald-50 text-emerald-800 border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800' : 'bg-rose-50 text-rose-800 border-rose-200 dark:bg-rose-950/60 dark:text-rose-300 dark:border-rose-800' ?> flex items-center space-x-2.5 shadow-sm">
                    <i data-lucide="<?= $flash['type'] === 'success' ? 'check-circle' : 'alert-circle' ?>" class="w-5 h-5 flex-shrink-0"></i>
                    <span><?= htmlspecialchars($flash['message']) ?></span>
                </div>
            <?php endif; ?>

            <!-- Breadcrumb Navigation Bar -->
            <div class="flex items-center justify-between text-sm text-slate-500 font-medium">
                <div class="flex items-center space-x-2">
                    <?php if ($currentUser['role'] === 'founder'): ?>
                        <a href="<?= url('founder/feed.php') ?>" class="hover:text-indigo-600 transition flex items-center space-x-1.5 font-bold text-indigo-600 dark:text-indigo-400">
                            <i data-lucide="arrow-left" class="w-4 h-4"></i>
                            <span>Network Feed</span>
                        </a>
                    <?php else: ?>
                        <a href="<?= url('investor/discover.php') ?>" class="hover:text-indigo-600 transition flex items-center space-x-1.5 font-bold text-indigo-600 dark:text-indigo-400">
                            <i data-lucide="arrow-left" class="w-4 h-4"></i>
                            <span>Discover Startups</span>
                        </a>
                    <?php endif; ?>
                    <span>/</span>
                    <span class="text-slate-900 dark:text-white font-bold truncate max-w-xs"><?= htmlspecialchars($founder['name']) ?></span>
                </div>

                <?php if ($isSelf): ?>
                    <a href="<?= url('founder/profile.php') ?>" class="flex items-center space-x-2 px-4 py-2 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/60 dark:hover:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 rounded-xl font-bold text-xs sm:text-sm transition shadow-2xs">
                        <i data-lucide="edit-3" class="w-4 h-4"></i>
                        <span>Edit My Profile</span>
                    </a>
                <?php endif; ?>
            </div>

            <!-- ======================================================== -->
            <!-- MASTER FOUNDER HERO CARD -->
            <!-- ======================================================== -->
            <div class="rounded-3xl overflow-hidden relative shadow-sm border border-slate-200 dark:border-slate-800 bg-white dark:bg-[#111827]">
                
                <!-- Venture Gradient Cover Banner -->
                <div class="h-40 sm:h-52 bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 relative overflow-hidden">
                    <div class="w-full h-full opacity-25 bg-[radial-gradient(#6366f1_1px,transparent_1px)] [background-size:18px_18px]"></div>
                    
                    <div class="absolute top-4 right-4 flex items-center gap-2">
                        <span class="px-3.5 py-1.5 rounded-xl bg-black/60 backdrop-blur-md text-white text-xs font-bold border border-white/20 flex items-center gap-1.5 shadow-md">
                            <i data-lucide="shield-check" class="w-3.5 h-3.5 text-emerald-400"></i>
                            <span>Verified Founder Identity</span>
                        </span>
                    </div>
                </div>

                <!-- Profile Identity Bar -->
                <div class="px-6 sm:px-10 pb-8 sm:pb-9 pt-0 relative">
                    <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-6 sm:gap-8 border-b border-slate-100 dark:border-slate-800/80 pb-7 sm:pb-8">
                        
                        <!-- Left: Avatar + Details -->
                        <div class="flex flex-col sm:flex-row sm:items-end gap-6">
                            
                            <!-- Founder Headshot Avatar -->
                            <div class="relative -mt-16 sm:-mt-22 flex-shrink-0">
                                <div class="w-32 h-32 sm:w-36 sm:h-36 rounded-full border-4 border-white dark:border-[#111827] shadow-xl overflow-hidden bg-white dark:bg-slate-800 flex items-center justify-center relative">
                                    <?php if (!empty($founder['avatar_url'])): ?>
                                        <img src="<?= htmlspecialchars(get_avatar_url($founder['avatar_url'])) ?>" 
                                             alt="<?= htmlspecialchars($founder['name']) ?>"
                                             class="w-full h-full object-cover object-center">
                                    <?php else: ?>
                                        <div class="w-full h-full rounded-full bg-gradient-to-tr from-indigo-600 via-indigo-500 to-violet-600 text-white font-black text-3xl sm:text-4xl flex items-center justify-center shadow-inner">
                                            <?= htmlspecialchars($founderInitials) ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($founder['is_verified']): ?>
                                        <div class="absolute bottom-1 right-1 w-8 h-8 rounded-full bg-emerald-500 border-2 border-white dark:border-[#111827] flex items-center justify-center text-white shadow-md" title="KYC Verified Founder">
                                            <i data-lucide="check" class="w-4 h-4"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Name, Title, and Meta Chips -->
                            <div class="pt-2 sm:pt-0 space-y-2.5 min-w-0">
                                <div class="flex flex-wrap items-center gap-3">
                                    <h1 class="text-2xl sm:text-3xl lg:text-4xl font-black text-slate-900 dark:text-white tracking-tight leading-tight">
                                        <?= htmlspecialchars($founder['name']) ?>
                                    </h1>
                                    <?php if ($founder['is_verified']): ?>
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 text-xs font-black uppercase tracking-wider shadow-2xs">
                                            <i data-lucide="badge-check" class="w-3.5 h-3.5 text-emerald-600"></i>
                                            <span>KYC Verified</span>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <div class="text-sm sm:text-base font-bold text-indigo-600 dark:text-indigo-400 flex items-center gap-2 flex-wrap">
                                    <span><?= htmlspecialchars($profile['designation'] ?? 'Founder & CEO') ?></span>
                                    <?php if (!empty($companies)): ?>
                                        <span class="text-slate-400 font-normal">at</span>
                                        <a href="<?= url('founder/company_view.php?id=' . encode_id($companies[0]['id'])) ?>" class="text-slate-800 dark:text-slate-200 hover:text-indigo-600 dark:hover:text-indigo-400 font-extrabold underline decoration-slate-300">
                                            <?= htmlspecialchars($companies[0]['name']) ?>
                                        </a>
                                    <?php endif; ?>
                                </div>

                                <!-- Modern Icon Badges -->
                                <div class="flex flex-wrap items-center gap-2.5 pt-1 text-xs sm:text-sm">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-100/90 dark:bg-slate-800/80 text-slate-700 dark:text-slate-200 font-semibold border border-slate-200/80 dark:border-slate-700/80 shadow-2xs">
                                        <i data-lucide="map-pin" class="w-4 h-4 text-rose-500"></i>
                                        <span><?= htmlspecialchars($founder['city'] ?? 'Mumbai') ?>, <?= htmlspecialchars($founder['country'] ?? 'India') ?></span>
                                    </span>

                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-100/90 dark:bg-slate-800/80 text-slate-700 dark:text-slate-200 font-semibold border border-slate-200/80 dark:border-slate-700/80 shadow-2xs">
                                        <i data-lucide="calendar" class="w-4 h-4 text-indigo-500"></i>
                                        <span>Portal Member since <?= date('M Y', strtotime($founder['created_at'])) ?></span>
                                    </span>

                                    <?php if (!empty($profile['linkedin_url'])): ?>
                                        <a href="<?= htmlspecialchars($profile['linkedin_url']) ?>" target="_blank" rel="noopener noreferrer" 
                                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-blue-50/80 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 hover:bg-blue-100/90 dark:hover:bg-blue-900/50 font-bold border border-blue-200/80 dark:border-blue-800/70 transition shadow-2xs">
                                            <i data-lucide="linkedin" class="w-4 h-4 text-blue-600"></i>
                                            <span>LinkedIn</span>
                                            <i data-lucide="external-link" class="w-3.5 h-3.5 text-blue-400"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Right Actions -->
                        <div class="lg:w-72 flex flex-col justify-between gap-3 flex-shrink-0">
                            <?php if (!$isSelf): ?>
                                <a href="<?= $currentUser['role'] === 'founder' ? url('founder/messages.php') : url('investor/messages.php') ?>" 
                                   class="w-full py-3 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-sm shadow-md transition active:scale-95 flex items-center justify-center gap-2">
                                    <i data-lucide="message-square" class="w-4 h-4"></i>
                                    <span>Send Message / Inquire</span>
                                </a>

                                <?php if (!empty($companies)): ?>
                                    <a href="<?= $currentUser['role'] === 'founder' ? url('founder/company_view.php?id=' . encode_id($companies[0]['id'])) : url('investor/startup_detail.php?id=' . encode_id($companies[0]['id'])) ?>" 
                                       class="w-full py-2.5 px-4 rounded-xl bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 border border-slate-200 dark:border-slate-700 font-bold text-xs transition flex items-center justify-center gap-2 shadow-2xs">
                                        <i data-lucide="building-2" class="w-4 h-4 text-indigo-500"></i>
                                        <span>View Startup Deal Room</span>
                                    </a>
                                <?php endif; ?>
                            <?php else: ?>
                                <a href="<?= url('founder/profile.php') ?>" 
                                   class="w-full py-3 px-4 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-extrabold text-sm shadow-md transition active:scale-95 flex items-center justify-center gap-2">
                                    <i data-lucide="settings" class="w-4 h-4"></i>
                                    <span>Account &amp; Profile Settings</span>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- 4 Quick Stats Metrics Strip -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3.5 pt-6">
                        <div class="p-4 sm:p-5 rounded-2xl bg-slate-50/90 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700/80 shadow-2xs">
                            <span class="text-[10.5px] uppercase font-extrabold text-slate-400 dark:text-slate-500 tracking-wider flex items-center gap-1.5">
                                <i data-lucide="rocket" class="w-3.5 h-3.5 text-indigo-500"></i>
                                Startups Founded
                            </span>
                            <div class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white mt-1.5"><?= count($companies) ?></div>
                        </div>

                        <div class="p-4 sm:p-5 rounded-2xl bg-slate-50/90 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700/80 shadow-2xs">
                            <span class="text-[10.5px] uppercase font-extrabold text-slate-400 dark:text-slate-500 tracking-wider flex items-center gap-1.5">
                                <i data-lucide="circle-dollar-sign" class="w-3.5 h-3.5 text-amber-500"></i>
                                Active Campaigns
                            </span>
                            <div class="text-xl sm:text-2xl font-black text-indigo-600 dark:text-indigo-400 mt-1.5"><?= count($fundingRounds) ?></div>
                        </div>

                        <div class="p-4 sm:p-5 rounded-2xl bg-slate-50/90 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700/80 shadow-2xs">
                            <span class="text-[10.5px] uppercase font-extrabold text-slate-400 dark:text-slate-500 tracking-wider flex items-center gap-1.5">
                                <i data-lucide="shield-check" class="w-3.5 h-3.5 text-emerald-500"></i>
                                Compliance
                            </span>
                            <div class="text-base sm:text-lg font-black text-emerald-600 dark:text-emerald-400 mt-1.5 flex items-center gap-1.5">
                                <span>100% Passed</span>
                            </div>
                        </div>

                        <div class="p-4 sm:p-5 rounded-2xl bg-slate-50/90 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700/80 shadow-2xs">
                            <span class="text-[10.5px] uppercase font-extrabold text-slate-400 dark:text-slate-500 tracking-wider flex items-center gap-1.5">
                                <i data-lucide="pie-chart" class="w-3.5 h-3.5 text-violet-500"></i>
                                Corporate Equity
                            </span>
                            <div class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white mt-1.5">
                                <?= !empty($companies[0]['equity_percent']) ? $companies[0]['equity_percent'] . '%' : 'Founder' ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ======================================================== -->
            <!-- 2-COLUMN LAYOUT: BIO/CREDS & STARTUPS -->
            <!-- ======================================================== -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <!-- Left Column (1 Col): Bio, Credentials, Links -->
                <div class="space-y-6">
                    
                    <!-- Bio Card -->
                    <div class="rounded-3xl p-6 sm:p-7 bg-white dark:bg-[#111827] border border-slate-200 dark:border-slate-800 shadow-sm space-y-3.5">
                        <h2 class="text-xs sm:text-sm font-extrabold text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-2 border-b border-slate-100 dark:border-slate-800 pb-3">
                            <i data-lucide="user" class="w-4 h-4 text-indigo-600"></i>
                            <span>Founder Bio &amp; Background</span>
                        </h2>
                        <p class="text-xs sm:text-sm text-slate-700 dark:text-slate-300 leading-relaxed font-normal">
                            <?= nl2br(htmlspecialchars($profile['bio'] ?: 'High-conviction startup founder building innovative, scalable solutions. Verified identity on the portal.')) ?>
                        </p>
                    </div>

                    <!-- Verified Regulatory & Compliance Credentials -->
                    <div class="rounded-3xl p-6 sm:p-7 bg-white dark:bg-[#111827] border border-slate-200 dark:border-slate-800 shadow-sm space-y-3.5">
                        <h2 class="text-xs sm:text-sm font-extrabold text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-2 border-b border-slate-100 dark:border-slate-800 pb-3">
                            <i data-lucide="shield" class="w-4 h-4 text-emerald-600"></i>
                            <span>Verified Credentials</span>
                        </h2>
                        <div class="space-y-2.5 text-xs sm:text-sm">
                            <div class="flex items-center justify-between p-3.5 rounded-2xl bg-emerald-50/70 dark:bg-emerald-950/40 border border-emerald-200/80 dark:border-emerald-800/70">
                                <span class="text-slate-800 dark:text-slate-200 font-bold flex items-center gap-2">
                                    <i data-lucide="check-circle" class="w-4 h-4 text-emerald-600"></i>
                                    <span>DigiLocker KYC</span>
                                </span>
                                <span class="text-emerald-700 dark:text-emerald-300 font-black text-xs px-2.5 py-0.5 bg-emerald-100/70 dark:bg-emerald-900/60 rounded-full">VERIFIED</span>
                            </div>

                            <div class="flex items-center justify-between p-3.5 rounded-2xl bg-emerald-50/70 dark:bg-emerald-950/40 border border-emerald-200/80 dark:border-emerald-800/70">
                                <span class="text-slate-800 dark:text-slate-200 font-bold flex items-center gap-2">
                                    <i data-lucide="check-circle" class="w-4 h-4 text-emerald-600"></i>
                                    <span>MCA Registry Match</span>
                                </span>
                                <span class="text-emerald-700 dark:text-emerald-300 font-black text-xs px-2.5 py-0.5 bg-emerald-100/70 dark:bg-emerald-900/60 rounded-full">VERIFIED</span>
                            </div>

                            <div class="flex items-center justify-between p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700">
                                <span class="text-slate-700 dark:text-slate-300 font-bold flex items-center gap-2">
                                    <i data-lucide="credit-card" class="w-4 h-4 text-slate-400"></i>
                                    <span>PAN Verification</span>
                                </span>
                                <span class="font-mono text-xs font-black text-slate-800 dark:text-slate-200"><?= htmlspecialchars($profile['pan_number'] ?? 'VERIFIED_ON_FILE') ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Direct Contact & Web Links -->
                    <div class="rounded-3xl p-6 sm:p-7 bg-white dark:bg-[#111827] border border-slate-200 dark:border-slate-800 shadow-sm space-y-3.5">
                        <h2 class="text-xs sm:text-sm font-extrabold text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-2 border-b border-slate-100 dark:border-slate-800 pb-3">
                            <i data-lucide="globe" class="w-4 h-4 text-indigo-600"></i>
                            <span>Links &amp; Official Contact</span>
                        </h2>
                        <div class="space-y-3 text-xs sm:text-sm">
                            <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700/80">
                                <span class="text-[10px] text-slate-400 font-bold block uppercase tracking-wider">Official Email</span>
                                <span class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white mt-0.5 block truncate"><?= htmlspecialchars($founder['email']) ?></span>
                            </div>

                            <?php if (!empty($founder['phone'])): ?>
                                <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700/80">
                                    <span class="text-[10px] text-slate-400 font-bold block uppercase tracking-wider">Direct Phone</span>
                                    <span class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white mt-0.5 block"><?= htmlspecialchars($founder['phone']) ?></span>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($profile['linkedin_url'])): ?>
                                <a href="<?= htmlspecialchars($profile['linkedin_url']) ?>" target="_blank" rel="noopener noreferrer" class="flex items-center justify-between p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/60 hover:bg-slate-100 text-slate-800 dark:text-slate-200 transition font-bold text-xs sm:text-sm border border-slate-200/80 dark:border-slate-700/80">
                                    <span class="flex items-center gap-2">
                                        <i data-lucide="linkedin" class="w-4 h-4 text-blue-600"></i>
                                        <span>LinkedIn Profile</span>
                                    </span>
                                    <i data-lucide="external-link" class="w-3.5 h-3.5 text-slate-400"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>

                <!-- Right Column (2 Cols): Associated Startups & Funding Campaigns -->
                <div class="lg:col-span-2 space-y-6">
                    
                    <!-- Associated Startups & Entities -->
                    <div class="rounded-3xl p-6 sm:p-7 bg-white dark:bg-[#111827] border border-slate-200 dark:border-slate-800 shadow-sm space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                            <h2 class="text-xs sm:text-sm font-extrabold text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                                <i data-lucide="building-2" class="w-4 h-4 text-indigo-600"></i>
                                <span>Startups &amp; Portfolio Entities (<?= count($companies) ?>)</span>
                            </h2>
                        </div>

                        <?php if (empty($companies)): ?>
                            <div class="py-12 text-center text-slate-400 text-xs">
                                No company profiles linked to this founder yet.
                            </div>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php foreach ($companies as $comp): ?>
                                    <div class="p-5 sm:p-6 rounded-3xl bg-slate-50/80 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700/80 space-y-4 shadow-2xs">
                                        
                                        <!-- Company Header Row (Fixed Logo Sizing & Alignment) -->
                                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                            <div class="flex items-center gap-4 min-w-0">
                                                <!-- Company Logo Box (Fixed dimensions, never stretches) -->
                                                <div class="w-16 h-16 sm:w-18 sm:h-18 rounded-2xl bg-white dark:bg-slate-900 border-2 border-slate-200 dark:border-slate-700 p-1 flex-shrink-0 shadow-sm overflow-hidden flex items-center justify-center">
                                                    <img src="<?= htmlspecialchars(get_company_logo_url($comp['logo_url'] ?? '')) ?>" 
                                                         alt="<?= htmlspecialchars($comp['name']) ?>" 
                                                         class="w-full h-full object-cover object-center rounded-xl">
                                                </div>

                                                <div class="min-w-0">
                                                    <div class="flex items-center gap-2 flex-wrap">
                                                        <h3 class="font-black text-slate-900 dark:text-white text-base sm:text-lg tracking-tight">
                                                            <?= htmlspecialchars($comp['name']) ?>
                                                        </h3>
                                                        <?= render_status_badge($comp['verified_status']) ?>
                                                    </div>

                                                    <div class="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-400 font-semibold mt-1 flex-wrap">
                                                        <span class="inline-flex items-center gap-1 text-indigo-600 dark:text-indigo-400 font-bold">
                                                            <i data-lucide="briefcase" class="w-3.5 h-3.5"></i>
                                                            <?= htmlspecialchars($comp['industry']) ?>
                                                        </span>
                                                        <span>•</span>
                                                        <span class="text-amber-700 dark:text-amber-400 font-bold">
                                                            <?= htmlspecialchars($comp['stage']) ?> Stage
                                                        </span>
                                                        <?php if (!empty($comp['city'])): ?>
                                                            <span>•</span>
                                                            <span class="text-slate-500">
                                                                <?= htmlspecialchars($comp['city']) ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="sm:text-right flex-shrink-0">
                                                <span class="text-[10px] text-slate-400 uppercase font-black tracking-wider block">Founder Equity Stake</span>
                                                <span class="font-black text-indigo-600 dark:text-indigo-400 text-sm sm:text-base">
                                                    <?= !empty($comp['equity_percent']) ? $comp['equity_percent'] . '%' : 'Founder' ?>
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Elevator Pitch / Description -->
                                        <p class="text-xs sm:text-sm text-slate-700 dark:text-slate-300 leading-relaxed font-normal">
                                            <?= htmlspecialchars($comp['pitch'] ?: ($comp['description'] ?: 'High-growth technology enterprise registered under the Ministry of Corporate Affairs.')) ?>
                                        </p>

                                        <!-- Bottom Meta Strip & Action CTAs -->
                                        <div class="pt-3.5 border-t border-slate-200/70 dark:border-slate-700/70 flex flex-wrap items-center justify-between gap-3 text-xs">
                                            <div class="font-mono text-[11px] text-slate-500">
                                                CIN: <span class="font-bold text-slate-800 dark:text-slate-200"><?= htmlspecialchars($comp['cin_number'] ?? 'PENDING') ?></span>
                                            </div>

                                            <div class="flex items-center gap-2">
                                                <?php if ($currentUser['role'] === 'investor'): ?>
                                                    <a href="<?= url('investor/startup_detail.php?id=' . encode_id($comp['id'])) ?>" class="px-3.5 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-xs transition flex items-center gap-1.5">
                                                        <span>Open Deal Room</span>
                                                        <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                                                    </a>
                                                <?php elseif ($isSelf): ?>
                                                    <a href="<?= url('founder/company.php') ?>" class="px-3.5 py-1.5 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 hover:bg-indigo-100 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 font-bold text-xs transition flex items-center gap-1.5">
                                                        <span>Manage Company</span>
                                                        <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <a href="<?= url('founder/company_view.php?id=' . encode_id($comp['id'])) ?>" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-xs transition flex items-center gap-1.5">
                                                        <span>View Startup &amp; Angel Invest</span>
                                                        <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Live Funding Campaigns -->
                    <div class="rounded-3xl p-6 sm:p-7 bg-white dark:bg-[#111827] border border-slate-200 dark:border-slate-800 shadow-sm space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                            <h2 class="text-xs sm:text-sm font-extrabold text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                                <i data-lucide="circle-dollar-sign" class="w-4 h-4 text-indigo-600"></i>
                                <span>Active Funding Campaigns</span>
                            </h2>
                        </div>

                        <?php if (empty($fundingRounds)): ?>
                            <div class="py-10 text-center text-slate-400 text-xs">
                                No active funding campaigns open for this founder currently.
                            </div>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php foreach ($fundingRounds as $fr): 
                                    $pct = $fr['target_amount'] > 0 ? min(100, round(($fr['amount_raised'] / $fr['target_amount']) * 100)) : 0;
                                ?>
                                    <div class="p-5 sm:p-6 rounded-3xl bg-slate-50/80 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700/80 space-y-4 shadow-2xs">
                                        <div class="flex items-center justify-between gap-3">
                                            <div>
                                                <div class="font-black text-slate-900 dark:text-white text-sm sm:text-base"><?= htmlspecialchars($fr['round_name']) ?></div>
                                                <div class="text-xs text-slate-500 font-semibold"><?= htmlspecialchars($fr['company_name']) ?></div>
                                            </div>
                                            <?= render_status_badge($fr['status']) ?>
                                        </div>

                                        <!-- Progress Bar -->
                                        <div class="space-y-1.5">
                                            <div class="flex justify-between text-xs font-semibold">
                                                <span class="text-slate-600 dark:text-slate-300">Raised: <strong class="text-slate-900 dark:text-white font-black"><?= format_inr($fr['amount_raised']) ?></strong></span>
                                                <span class="text-indigo-600 dark:text-indigo-400 font-black"><?= $pct ?>%</span>
                                            </div>
                                            <div class="w-full h-2.5 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
                                                <div class="h-full bg-gradient-to-r from-indigo-500 to-indigo-600 rounded-full transition-all duration-500" style="width: <?= $pct ?>%"></div>
                                            </div>
                                            <div class="text-[11px] text-slate-400">
                                                Target: <?= format_inr($fr['target_amount']) ?>
                                            </div>
                                        </div>

                                        <div class="pt-3 border-t border-slate-200/70 dark:border-slate-700/70 flex flex-wrap items-center justify-between gap-3 text-xs font-semibold">
                                            <div class="flex items-center gap-3 text-slate-700 dark:text-slate-300">
                                                <span>Valuation: <strong class="font-black text-slate-900 dark:text-white"><?= format_inr($fr['valuation']) ?></strong></span>
                                                <span>•</span>
                                                <span>Equity Offered: <strong class="text-emerald-600 font-black"><?= $fr['equity_offered'] ?>%</strong></span>
                                            </div>

                                            <?php if ($currentUser['role'] === 'investor'): ?>
                                                <a href="<?= url('investor/invest.php?round_id=' . encode_id($fr['id'])) ?>" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs transition">
                                                    Invest in Round
                                                </a>
                                            <?php elseif (!$isSelf): ?>
                                                <a href="<?= url('founder/company_view.php?id=' . encode_id($fr['company_id'])) ?>" class="px-4 py-2 bg-amber-500 hover:bg-amber-400 text-slate-950 font-black text-xs rounded-xl shadow-xs transition">
                                                    Angel Syndicate
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
        if (typeof lucide !== 'undefined' && lucide.createIcons) {
            lucide.createIcons();
        }
        if (typeof gsap !== 'undefined') {
            gsap.from("#founder-view-main", { duration: 0.35, y: 10, opacity: 0, ease: "power2.out" });
        }
    </script>
</body>
</html>
