<?php
/**
 * Investor Workspace & Dashboard
 * Premium, User-Friendly & Intuitive Venture Experience
 * Fully responsive with Dark/Light mode support & smooth micro-interactions
 */
require_once __DIR__ . '/../config.php';
$user = require_auth('investor');
$db = get_db();
$pageTitle = 'Investor Dashboard';

$portfolioInvestments = [];
$recommendedStartups = [];
$totalInvested = 0;
$totalEquity = 0;
$watchlistCount = 0;
$investorProfile = null;
$preferences = null;
$investorProgress = get_profile_progress($user['id'], 'investor');

if ($db) {
    // 1. Get confirmed investments
    $invStmt = $db->prepare("
        SELECT inv.*, c.name as company_name, c.industry, c.logo_url, c.id as comp_id,
               fr.round_name, fr.status as round_status, fr.target_amount, fr.amount_raised
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
        $totalEquity += (float) ($p['equity_allotted_percent'] ?? 0);
    }

    // 2. Watchlist count
    $wStmt = $db->prepare("SELECT COUNT(*) FROM watchlists WHERE investor_user_id = ?");
    $wStmt->execute([$user['id']]);
    $watchlistCount = (int) $wStmt->fetchColumn();

    // 3. Recommended Live Funding Rounds
    $recStmt = $db->query("
        SELECT c.*, fr.id as round_id, fr.round_name, fr.target_amount, fr.amount_raised, 
               fr.min_investment, fr.valuation, fr.equity_offered, fr.status as round_status
        FROM companies c
        JOIN funding_rounds fr ON c.id = fr.company_id
        WHERE fr.status IN ('LIVE', 'PARTIALLY_FUNDED')
        ORDER BY fr.created_at DESC LIMIT 6
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

$firstName = htmlspecialchars(explode(' ', $user['name'] ?? 'Investor')[0]);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Investor Dashboard • <?= APP_NAME ?></title>
    <?php include __DIR__ . '/../includes/investor/head.php'; ?>
    <style>
        .glass-panel {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(8px);
        }
        .dark .glass-panel {
            background: rgba(15, 23, 42, 0.92);
        }
        .metric-card {
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .metric-card:hover {
            transform: translateY(-2px);
        }
    </style>
</head>

<body class="bg-slate-50 text-slate-900 flex min-h-screen dark:bg-[#0B0F19] dark:text-slate-100 antialiased selection:bg-blue-600 selection:text-white">

    <!-- Investor Navigation Sidebar -->
    <?php include __DIR__ . '/../includes/investor/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0">
        <?php include __DIR__ . '/../includes/investor/navbar.php'; ?>

        <main class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6 sm:space-y-8" id="investor-workspace">

            <?php if ($flash): ?>
                <div class="p-4 rounded-2xl text-xs sm:text-sm font-semibold border <?= $flash['type'] === 'success' ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800/60' : 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border-rose-200 dark:border-rose-800/60' ?> flex items-center space-x-2.5 shadow-xs">
                    <i data-lucide="<?= $flash['type'] === 'success' ? 'check-circle' : 'alert-circle' ?>" class="w-4 h-4 flex-shrink-0"></i>
                    <span><?= htmlspecialchars($flash['message']) ?></span>
                </div>
            <?php endif; ?>

            <!-- Regulatory Bulletins -->
            <?php include __DIR__ . '/../includes/announcement_banner.php'; ?>

            <!-- ==========================================
                 HERO GREETING & PRIMARY ACTIONS
                 ========================================== -->
            <section class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-3xl p-6 sm:p-8 shadow-xs relative overflow-hidden">
                <!-- Ambient Accent Glow -->
                <div class="absolute -right-20 -top-20 w-80 h-80 rounded-full bg-gradient-to-br from-blue-500/10 via-indigo-500/5 to-transparent blur-3xl pointer-events-none"></div>

                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 relative z-10">
                    <div class="max-w-2xl">
                        <div class="flex flex-wrap items-center gap-2 mb-2.5">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-400 border border-blue-200/60 dark:border-blue-800/60">
                                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                <span>Verified Investor Desk</span>
                            </span>
                            <?php if ($user['is_verified']): ?>
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800/60">
                                    <i data-lucide="shield-check" class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400"></i>
                                    SEBI Compliant
                                </span>
                            <?php else: ?>
                                <a href="<?= url('investor/verification.php') ?>" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300 border border-amber-200/60 dark:border-amber-800/60 hover:bg-amber-100 transition">
                                    <i data-lucide="alert-circle" class="w-3.5 h-3.5 text-amber-600"></i>
                                    Verify Accreditation
                                </a>
                            <?php endif; ?>
                            <span class="text-xs text-slate-400 dark:text-slate-500 hidden sm:inline">• <?= date('l, F j') ?></span>
                        </div>

                        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                            <?= $greetingTime ?>, <?= $firstName ?>
                        </h1>
                        <p class="text-sm text-slate-600 dark:text-slate-400 mt-2 leading-relaxed">
                            Evaluate curated high-growth ventures, review audited institutional due diligence, and track your active syndicate investments.
                        </p>
                    </div>

                    <!-- Quick Navigation Action Buttons -->
                    <div class="flex flex-wrap items-center gap-3">
                        <a href="<?= url('investor/discover.php') ?>"
                            class="px-5 py-3 rounded-2xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white text-sm font-bold shadow-md shadow-blue-500/20 transition-all flex items-center space-x-2 group">
                            <i data-lucide="sparkles" class="w-4 h-4 group-hover:scale-110 transition-transform"></i>
                            <span>Discover Startups</span>
                        </a>
                        <a href="<?= url('investor/portfolio.php') ?>"
                            class="px-5 py-3 rounded-2xl bg-slate-100 hover:bg-slate-200/80 dark:bg-slate-800 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 text-sm font-bold transition flex items-center space-x-2">
                            <i data-lucide="briefcase" class="w-4 h-4 text-slate-500 dark:text-slate-400"></i>
                            <span>My Portfolio</span>
                        </a>
                        <a href="<?= url('investor/watchlist.php') ?>"
                            class="px-4 py-3 rounded-2xl bg-white hover:bg-slate-50 dark:bg-slate-800/60 dark:hover:bg-slate-700/80 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 text-sm font-semibold transition flex items-center space-x-1.5"
                            title="View Tracked Companies">
                            <i data-lucide="bookmark" class="w-4 h-4 text-amber-500"></i>
                            <span class="hidden sm:inline">Watchlist</span>
                            <span class="ml-1 px-1.5 py-0.5 text-xs rounded-full bg-slate-100 dark:bg-slate-700 font-bold"><?= $watchlistCount ?></span>
                        </a>
                    </div>
                </div>
            </section>

            <!-- ==========================================
                 EXECUTIVE KPI METRIC CARDS (4 TILES)
                 ========================================== -->
            <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
                <!-- 1. Total Capital Deployed -->
                <div class="metric-card bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-3xl p-5 sm:p-6 shadow-xs flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Capital Deployed</span>
                            <div class="w-10 h-10 rounded-2xl bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                                <i data-lucide="wallet" class="w-5 h-5"></i>
                            </div>
                        </div>
                        <div class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                            <?= format_inr($totalInvested) ?>
                        </div>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between text-xs">
                        <span class="text-emerald-600 dark:text-emerald-400 font-semibold flex items-center gap-1">
                            <i data-lucide="shield-check" class="w-3.5 h-3.5"></i> Escrow Secured
                        </span>
                        <a href="<?= url('investor/portfolio.php') ?>" class="text-blue-600 dark:text-blue-400 hover:underline font-bold">Details &rarr;</a>
                    </div>
                </div>

                <!-- 2. Portfolio Holdings -->
                <div class="metric-card bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-3xl p-5 sm:p-6 shadow-xs flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Portfolio Holdings</span>
                            <div class="w-10 h-10 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                                <i data-lucide="briefcase" class="w-5 h-5"></i>
                            </div>
                        </div>
                        <div class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                            <?= count($portfolioInvestments) ?> <span class="text-sm font-semibold text-slate-400 dark:text-slate-500">Ventures</span>
                        </div>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between text-xs">
                        <span class="text-slate-500 dark:text-slate-400 font-medium">
                            <?= number_format($totalEquity, 2) ?>% Total Equity
                        </span>
                        <a href="<?= url('investor/portfolio.php') ?>" class="text-blue-600 dark:text-blue-400 hover:underline font-bold">View Cap Tables &rarr;</a>
                    </div>
                </div>

                <!-- 3. Live Deal Flow -->
                <div class="metric-card bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-3xl p-5 sm:p-6 shadow-xs flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Active Deal Flow</span>
                            <div class="w-10 h-10 rounded-2xl bg-purple-50 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 flex items-center justify-center">
                                <i data-lucide="sparkles" class="w-5 h-5"></i>
                            </div>
                        </div>
                        <div class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                            <?= count($recommendedStartups) ?> <span class="text-sm font-semibold text-slate-400 dark:text-slate-500">Open Rounds</span>
                        </div>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between text-xs">
                        <span class="text-purple-600 dark:text-purple-400 font-semibold flex items-center gap-1">
                            <span class="w-2 h-2 rounded-full bg-purple-500 animate-ping"></span> Live Syndicates
                        </span>
                        <a href="<?= url('investor/discover.php') ?>" class="text-blue-600 dark:text-blue-400 hover:underline font-bold">Explore &rarr;</a>
                    </div>
                </div>

                <!-- 4. Watchlist Pipeline -->
                <div class="metric-card bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-3xl p-5 sm:p-6 shadow-xs flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Saved Pipeline</span>
                            <div class="w-10 h-10 rounded-2xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                                <i data-lucide="bookmark" class="w-5 h-5"></i>
                            </div>
                        </div>
                        <div class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                            <?= $watchlistCount ?> <span class="text-sm font-semibold text-slate-400 dark:text-slate-500">Startups</span>
                        </div>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between text-xs">
                        <span class="text-slate-500 dark:text-slate-400 font-medium">Tracking Milestones</span>
                        <a href="<?= url('investor/watchlist.php') ?>" class="text-blue-600 dark:text-blue-400 hover:underline font-bold">Open Watchlist &rarr;</a>
                    </div>
                </div>
            </section>

            <!-- ==========================================
                 INSTITUTIONAL TRUST & ESCROW STRIP
                 ========================================== -->
            <div class="p-4 sm:p-5 rounded-3xl bg-gradient-to-r from-slate-900 via-blue-950 to-indigo-950 text-white shadow-sm flex flex-col md:flex-row items-center justify-between gap-4 border border-blue-900/30">
                <div class="flex items-center space-x-3.5">
                    <div class="w-11 h-11 rounded-2xl bg-white/10 flex items-center justify-center text-emerald-400 flex-shrink-0 border border-white/10">
                        <i data-lucide="shield-check" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <div class="text-sm font-bold tracking-tight flex items-center gap-2">
                            <span>SEBI Angel Syndicate & DigiLocker Verified Network</span>
                            <span class="px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 text-[10px] font-bold uppercase tracking-wider border border-emerald-400/20">AIF Compliant</span>
                        </div>
                        <div class="text-xs text-slate-300 mt-0.5">
                            All opportunities undergo MCA incorporation verification, financial audit scrutiny, and RBI-regulated escrow custody.
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2.5 flex-shrink-0 w-full md:w-auto justify-end">
                    <a href="<?= url('investor/verification.php') ?>" class="px-3.5 py-2 rounded-xl bg-white/10 hover:bg-white/20 text-white text-xs font-bold transition flex items-center gap-1.5 border border-white/10">
                        <i data-lucide="file-check" class="w-3.5 h-3.5 text-emerald-400"></i>
                        <span>Accreditation Status</span>
                    </a>
                    <a href="<?= url('investor/discover.php') ?>" class="px-4 py-2 rounded-xl bg-white hover:bg-slate-100 text-slate-900 text-xs font-extrabold transition shadow-xs">
                        Browse Deals →
                    </a>
                </div>
            </div>

            <!-- ==========================================
                 MAIN WORKSPACE: 2 COLUMNS (DEAL FLOW + THESIS)
                 ========================================== -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 sm:gap-8">

                <!-- LEFT COLUMN: CURATED LIVE ROUNDS & HOT OPPORTUNITIES (col-span-8) -->
                <div class="lg:col-span-8 space-y-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-lg sm:text-xl font-extrabold text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                                <span>Curated Live Opportunities</span>
                                <span class="px-2 py-0.5 text-xs rounded-full bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300 font-bold"><?= count($recommendedStartups) ?> Live</span>
                            </h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Verified funding rounds matching active syndicates</p>
                        </div>
                        <a href="<?= url('investor/discover.php') ?>" class="text-xs font-bold text-blue-600 dark:text-blue-400 hover:underline flex items-center gap-1">
                            <span>Browse All Ventures</span>
                            <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                        </a>
                    </div>

                    <?php if (empty($recommendedStartups)): ?>
                        <div class="py-12 px-6 text-center text-xs text-slate-500 dark:text-slate-400 border border-dashed border-slate-200 dark:border-slate-800 rounded-3xl bg-white dark:bg-slate-900">
                            <i data-lucide="folder-search" class="w-8 h-8 text-slate-400 mx-auto mb-2 opacity-60"></i>
                            <p class="font-bold text-slate-800 dark:text-slate-200">No active funding rounds available right now</p>
                            <p class="mt-1">Check back shortly or update your industry preference filters.</p>
                        </div>
                    <?php else: 
                        $featured = $recommendedStartups[0];
                        $featPct = ($featured['target_amount'] ?? 0) > 0 ? round(($featured['amount_raised'] / $featured['target_amount']) * 100) : 0;
                        $featHash = hash_id_encode($featured['id']);
                    ?>
                        <!-- Hero Featured Deal Card -->
                        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-3xl p-6 sm:p-7 shadow-xs hover:border-blue-500/40 dark:hover:border-blue-500/40 transition-all">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-5 border-b border-slate-100 dark:border-slate-800">
                                <div class="flex items-center space-x-4">
                                    <img src="<?= $featured['logo_url'] ?: 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=120' ?>"
                                        class="w-14 h-14 rounded-2xl object-cover border border-slate-200 dark:border-slate-700 flex-shrink-0 shadow-2xs">
                                    <div>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h3 class="text-lg font-bold text-slate-900 dark:text-white">
                                                <?= htmlspecialchars($featured['name']) ?>
                                            </h3>
                                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 border border-blue-200/60 dark:border-blue-800/60 uppercase tracking-wider">
                                                Featured Round
                                            </span>
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                                                <?= htmlspecialchars($featured['industry']) ?>
                                            </span>
                                        </div>
                                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 flex items-center gap-2">
                                            <span>Stage: <strong class="text-slate-700 dark:text-slate-300"><?= htmlspecialchars($featured['stage']) ?></strong></span>
                                            <span>•</span>
                                            <span>Round: <strong class="text-slate-700 dark:text-slate-300"><?= htmlspecialchars($featured['round_name']) ?></strong></span>
                                        </p>
                                    </div>
                                </div>

                                <div class="text-left sm:text-right">
                                    <div class="text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Round Progress</div>
                                    <div class="text-base font-extrabold text-blue-600 dark:text-blue-400 mt-0.5"><?= $featPct ?>% Funded</div>
                                </div>
                            </div>

                            <!-- Pitch snippet -->
                            <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-300 mt-4 leading-relaxed">
                                <?= htmlspecialchars($featured['pitch']) ?>
                            </p>

                            <!-- Key Investment Terms Grid -->
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 py-4 my-4 bg-slate-50 dark:bg-slate-800/40 rounded-2xl px-4 border border-slate-100 dark:border-slate-800 text-xs">
                                <div>
                                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Target Raise</span>
                                    <span class="font-extrabold text-slate-900 dark:text-white"><?= format_inr($featured['target_amount']) ?></span>
                                </div>
                                <div>
                                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Pre-Money Val</span>
                                    <span class="font-extrabold text-slate-900 dark:text-white"><?= format_inr($featured['valuation']) ?></span>
                                </div>
                                <div>
                                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Min Ticket</span>
                                    <span class="font-extrabold text-slate-900 dark:text-white"><?= format_inr($featured['min_investment']) ?></span>
                                </div>
                                <div>
                                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Equity Offered</span>
                                    <span class="font-extrabold text-blue-600 dark:text-blue-400"><?= htmlspecialchars($featured['equity_offered']) ?>%</span>
                                </div>
                            </div>

                            <!-- Progress Bar -->
                            <div class="mb-5">
                                <div class="flex justify-between text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1.5">
                                    <span>Raised <?= format_inr($featured['amount_raised']) ?></span>
                                    <span>Goal <?= format_inr($featured['target_amount']) ?></span>
                                </div>
                                <div class="w-full h-2.5 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                                    <div class="h-full bg-gradient-to-r from-blue-600 to-indigo-600 rounded-full transition-all duration-500" style="width: <?= min(100, $featPct) ?>%"></div>
                                </div>
                            </div>

                            <!-- CTAs -->
                            <div class="flex flex-wrap items-center gap-3">
                                <a href="<?= url('investor/startup_detail.php?id=' . $featHash) ?>"
                                    class="flex-1 sm:flex-initial px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs sm:text-sm font-bold text-center transition flex items-center justify-center gap-1.5 shadow-sm">
                                    <span>Review Deal Room</span>
                                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                                </a>
                                <a href="<?= url('company.php?id=' . $featHash) ?>" target="_blank"
                                    class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs sm:text-sm font-semibold transition flex items-center gap-1.5">
                                    <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                                    <span>Public Dossier</span>
                                </a>
                            </div>
                        </div>

                        <!-- Additional Live Rounds Grid -->
                        <?php if (count($recommendedStartups) > 1): ?>
                            <div class="space-y-3 pt-2">
                                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">More Live Syndicate Rounds</h3>
                                
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <?php for ($idx = 1; $idx < count($recommendedStartups); $idx++):
                                        $st = $recommendedStartups[$idx];
                                        $stPct = ($st['target_amount'] ?? 0) > 0 ? round(($st['amount_raised'] / $st['target_amount']) * 100) : 0;
                                        $stHash = hash_id_encode($st['id']);
                                    ?>
                                        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-5 shadow-xs hover:border-slate-300 dark:hover:border-slate-700 transition flex flex-col justify-between">
                                            <div>
                                                <div class="flex items-start justify-between gap-3 mb-3">
                                                    <div class="flex items-center space-x-3 min-w-0">
                                                        <img src="<?= $st['logo_url'] ?: 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=80' ?>"
                                                            class="w-10 h-10 rounded-xl object-cover border border-slate-200 dark:border-slate-700 flex-shrink-0">
                                                        <div class="min-w-0">
                                                            <h4 class="text-sm font-bold text-slate-900 dark:text-white truncate"><?= htmlspecialchars($st['name']) ?></h4>
                                                            <span class="text-[10px] font-semibold text-slate-500 dark:text-slate-400"><?= htmlspecialchars($st['industry']) ?> • <?= htmlspecialchars($st['stage']) ?></span>
                                                        </div>
                                                    </div>
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 flex-shrink-0">
                                                        <?= $stPct ?>%
                                                    </span>
                                                </div>

                                                <p class="text-xs text-slate-500 dark:text-slate-400 line-clamp-2 mb-4 leading-relaxed">
                                                    <?= htmlspecialchars($st['pitch']) ?>
                                                </p>
                                            </div>

                                            <div>
                                                <div class="w-full h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden mb-3">
                                                    <div class="h-full bg-blue-600 rounded-full" style="width: <?= min(100, $stPct) ?>%"></div>
                                                </div>

                                                <div class="flex items-center justify-between text-xs pt-1">
                                                    <div>
                                                        <span class="text-[10px] text-slate-400 block">Target</span>
                                                        <span class="font-bold text-slate-800 dark:text-slate-200"><?= format_inr($st['target_amount']) ?></span>
                                                    </div>
                                                    <div>
                                                        <span class="text-[10px] text-slate-400 block">Min Check</span>
                                                        <span class="font-bold text-slate-800 dark:text-slate-200"><?= format_inr($st['min_investment']) ?></span>
                                                    </div>
                                                    <a href="<?= url('investor/startup_detail.php?id=' . $stHash) ?>"
                                                        class="px-3 py-1.5 rounded-xl bg-blue-50 hover:bg-blue-100 dark:bg-blue-950/50 dark:hover:bg-blue-900/60 text-blue-700 dark:text-blue-300 font-bold text-xs transition">
                                                        Review &rarr;
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endfor; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <!-- ==========================================
                         PORTFOLIO & COMMITMENT RECORDS
                         ========================================== -->
                    <div class="pt-4 space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-lg font-extrabold text-slate-900 dark:text-white tracking-tight">Active Portfolio Holdings</h2>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Your confirmed equity commitments and cap table holdings</p>
                            </div>
                            <a href="<?= url('investor/portfolio.php') ?>" class="text-xs font-bold text-blue-600 dark:text-blue-400 hover:underline">
                                View Full Portfolio (<?= count($portfolioInvestments) ?>) &rarr;
                            </a>
                        </div>

                        <?php if (empty($portfolioInvestments)): ?>
                            <div class="py-8 px-6 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-3xl text-center text-xs text-slate-500 dark:text-slate-400">
                                <div class="w-12 h-12 rounded-2xl bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 flex items-center justify-center mx-auto mb-3">
                                    <i data-lucide="briefcase" class="w-6 h-6"></i>
                                </div>
                                <p class="font-bold text-slate-900 dark:text-white text-sm">No capital allocations deployed yet</p>
                                <p class="max-w-md mx-auto text-slate-500 dark:text-slate-400 mt-1">Browse verified startups, review comprehensive due diligence documents, and participate in active syndicate rounds.</p>
                                <a href="<?= url('investor/discover.php') ?>" class="inline-flex items-center gap-1.5 mt-4 px-4 py-2 bg-blue-600 text-white font-bold rounded-xl text-xs hover:bg-blue-700 transition shadow-xs">
                                    <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
                                    <span>Explore Active Rounds</span>
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-3xl divide-y divide-slate-100 dark:divide-slate-800/80 overflow-hidden shadow-xs">
                                <?php foreach ($portfolioInvestments as $inv): ?>
                                    <div class="p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                                        <div class="flex items-center space-x-3.5 min-w-0">
                                            <img src="<?= $inv['logo_url'] ?: 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=80' ?>"
                                                class="w-11 h-11 rounded-2xl object-cover border border-slate-200 dark:border-slate-700 flex-shrink-0">
                                            <div class="min-w-0">
                                                <div class="flex items-center gap-2">
                                                    <h4 class="text-sm font-bold text-slate-900 dark:text-white truncate"><?= htmlspecialchars($inv['company_name']) ?></h4>
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                                                        <?= htmlspecialchars($inv['industry']) ?>
                                                    </span>
                                                </div>
                                                <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 flex items-center gap-2">
                                                    <span>Round: <strong><?= htmlspecialchars($inv['round_name']) ?></strong></span>
                                                    <span>•</span>
                                                    <span>Cert: <?= htmlspecialchars($inv['certificate_number'] ?? 'CONFIRMED') ?></span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="flex items-center justify-between sm:justify-end gap-5 text-xs flex-shrink-0">
                                            <div>
                                                <span class="text-[10px] text-slate-400 uppercase font-semibold block">Committed</span>
                                                <span class="font-extrabold text-slate-900 dark:text-white"><?= format_inr($inv['amount_invested']) ?></span>
                                            </div>
                                            <div>
                                                <span class="text-[10px] text-slate-400 uppercase font-semibold block">Equity Stake</span>
                                                <span class="font-extrabold text-blue-600 dark:text-blue-400"><?= $inv['equity_allotted_percent'] ?>%</span>
                                            </div>
                                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 flex items-center gap-1">
                                                <i data-lucide="check" class="w-3 h-3"></i> Confirmed
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- RIGHT COLUMN: INVESTOR THESIS & QUICK CONTROLS (col-span-4) -->
                <div class="lg:col-span-4 space-y-6">

                    <!-- Investor Profile & Thesis Widget -->
                    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-3xl p-6 shadow-xs">
                        <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800">
                            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Your Investor Profile</span>
                            <a href="<?= url('investor/profile.php') ?>" class="text-xs font-bold text-blue-600 dark:text-blue-400 hover:underline flex items-center gap-1">
                                <span>Edit</span>
                                <i data-lucide="pencil" class="w-3 h-3"></i>
                            </a>
                        </div>

                        <div class="pt-4 flex items-center space-x-3.5">
                            <img src="<?= htmlspecialchars(get_avatar_url($user['avatar_url'] ?? '', 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=160')) ?>"
                                alt="<?= htmlspecialchars($user['name']) ?>"
                                class="w-14 h-14 rounded-full object-cover border-2 border-slate-200 dark:border-slate-700">
                            <div>
                                <h3 class="text-base font-extrabold text-slate-900 dark:text-white"><?= htmlspecialchars($user['name']) ?></h3>
                                <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                    <?= htmlspecialchars($investorProfile['investor_type'] ?? 'Accredited Angel Investor') ?>
                                </div>
                                <div class="mt-1 flex items-center gap-1 text-[11px] font-semibold text-emerald-600 dark:text-emerald-400">
                                    <i data-lucide="shield-check" class="w-3.5 h-3.5"></i>
                                    <span><?= $user['is_verified'] ? 'Accredited & Verified' : 'Accreditation Pending' ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Progress Bar -->
                        <div class="mt-5 pt-4 border-t border-slate-100 dark:border-slate-800">
                            <div class="flex justify-between text-xs font-bold mb-1.5">
                                <span class="text-slate-500 dark:text-slate-400">Profile Readiness</span>
                                <span class="text-blue-600 dark:text-blue-400"><?= $investorProgress['percentage'] ?>%</span>
                            </div>
                            <div class="w-full h-2 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                                <div class="h-full bg-blue-600 rounded-full" style="width: <?= $investorProgress['percentage'] ?>%"></div>
                            </div>
                        </div>

                        <!-- Thesis Attributes -->
                        <div class="mt-5 space-y-3 text-xs pt-4 border-t border-slate-100 dark:border-slate-800">
                            <div>
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Target Sectors</span>
                                <div class="flex flex-wrap gap-1.5">
                                    <?php 
                                    $inds = explode(',', $preferences['preferred_industries'] ?? 'FinTech, AI/SaaS, DeepTech');
                                    foreach ($inds as $ind): 
                                        $trimmed = trim($ind);
                                        if (!$trimmed) continue;
                                    ?>
                                        <span class="px-2 py-0.5 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-semibold text-[11px]">
                                            <?= htmlspecialchars($trimmed) ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div>
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Stage Focus</span>
                                <span class="font-semibold text-slate-800 dark:text-slate-200">
                                    <?= htmlspecialchars($preferences['preferred_stages'] ?? 'Pre-Seed, Seed, Pre-Series A') ?>
                                </span>
                            </div>

                            <div>
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Ticket Range</span>
                                <span class="font-bold text-slate-900 dark:text-white">
                                    <?= !empty($preferences['min_ticket']) ? format_inr($preferences['min_ticket']) : '₹1,00,000' ?> – <?= !empty($preferences['max_ticket']) ? format_inr($preferences['max_ticket']) : '₹50,00,000' ?>
                                </span>
                            </div>

                            <?php if (!empty($preferences['investment_thesis'])): ?>
                                <div>
                                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Investment Thesis</span>
                                    <p class="text-slate-600 dark:text-slate-400 italic line-clamp-3 text-[11.5px]">
                                        "<?= htmlspecialchars($preferences['investment_thesis']) ?>"
                                    </p>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="mt-6 pt-4 border-t border-slate-100 dark:border-slate-800">
                            <a href="<?= url('investor/profile.php') ?>" class="w-full py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold block text-center transition">
                                Update Investment Thesis
                            </a>
                        </div>
                    </div>

                    <!-- Quick Shortcuts Card -->
                    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-3xl p-6 shadow-xs space-y-3">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-1">Quick Shortcuts</span>

                        <a href="<?= url('investor/discover.php') ?>" class="flex items-center justify-between p-3 rounded-2xl hover:bg-slate-50 dark:hover:bg-slate-800/60 transition group border border-transparent hover:border-slate-200 dark:hover:border-slate-700">
                            <div class="flex items-center space-x-3">
                                <div class="w-9 h-9 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                                    <i data-lucide="compass" class="w-4 h-4"></i>
                                </div>
                                <div>
                                    <div class="text-xs font-bold text-slate-900 dark:text-white group-hover:text-blue-600 transition">Startup Marketplace</div>
                                    <div class="text-[11px] text-slate-500 dark:text-slate-400">Filter by sector & check size</div>
                                </div>
                            </div>
                            <i data-lucide="chevron-right" class="w-4 h-4 text-slate-400"></i>
                        </a>

                        <a href="<?= url('investor/watchlist.php') ?>" class="flex items-center justify-between p-3 rounded-2xl hover:bg-slate-50 dark:hover:bg-slate-800/60 transition group border border-transparent hover:border-slate-200 dark:hover:border-slate-700">
                            <div class="flex items-center space-x-3">
                                <div class="w-9 h-9 rounded-xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                                    <i data-lucide="bookmark" class="w-4 h-4"></i>
                                </div>
                                <div>
                                    <div class="text-xs font-bold text-slate-900 dark:text-white group-hover:text-amber-600 transition">Saved Watchlist</div>
                                    <div class="text-[11px] text-slate-500 dark:text-slate-400"><?= $watchlistCount ?> companies saved</div>
                                </div>
                            </div>
                            <i data-lucide="chevron-right" class="w-4 h-4 text-slate-400"></i>
                        </a>

                        <a href="<?= url('investor/messages.php') ?>" class="flex items-center justify-between p-3 rounded-2xl hover:bg-slate-50 dark:hover:bg-slate-800/60 transition group border border-transparent hover:border-slate-200 dark:hover:border-slate-700">
                            <div class="flex items-center space-x-3">
                                <div class="w-9 h-9 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                                    <i data-lucide="message-square" class="w-4 h-4"></i>
                                </div>
                                <div>
                                    <div class="text-xs font-bold text-slate-900 dark:text-white group-hover:text-indigo-600 transition">Founder Messages</div>
                                    <div class="text-[11px] text-slate-500 dark:text-slate-400">Direct due diligence threads</div>
                                </div>
                            </div>
                            <i data-lucide="chevron-right" class="w-4 h-4 text-slate-400"></i>
                        </a>

                        <a href="<?= url('investor/community.php') ?>" class="flex items-center justify-between p-3 rounded-2xl hover:bg-slate-50 dark:hover:bg-slate-800/60 transition group border border-transparent hover:border-slate-200 dark:hover:border-slate-700">
                            <div class="flex items-center space-x-3">
                                <div class="w-9 h-9 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                                    <i data-lucide="users" class="w-4 h-4"></i>
                                </div>
                                <div>
                                    <div class="text-xs font-bold text-slate-900 dark:text-white group-hover:text-emerald-600 transition">Investor Syndicate Forum</div>
                                    <div class="text-[11px] text-slate-500 dark:text-slate-400">Co-invest & network</div>
                                </div>
                            </div>
                            <i data-lucide="chevron-right" class="w-4 h-4 text-slate-400"></i>
                        </a>
                    </div>

                </div>

            </div>

        </main>
    </div>

    <script>
        lucide.createIcons();
        if (window.gsap) {
            gsap.from("#investor-workspace > section, #investor-workspace > div", {
                duration: 0.45,
                y: 12,
                opacity: 0,
                stagger: 0.08,
                ease: "power2.out"
            });
        }
    </script>
</body>

</html>