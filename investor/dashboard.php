<?php
/**
 * Investor Workspace & Home
 * Professional Investor Network Experience
 * Font: 'Vay Portal - Regular', Blue: #123B7A, Deep Navy: #0B1F3A
 * STRICTLY NO KPI CARDS. Editorial Sections, Profile Block, Horizontal Rows.
 */
require_once __DIR__ . '/../config.php';
$user = require_auth('investor');
$db = get_db();
$pageTitle = 'Investor Home';

$portfolioInvestments = [];
$recommendedStartups = [];
$totalInvested = 0;
$watchlistCount = 0;
$investorProfile = null;
$preferences = null;
$investorProgress = get_profile_progress($user['id'], 'investor');

if ($db) {
    // 1. Get confirmed investments
    $invStmt = $db->prepare("
        SELECT inv.*, c.name as company_name, c.industry, c.logo_url, fr.round_name, fr.status as round_status
        FROM investments inv
        JOIN companies c ON inv.company_id = c.id
        JOIN funding_rounds fr ON inv.funding_round_id = fr.id
        WHERE inv.investor_user_id = ?
        ORDER BY inv.confirmed_at DESC
    ");
    $invStmt->execute([$user['id']]);
    $portfolioInvestments = $invStmt->fetchAll();

    foreach ($portfolioInvestments as $p) {
        $totalInvested += (float) $p['amount_invested'];
    }

    // 2. Watchlist count
    $wStmt = $db->prepare("SELECT COUNT(*) FROM watchlists WHERE investor_user_id = ?");
    $wStmt->execute([$user['id']]);
    $watchlistCount = (int) $wStmt->fetchColumn();

    // 3. Recommended Live Funding Rounds
    $recStmt = $db->query("
        SELECT c.*, fr.id as round_id, fr.round_name, fr.target_amount, fr.amount_raised, fr.min_investment, fr.valuation, fr.equity_offered, fr.status as round_status
        FROM companies c
        JOIN funding_rounds fr ON c.id = fr.company_id
        WHERE fr.status IN ('LIVE', 'PARTIALLY_FUNDED')
        ORDER BY fr.created_at DESC LIMIT 5
    ");
    $recommendedStartups = $recStmt->fetchAll();

    // 4. Investor Profile & Preferences
    $ipStmt = $db->prepare("SELECT * FROM investor_profiles WHERE user_id = ?");
    $ipStmt->execute([$user['id']]);
    $investorProfile = $ipStmt->fetch();

    $prefStmt = $db->prepare("SELECT * FROM investor_preferences WHERE user_id = ?");
    $prefStmt->execute([$user['id']]);
    $preferences = $prefStmt->fetch();
}

$flash = get_flash();

// Time-based greeting
$hour = (int) date('G');
if ($hour < 12) {
    $greetingTime = 'Good morning';
} elseif ($hour < 17) {
    $greetingTime = 'Good afternoon';
} else {
    $greetingTime = 'Good evening';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Investor Workspace • <?= APP_NAME ?></title>
    <?php include __DIR__ . '/../includes/investor/head.php'; ?>
    <style>
        body {
            background-color: #F4F2EE;
            color: #111827;
        }
        .network-border {
            border-color: #E4E8EF;
        }
    </style>
</head>

<body class="bg-[#F4F2EE] text-[#111827] flex min-h-screen dark:bg-[#0B0F19] dark:text-slate-100">

    <!-- Investor Navigation Sidebar -->
    <?php include __DIR__ . '/../includes/investor/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0">
        <?php include __DIR__ . '/../includes/investor/navbar.php'; ?>

        <main class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6" id="workspace-main">

            <?php if ($flash): ?>
                <div class="p-4 rounded-2xl text-xs sm:text-sm font-semibold border <?= $flash['type'] === 'success' ? 'bg-[#EAF2FF] dark:bg-blue-950/40 text-[#123B7A] dark:text-blue-300 border-[#123B7A]/20' : 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border-rose-200' ?> flex items-center space-x-2.5 shadow-2xs">
                    <i data-lucide="check-circle" class="w-4 h-4 flex-shrink-0"></i>
                    <span><?= htmlspecialchars($flash['message']) ?></span>
                </div>
            <?php endif; ?>

            <!-- Regulatory Bulletins -->
            <?php include __DIR__ . '/../includes/announcement_banner.php'; ?>

            <!-- ==========================================
                 SECTION 1: INVESTOR GREETING & IDENTITY
                 ========================================== -->
            <section class="border-b border-[#E4E8EF] dark:border-slate-800 pb-8">
                <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
                    <div>
                        <div class="text-xs font-bold text-[#123B7A] dark:text-blue-400 tracking-wider uppercase mb-1.5 flex items-center gap-2">
                            <span>INVESTOR NETWORK</span>
                            <span class="w-2 h-2 rounded-full bg-emerald-500 pulse-beacon"></span>
                            <span class="text-[#667085] dark:text-slate-400 font-semibold">Active Institutional Session</span>
                        </div>
                        <h1 class="text-lg sm:text-xl font-bold text-[#0B1F3A] dark:text-white tracking-tight leading-tight">
                            <?= $greetingTime ?>, <?= htmlspecialchars(explode(' ', $user['name'])[0]) ?>.
                        </h1>
                        <p class="text-xs sm:text-sm text-[#667085] dark:text-slate-400 mt-1 max-w-3xl leading-relaxed">
                            Discover high-growth startups, evaluate verified institutional due diligence, and deploy venture capital into active syndicate rounds.
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <a href="<?= url('investor/discover.php') ?>"
                            class="px-5 py-3 rounded-xl bg-[#123B7A] hover:bg-[#0B1F3A] dark:bg-blue-600 dark:hover:bg-blue-700 text-white text-xs sm:text-sm font-bold transition flex items-center space-x-2 shadow-sm">
                            <i data-lucide="sparkles" class="w-4 h-4"></i>
                            <span>Discover Opportunities</span>
                        </a>
                        <a href="<?= url('investor/view.php') ?>"
                            class="px-5 py-3 rounded-xl bg-white dark:bg-slate-800 hover:bg-[#FAFBFD] dark:hover:bg-slate-700/80 border border-[#E4E8EF] dark:border-slate-700 text-[#111827] dark:text-slate-100 text-xs sm:text-sm font-bold transition flex items-center space-x-2 shadow-2xs">
                            <i data-lucide="user" class="w-4 h-4 text-[#667085] dark:text-slate-400"></i>
                            <span>My Profile</span>
                        </a>
                    </div>
                </div>
            </section>

            <!-- ==========================================
                 EXECUTIVE INVESTMENT KPI DASHBOARD TILES
                 ========================================== -->
            <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
                <!-- Total Capital Deployed -->
                <div class="dashboard-card bg-white dark:bg-slate-900 border border-[#E4E8EF] dark:border-slate-800 rounded-2xl p-6 shadow-xs relative overflow-hidden group">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold text-[#667085] dark:text-slate-400 uppercase tracking-wider">Capital Deployed</span>
                        <div class="w-10 h-10 rounded-xl bg-[#EAF2FF] dark:bg-blue-950/60 text-[#123B7A] dark:text-blue-400 flex items-center justify-center">
                            <i data-lucide="wallet" class="w-5 h-5"></i>
                        </div>
                    </div>
                    <div class="text-lg sm:text-xl font-bold text-[#0B1F3A] dark:text-white tracking-tight">
                        <?= format_inr($totalInvested) ?>
                    </div>
                    <div class="mt-2.5 flex items-center space-x-1.5 text-xs font-semibold text-[#123B7A] dark:text-blue-400">
                        <i data-lucide="shield-check" class="w-4 h-4 text-emerald-500"></i>
                        <span>Institutional Escrow Protected</span>
                    </div>
                </div>

                <!-- Active Portfolio Companies -->
                <div class="dashboard-card bg-white dark:bg-slate-900 border border-[#E4E8EF] dark:border-slate-800 rounded-2xl p-6 shadow-xs relative overflow-hidden group">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold text-[#667085] dark:text-slate-400 uppercase tracking-wider">Portfolio Holdings</span>
                        <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                            <i data-lucide="briefcase" class="w-5 h-5"></i>
                        </div>
                    </div>
                    <div class="text-lg sm:text-xl font-bold text-[#0B1F3A] dark:text-white tracking-tight">
                        <?= count($portfolioInvestments) ?> <span class="text-xs sm:text-sm font-semibold text-[#667085] dark:text-slate-400">Ventures</span>
                    </div>
                    <div class="mt-2.5 flex items-center space-x-1.5 text-xs text-[#667085] dark:text-slate-400 font-medium">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 inline-block"></span>
                        <span>Active Equity Stakes & Cap Tables</span>
                    </div>
                </div>

                <!-- Curated Syndicate Rounds -->
                <div class="dashboard-card bg-white dark:bg-slate-900 border border-[#E4E8EF] dark:border-slate-800 rounded-2xl p-6 shadow-xs relative overflow-hidden group">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold text-[#667085] dark:text-slate-400 uppercase tracking-wider">Live Deal Flow</span>
                        <div class="w-10 h-10 rounded-xl bg-purple-50 dark:bg-purple-950/40 text-purple-600 dark:text-purple-400 flex items-center justify-center">
                            <i data-lucide="sparkles" class="w-5 h-5"></i>
                        </div>
                    </div>
                    <div class="text-lg sm:text-xl font-bold text-[#0B1F3A] dark:text-white tracking-tight">
                        <?= count($recommendedStartups) ?> <span class="text-xs sm:text-sm font-semibold text-[#667085] dark:text-slate-400">Opportunities</span>
                    </div>
                    <div class="mt-2.5 flex items-center space-x-1.5 text-xs text-[#667085] dark:text-slate-400 font-medium">
                        <span class="w-2 h-2 rounded-full bg-purple-500 pulse-beacon inline-block"></span>
                        <span>SEBI AIF & Syndicate Compliant</span>
                    </div>
                </div>

                <!-- Saved Watchlist Pipeline -->
                <div class="dashboard-card bg-white dark:bg-slate-900 border border-[#E4E8EF] dark:border-slate-800 rounded-2xl p-6 shadow-xs relative overflow-hidden group">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold text-[#667085] dark:text-slate-400 uppercase tracking-wider">Saved Pipeline</span>
                        <div class="w-10 h-10 rounded-xl bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                            <i data-lucide="bookmark" class="w-5 h-5"></i>
                        </div>
                    </div>
                    <div class="text-lg sm:text-xl font-bold text-[#0B1F3A] dark:text-white tracking-tight">
                        <?= $watchlistCount ?> <span class="text-xs sm:text-sm font-semibold text-[#667085] dark:text-slate-400">Companies</span>
                    </div>
                    <div class="mt-2.5 flex items-center space-x-1.5 text-xs text-[#667085] dark:text-slate-400 font-medium">
                        <i data-lucide="eye" class="w-3.5 h-3.5 text-amber-500"></i>
                        <span>Tracking Round Progress</span>
                    </div>
                </div>
            </section>

            <!-- ==========================================
                 INSTITUTIONAL TRUST & REGULATORY BADGE STRIP
                 ========================================== -->
            <div class="p-4 sm:p-5 rounded-2xl bg-gradient-to-r from-[#0B1F3A] to-[#123B7A] dark:from-slate-900 dark:to-blue-950 text-white shadow-sm flex flex-col md:flex-row items-center justify-between gap-4">
                <div class="flex items-center space-x-3.5">
                    <div class="w-10 h-10 rounded-xl bg-white/10 flex items-center justify-center text-white flex-shrink-0">
                        <i data-lucide="shield-check" class="w-6 h-6 text-emerald-400"></i>
                    </div>
                    <div>
                        <div class="text-sm font-black tracking-tight flex items-center gap-2">
                            <span>SEBI Angel Syndicate & DigiLocker Verified Network</span>
                            <span class="px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 text-[10px] font-bold uppercase tracking-wider">AIF Compliant</span>
                        </div>
                        <div class="text-xs text-slate-300 mt-0.5">All ventures undergo corporate due diligence, MCA incorporation verification, and RBI-regulated escrow protection.</div>
                    </div>
                </div>
                <div class="flex items-center gap-2 sm:gap-3 flex-shrink-0">
                    <a href="<?= url('investor/verification.php') ?>" class="px-4 py-2 rounded-xl bg-white/10 hover:bg-white/20 text-white text-xs font-bold transition flex items-center gap-1.5 border border-white/10">
                        <i data-lucide="file-check" class="w-3.5 h-3.5 text-emerald-400"></i>
                        <span>Verify Status</span>
                    </a>
                    <a href="<?= url('investor/discover.php') ?>" class="px-4 py-2 rounded-xl bg-white hover:bg-slate-100 text-[#0B1F3A] text-xs font-extrabold transition shadow-xs">
                        Browse Deals →
                    </a>
                </div>
            </div>

            <!-- ==========================================
                 SECTION 2: YOUR INVESTOR PROFILE (Editorial Row)
                 ========================================== -->
            <section class="space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-xs font-bold text-[#667085] uppercase tracking-wider">Your Investor Profile</h2>
                    <a href="<?= url('investor/profile.php') ?>" class="text-xs font-bold text-[#123B7A] hover:underline flex items-center gap-1">
                        <span>Edit Thesis & Preferences</span>
                        <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                    </a>
                </div>

                <div class="bg-white border border-[#E4E8EF] rounded-xl p-6 sm:p-8">
                    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6 pb-6 border-b border-[#E4E8EF]">
                        <div class="flex items-center space-x-4">
                            <img src="<?= $user['avatar_url'] ?: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=160' ?>"
                                class="w-16 h-16 rounded-full object-cover border-2 border-[#E4E8EF]">
                            <div>
                                <div class="flex items-center gap-2.5">
                                    <h3 class="text-lg font-bold text-[#0B1F3A]"><?= htmlspecialchars($user['name']) ?></h3>
                                    <?php if ($user['is_verified']): ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-[#EAF2FF] text-[#123B7A]">
                                            <i data-lucide="shield-check" class="w-3 h-3"></i>
                                            SEBI Verified Angel
                                        </span>
                                    <?php else: ?>
                                        <a href="<?= url('investor/verification.php') ?>" class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200 hover:bg-amber-100 transition">
                                            <i data-lucide="alert-circle" class="w-3 h-3"></i>
                                            Verification Pending
                                        </a>
                                    <?php endif; ?>
                                </div>
                                <div class="text-xs text-[#667085] mt-1 flex flex-wrap items-center gap-3">
                                    <span><?= htmlspecialchars($investorProfile['investor_type'] ?? 'Accredited Angel Investor') ?></span>
                                    <span>•</span>
                                    <span><?= htmlspecialchars($user['city'] ?? 'India') ?></span>
                                    <span>•</span>
                                    <span><?= htmlspecialchars($user['email']) ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="text-left md:text-right">
                            <div class="text-[11px] text-[#667085] uppercase tracking-wider font-semibold">Accreditation Progress</div>
                            <div class="text-sm font-bold text-[#0B1F3A] mt-0.5"><?= $investorProgress['percentage'] ?>% Complete</div>
                        </div>
                    </div>

                    <!-- Profile Information Rows -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 pt-6 text-xs">
                        <div>
                            <span class="text-[10.5px] font-bold text-[#667085] uppercase tracking-wider block mb-1">Target Sectors</span>
                            <span class="text-[#111827] font-semibold">
                                <?= htmlspecialchars($preferences['preferred_industries'] ?? 'FinTech, AI/SaaS, DeepTech') ?>
                            </span>
                        </div>
                        <div>
                            <span class="text-[10.5px] font-bold text-[#667085] uppercase tracking-wider block mb-1">Stage Focus</span>
                            <span class="text-[#111827] font-semibold">
                                <?= htmlspecialchars($preferences['preferred_stages'] ?? 'Pre-Seed, Seed, Pre-Series A') ?>
                            </span>
                        </div>
                        <div>
                            <span class="text-[10.5px] font-bold text-[#667085] uppercase tracking-wider block mb-1">Check Size Range</span>
                            <span class="text-[#111827] font-semibold">
                                <?= !empty($preferences['min_ticket']) ? format_inr($preferences['min_ticket']) : '₹1,00,000' ?> – <?= !empty($preferences['max_ticket']) ? format_inr($preferences['max_ticket']) : '₹50,00,000' ?>
                            </span>
                        </div>
                        <div>
                            <span class="text-[10.5px] font-bold text-[#667085] uppercase tracking-wider block mb-1">Experience</span>
                            <span class="text-[#111827] font-semibold">
                                <?= htmlspecialchars($investorProfile['experience_years'] ?? '3') ?> Years Active Venture
                            </span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ==========================================
                 SECTION 3: DISCOVER OPPORTUNITIES (Editorial Featured + Rows)
                 ========================================== -->
            <section class="space-y-6">
                <div class="flex items-center justify-between border-b border-[#E4E8EF] pb-3">
                    <div>
                        <h2 class="text-base sm:text-lg font-extrabold text-[#0B1F3A] tracking-tight">Discover Opportunities</h2>
                        <p class="text-xs text-[#667085] mt-0.5">Live funding rounds from verified ventures</p>
                    </div>
                    <a href="<?= url('investor/discover.php') ?>" class="text-xs font-bold text-[#123B7A] hover:underline flex items-center gap-1">
                        <span>Browse All Startups</span>
                        <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                    </a>
                </div>

                <?php if (empty($recommendedStartups)): ?>
                    <div class="py-12 text-center text-xs text-[#667085] border border-dashed border-[#E4E8EF] rounded-xl bg-white">
                        No active funding rounds matching your parameters at this moment. Check back soon.
                    </div>
                <?php else: 
                    $featured = $recommendedStartups[0];
                    $featPct = ($featured['target_amount'] ?? 0) > 0 ? round(($featured['amount_raised'] / $featured['target_amount']) * 100) : 0;
                    $featHash = hash_id_encode($featured['id']);
                ?>
                    <!-- Large Featured Startup Area -->
                    <div class="bg-white border border-[#E4E8EF] rounded-xl p-6 sm:p-8 relative hover:border-[#123B7A]/40 transition">
                        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-3">
                                    <span class="px-2.5 py-0.5 rounded text-[10px] font-bold bg-[#EAF2FF] text-[#123B7A] uppercase tracking-wider">
                                        Featured Opportunity
                                    </span>
                                    <span class="text-[#667085] text-xs">•</span>
                                    <span class="text-xs font-semibold text-[#667085]"><?= htmlspecialchars($featured['industry']) ?></span>
                                    <span class="text-[#667085] text-xs">•</span>
                                    <span class="text-xs text-[#667085]"><?= htmlspecialchars($featured['stage']) ?></span>
                                </div>

                                <div class="flex items-start space-x-4 mb-4">
                                    <img src="<?= $featured['logo_url'] ?: 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=120' ?>"
                                        class="w-14 h-14 rounded-xl object-cover border border-[#E4E8EF] flex-shrink-0">
                                    <div>
                                        <h3 class="text-xl font-extrabold text-[#0B1F3A]">
                                            <?= htmlspecialchars($featured['name']) ?>
                                        </h3>
                                        <p class="text-xs sm:text-sm text-[#667085] mt-1 leading-relaxed max-w-2xl">
                                            <?= htmlspecialchars($featured['pitch']) ?>
                                        </p>
                                    </div>
                                </div>

                                <div class="flex flex-wrap items-center gap-6 text-xs text-[#111827] pt-2">
                                    <div>
                                        <span class="text-[10.5px] text-[#667085] block">Target Round</span>
                                        <span class="font-bold"><?= format_inr($featured['target_amount']) ?></span>
                                    </div>
                                    <div>
                                        <span class="text-[10.5px] text-[#667085] block">Pre-Money Valuation</span>
                                        <span class="font-bold"><?= format_inr($featured['valuation']) ?></span>
                                    </div>
                                    <div>
                                        <span class="text-[10.5px] text-[#667085] block">Minimum Ticket</span>
                                        <span class="font-bold"><?= format_inr($featured['min_investment']) ?></span>
                                    </div>
                                    <div>
                                        <span class="text-[10.5px] text-[#667085] block">Equity Offered</span>
                                        <span class="font-bold text-[#123B7A]"><?= htmlspecialchars($featured['equity_offered']) ?>%</span>
                                    </div>
                                </div>
                            </div>

                            <div class="lg:w-72 flex flex-col justify-between pt-4 lg:pt-0 lg:border-l lg:border-[#E4E8EF] lg:pl-8">
                                <div>
                                    <div class="flex justify-between text-xs mb-1.5 font-bold">
                                        <span class="text-[#667085]">Raised <?= format_inr($featured['amount_raised']) ?></span>
                                        <span class="text-[#123B7A]"><?= $featPct ?>%</span>
                                    </div>
                                    <div class="w-full h-2 bg-[#E4E8EF] rounded-full overflow-hidden mb-5">
                                        <div class="h-full bg-[#123B7A] rounded-full" style="width: <?= min(100, $featPct) ?>%"></div>
                                    </div>
                                </div>

                                <a href="<?= url('investor/startup_detail.php?id=' . $featHash) ?>"
                                    class="w-full py-3 rounded-lg bg-[#123B7A] hover:bg-[#0B1F3A] text-white text-xs font-bold text-center block transition shadow-sm">
                                    View Opportunity →
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Clean Horizontal Startup Rows (More Opportunities) -->
                    <?php if (count($recommendedStartups) > 1): ?>
                        <div class="space-y-0 border-t border-[#E4E8EF]">
                            <div class="py-3 text-[11px] font-bold text-[#667085] uppercase tracking-wider">
                                More Live Opportunities
                            </div>
                            <?php for ($idx = 1; $idx < count($recommendedStartups); $idx++):
                                $st = $recommendedStartups[$idx];
                                $stPct = ($st['target_amount'] ?? 0) > 0 ? round(($st['amount_raised'] / $st['target_amount']) * 100) : 0;
                                $stHash = hash_id_encode($st['id']);
                            ?>
                                <div class="network-row py-4 px-2 sm:px-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                    <div class="flex items-center space-x-3.5 min-w-0">
                                        <img src="<?= $st['logo_url'] ?: 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=80' ?>"
                                            class="w-10 h-10 rounded-lg object-cover border border-[#E4E8EF] flex-shrink-0">
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-2">
                                                <h4 class="text-sm font-bold text-[#0B1F3A] truncate"><?= htmlspecialchars($st['name']) ?></h4>
                                                <span class="px-2 py-0.5 rounded text-[9.5px] font-semibold bg-[#FAFBFD] text-[#667085] border border-[#E4E8EF]">
                                                    <?= htmlspecialchars($st['industry']) ?>
                                                </span>
                                            </div>
                                            <p class="text-xs text-[#667085] truncate mt-0.5 max-w-lg">
                                                <?= htmlspecialchars($st['pitch']) ?>
                                            </p>
                                        </div>
                                    </div>

                                    <div class="flex items-center justify-between sm:justify-end gap-6 text-xs flex-shrink-0">
                                        <div>
                                            <span class="text-[10px] text-[#667085] block">Target</span>
                                            <span class="font-bold text-[#111827]"><?= format_inr($st['target_amount']) ?></span>
                                        </div>
                                        <div>
                                            <span class="text-[10px] text-[#667085] block">Valuation</span>
                                            <span class="font-semibold text-[#667085]"><?= format_inr($st['valuation']) ?></span>
                                        </div>
                                        <a href="<?= url('investor/startup_detail.php?id=' . $stHash) ?>"
                                            class="px-4 py-2 rounded-lg bg-[#FAFBFD] hover:bg-[#EAF2FF] text-[#123B7A] hover:text-[#0B1F3A] border border-[#E4E8EF] font-bold text-xs transition">
                                            Review Deal →
                                        </a>
                                    </div>
                                </div>
                            <?php endfor; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </section>

            <!-- ==========================================
                 SECTION 4: YOUR ACTIVITY (Portfolio & Saved Ventures)
                 ========================================== -->
            <section class="space-y-4">
                <div class="flex items-center justify-between border-b border-[#E4E8EF] pb-3">
                    <div>
                        <h2 class="text-base sm:text-lg font-extrabold text-[#0B1F3A] tracking-tight">Your Activity</h2>
                        <p class="text-xs text-[#667085] mt-0.5">Recent investment commitments and tracked deal rooms</p>
                    </div>
                    <div class="flex items-center space-x-4 text-xs font-bold">
                        <a href="<?= url('investor/portfolio.php') ?>" class="text-[#123B7A] hover:underline">
                            My Portfolio (<?= count($portfolioInvestments) ?>)
                        </a>
                        <span class="text-[#E4E8EF]">|</span>
                        <a href="<?= url('investor/watchlist.php') ?>" class="text-[#667085] hover:text-[#123B7A]">
                            Saved Companies (<?= $watchlistCount ?>)
                        </a>
                    </div>
                </div>

                <?php if (empty($portfolioInvestments)): ?>
                    <div class="py-10 px-6 bg-white border border-[#E4E8EF] rounded-xl text-center text-xs text-[#667085]">
                        <p class="font-semibold text-[#111827] mb-1">No capital allocations deployed yet</p>
                        <p class="max-w-md mx-auto text-[#667085]">Browse verified startups on the discovery marketplace, review comprehensive due diligence documents, and participate in active syndicates.</p>
                        <a href="<?= url('investor/discover.php') ?>" class="inline-block mt-4 px-4 py-2 bg-[#123B7A] text-white font-bold rounded-lg text-xs hover:bg-[#0B1F3A] transition">
                            Explore Active Rounds
                        </a>
                    </div>
                <?php else: ?>
                    <div class="bg-white border border-[#E4E8EF] rounded-xl divide-y divide-[#E4E8EF]">
                        <?php foreach ($portfolioInvestments as $inv): ?>
                            <div class="p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-[#FAFBFD] transition">
                                <div class="flex items-center space-x-3.5 min-w-0">
                                    <img src="<?= $inv['logo_url'] ?: 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=80' ?>"
                                        class="w-10 h-10 rounded-lg object-cover border border-[#E4E8EF] flex-shrink-0">
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2">
                                            <h4 class="text-sm font-bold text-[#0B1F3A] truncate"><?= htmlspecialchars($inv['company_name']) ?></h4>
                                            <span class="px-2 py-0.5 rounded text-[9.5px] font-semibold bg-[#FAFBFD] text-[#667085] border border-[#E4E8EF]">
                                                <?= htmlspecialchars($inv['industry']) ?>
                                            </span>
                                        </div>
                                        <div class="text-xs text-[#667085] mt-0.5">
                                            <span>Round: <?= htmlspecialchars($inv['round_name']) ?></span>
                                            <span class="mx-1.5">•</span>
                                            <span>Ref: <?= htmlspecialchars($inv['certificate_number'] ?? 'CONFIRMED') ?></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between sm:justify-end gap-6 text-xs flex-shrink-0">
                                    <div>
                                        <span class="text-[10px] text-[#667085] block">Committed</span>
                                        <span class="font-bold text-[#111827]"><?= format_inr($inv['amount_invested']) ?></span>
                                    </div>
                                    <div>
                                        <span class="text-[10px] text-[#667085] block">Allotted Equity</span>
                                        <span class="font-bold text-[#123B7A]"><?= $inv['equity_allotted_percent'] ?>%</span>
                                    </div>
                                    <span class="px-2.5 py-1 rounded text-[10px] font-bold bg-[#EAF2FF] text-[#123B7A]">
                                        Confirmed
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

        </main>
    </div>

    <script>
        lucide.createIcons();
        gsap.from("#workspace-main", { duration: 0.4, y: 8, opacity: 0, ease: "power2.out" });
    </script>
</body>

</html>