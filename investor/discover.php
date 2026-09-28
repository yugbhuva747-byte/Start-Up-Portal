<?php
/**
 * Investor Module: Startup Discovery & Deal Flow Feed
 * LinkedIn-Style Venture Marketplace Experience
 * High-Trust Institutional Presentation, Rich Deal Cards, Sector Filter Chips
 */
require_once __DIR__ . '/../config.php';
$user = require_auth('investor');
$db = get_db();
$pageTitle = 'Discover Startups & Deal Flow';

$search = trim($_GET['q'] ?? '');
$industry = trim($_GET['industry'] ?? '');
$stage = trim($_GET['stage'] ?? '');
$location = trim($_GET['location'] ?? '');
$tab = trim($_GET['tab'] ?? 'all');
$minFunding = (float) ($_GET['min_funding'] ?? 0);

$startups = [];
$watchlistIds = [];
$investorProfile = null;
$investorPreferences = null;
$industryCounts = [];
$stageCounts = [];
$totalMarketCapital = 0;
$totalRaisedCapital = 0;

if ($db) {
    // 1. Fetch Investor Profile & Preferences
    $ipStmt = $db->prepare("SELECT * FROM investor_profiles WHERE user_id = ?");
    $ipStmt->execute([$user['id']]);
    $investorProfile = $ipStmt->fetch();

    $prefStmt = $db->prepare("SELECT * FROM investor_preferences WHERE user_id = ?");
    $prefStmt->execute([$user['id']]);
    $investorPreferences = $prefStmt->fetch();

    // 2. Watchlist IDs for this investor
    $wlStmt = $db->prepare("SELECT company_id FROM watchlists WHERE investor_user_id = ?");
    $wlStmt->execute([$user['id']]);
    $watchlistIds = $wlStmt->fetchAll(PDO::FETCH_COLUMN);

    // 3. Category & Stage Counts for LinkedIn-style filters
    $indStmt = $db->query("
        SELECT c.industry, COUNT(*) as cnt 
        FROM companies c 
        WHERE c.verified_status = 'verified' 
        GROUP BY c.industry
    ");
    if ($indStmt) {
        $industryCounts = $indStmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    $stgStmt = $db->query("
        SELECT c.stage, COUNT(*) as cnt 
        FROM companies c 
        WHERE c.verified_status = 'verified' 
        GROUP BY c.stage
    ");
    if ($stgStmt) {
        $stageCounts = $stgStmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    // 4. Build Structured Query
    $query = "
        SELECT c.*, 
               fr.id as round_id, fr.round_name, fr.target_amount, fr.amount_raised, fr.min_investment, fr.valuation, fr.equity_offered, fr.status as round_status, fr.end_date, fr.purpose,
               u.id as founder_user_id, u.name as founder_name, u.avatar_url as founder_avatar,
               cf.designation as founder_designation,
               (SELECT COUNT(*) FROM watchlists WHERE company_id = c.id) as saves_count,
               (SELECT COUNT(*) FROM company_documents WHERE company_id = c.id) as docs_count
        FROM companies c
        LEFT JOIN funding_rounds fr ON c.id = fr.company_id
        LEFT JOIN company_founders cf ON c.id = cf.company_id
        LEFT JOIN users u ON cf.user_id = u.id
        WHERE c.verified_status = 'verified'
    ";
    $params = [];

    // Filter by tab
    if ($tab === 'saved') {
        if (!empty($watchlistIds)) {
            $placeholders = implode(',', array_fill(0, count($watchlistIds), '?'));
            $query .= " AND c.id IN ($placeholders)";
            $params = array_merge($params, $watchlistIds);
        } else {
            $query .= " AND 1=0";
        }
    } elseif ($tab === 'trending') {
        $query .= " AND fr.amount_raised > 0";
    } elseif ($tab === 'seed') {
        $query .= " AND (c.stage LIKE '%Seed%' OR c.stage = 'Pre-Seed')";
    } elseif ($tab === 'series_a') {
        $query .= " AND (c.stage LIKE '%Series A%')";
    } elseif ($tab === 'ai') {
        $query .= " AND c.industry = 'AI/SaaS'";
    } elseif ($tab === 'fintech') {
        $query .= " AND c.industry = 'FinTech'";
    } elseif ($tab === 'cleantech') {
        $query .= " AND c.industry = 'CleanTech'";
    }

    if (!empty($search)) {
        $query .= " AND (c.name LIKE ? OR c.pitch LIKE ? OR c.description LIKE ? OR u.name LIKE ? OR c.industry LIKE ?)";
        $sTerm = "%{$search}%";
        $params[] = $sTerm;
        $params[] = $sTerm;
        $params[] = $sTerm;
        $params[] = $sTerm;
        $params[] = $sTerm;
    }

    if (!empty($industry)) {
        $query .= " AND c.industry = ?";
        $params[] = $industry;
    }

    if (!empty($stage)) {
        $query .= " AND c.stage = ?";
        $params[] = $stage;
    }

    if (!empty($location)) {
        $query .= " AND (c.city LIKE ? OR c.state LIKE ?)";
        $params[] = "%{$location}%";
        $params[] = "%{$location}%";
    }

    if ($minFunding > 0) {
        $query .= " AND fr.target_amount >= ?";
        $params[] = $minFunding;
    }

    $query .= " ORDER BY (fr.status = 'LIVE') DESC, fr.amount_raised DESC, c.created_at DESC";

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $startups = $stmt->fetchAll();

    foreach ($startups as $st) {
        $totalMarketCapital += (float) ($st['target_amount'] ?? 0);
        $totalRaisedCapital += (float) ($st['amount_raised'] ?? 0);
    }
}

// Watchlist toggle handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_watchlist') {
    if (verify_csrf($_POST['csrf_token'] ?? '')) {
        $compHash = $_POST['company_id'] ?? '';
        $targetCompId = hash_id_decode($compHash);
        if ($targetCompId > 0) {
            $chk = $db->prepare("SELECT id FROM watchlists WHERE investor_user_id = ? AND company_id = ?");
            $chk->execute([$user['id'], $targetCompId]);
            if ($chk->fetch()) {
                $db->prepare("DELETE FROM watchlists WHERE investor_user_id = ? AND company_id = ?")->execute([$user['id'], $targetCompId]);
            } else {
                $db->prepare("INSERT INTO watchlists (investor_user_id, company_id) VALUES (?, ?)")->execute([$user['id'], $targetCompId]);
            }
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> • <?= APP_NAME ?></title>
    <?php include __DIR__ . '/../includes/investor/head.php'; ?>
    <style>
        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            background-color: #F3F4F6;
            color: #0F172A;
        }

        html.dark body {
            background-color: #0B1120 !important;
            color: #F8FAFC !important;
        }

        /* LinkedIn Feed Post Card Style */
        .linkedin-card {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.04), 0 1px 2px -1px rgba(0, 0, 0, 0.02);
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        }

        html.dark .linkedin-card {
            background: #111827 !important;
            border-color: #1F2937 !important;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3);
        }

        .linkedin-card:hover {
            border-color: #0A66C2;
            box-shadow: 0 8px 16px -4px rgba(10, 102, 194, 0.08), 0 4px 6px -2px rgba(0, 0, 0, 0.03);
            transform: translateY(-1px);
        }

        html.dark .linkedin-card:hover {
            border-color: #38BDF8 !important;
            box-shadow: 0 8px 24px -4px rgba(56, 189, 248, 0.18), 0 0 16px -2px rgba(56, 189, 248, 0.12) !important;
            transform: translateY(-2px);
        }

        .badge-linkedin {
            background-color: #EAF2FF;
            color: #0A66C2;
            border: 1px solid rgba(10, 102, 194, 0.2);
        }

        html.dark .badge-linkedin {
            background-color: rgba(56, 189, 248, 0.12) !important;
            color: #38BDF8 !important;
            border-color: rgba(56, 189, 248, 0.25) !important;
        }

        /* Custom scrollbar for horizontal chip bar */
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }
        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
    </style>
</head>

<body class="bg-[#F3F4F6] dark:bg-[#0B1120] text-slate-900 dark:text-slate-100 flex min-h-screen">

    <!-- Investor Navigation Sidebar -->
    <?php include __DIR__ . '/../includes/investor/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0">
        <!-- Top Navbar -->
        <?php include __DIR__ . '/../includes/investor/navbar.php'; ?>

        <main class="w-full max-w-7xl mx-auto p-4 sm:p-6 lg:p-8 space-y-6" id="discover-main">

            <!-- ========================================================
                 1. LINKEDIN-STYLE PLATFORM BANNER & MARKET STATUS
                 ======================================================== -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 sm:p-6 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-5">
                <div class="space-y-1.5 max-w-2xl">
                    <div class="flex items-center space-x-2">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold badge-linkedin">
                            <i data-lucide="shield-check" class="w-3.5 h-3.5 mr-1"></i>
                            SEBI & MCA Verified Dealflow
                        </span>
                        <span class="text-xs text-slate-500 dark:text-slate-400 font-semibold">• Live Syndicate Allocations</span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight leading-tight">
                        Discover High-Growth Startups & Syndicates
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                        Back top-tier founders raising institutional capital under audited MCA due diligence and RBI-regulated escrow custody.
                    </p>
                </div>

                <div class="flex items-center gap-3 sm:gap-4 flex-shrink-0">
                    <div class="bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700/60 rounded-xl px-4 py-2.5 text-center">
                        <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Live Deals</div>
                        <div class="text-lg sm:text-xl font-black text-[#0A66C2] dark:text-blue-400"><?= count($startups) ?> Ventures</div>
                    </div>
                    <div class="bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700/60 rounded-xl px-4 py-2.5 text-center">
                        <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Active Capital</div>
                        <div class="text-lg sm:text-xl font-black text-emerald-600 dark:text-emerald-400"><?= format_inr($totalMarketCapital) ?></div>
                    </div>
                </div>
            </div>

            <!-- ========================================================
                 2. LINKEDIN-STYLE HORIZONTAL FILTER PILLS & SEARCH BAR
                 ======================================================== -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 sm:p-5 shadow-xs space-y-4">
                <!-- Search Input Bar with Quick Filter Controls -->
                <form action="<?= url('investor/discover.php') ?>" method="GET" class="space-y-3">
                    <div class="flex flex-col md:flex-row items-center gap-3">
                        <div class="relative flex-1 w-full">
                            <i data-lucide="search" class="w-4.5 h-4.5 text-slate-400 dark:text-slate-500 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                            <input type="text" name="q" value="<?= htmlspecialchars($search) ?>"
                                placeholder="Search by startup name, founder, industry keywords, tech stack..."
                                class="w-full pl-10 pr-10 py-3 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-slate-100 text-xs sm:text-sm font-medium outline-none focus:bg-white dark:focus:bg-slate-900 focus:border-[#0A66C2] dark:focus:border-blue-400 focus:ring-2 focus:ring-[#0A66C2]/15 transition">
                            <?php if (!empty($search)): ?>
                                <a href="<?= url('investor/discover.php') ?>" class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-white">
                                    <i data-lucide="x" class="w-4 h-4"></i>
                                </a>
                            <?php endif; ?>
                        </div>

                        <!-- Dropdowns for Precise Filter -->
                        <div class="flex items-center gap-2 w-full md:w-auto">
                            <select name="industry" onchange="this.form.submit()"
                                class="w-1/2 md:w-44 px-3.5 py-3 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-800 dark:text-slate-200 text-xs font-semibold outline-none focus:border-[#0A66C2]">
                                <option value="">All Industries</option>
                                <?php foreach (['AI/SaaS', 'FinTech', 'HealthTech', 'CleanTech', 'DeepTech', 'E-Commerce', 'EdTech'] as $ind): ?>
                                    <option value="<?= $ind ?>" <?= $industry === $ind ? 'selected' : '' ?>><?= $ind ?> <?= isset($industryCounts[$ind]) ? "({$industryCounts[$ind]})" : '' ?></option>
                                <?php endforeach; ?>
                            </select>

                            <select name="stage" onchange="this.form.submit()"
                                class="w-1/2 md:w-40 px-3.5 py-3 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-800 dark:text-slate-200 text-xs font-semibold outline-none focus:border-[#0A66C2]">
                                <option value="">All Stages</option>
                                <?php foreach (['Idea / MVP', 'Pre-Seed', 'Seed', 'Pre-Series A', 'Series A'] as $stg): ?>
                                    <option value="<?= $stg ?>" <?= $stage === $stg ? 'selected' : '' ?>><?= $stg ?> <?= isset($stageCounts[$stg]) ? "({$stageCounts[$stg]})" : '' ?></option>
                                <?php endforeach; ?>
                            </select>

                            <button type="submit"
                                class="px-5 py-3 bg-[#0A66C2] hover:bg-[#004182] text-white text-xs font-bold rounded-xl transition flex items-center justify-center space-x-1.5 shadow-sm cursor-pointer">
                                <i data-lucide="sliders" class="w-4 h-4"></i>
                                <span class="hidden sm:inline">Filter</span>
                            </button>
                        </div>
                    </div>
                </form>

                <!-- LinkedIn-style Horizontal Chip Tags -->
                <div class="flex items-center space-x-2 overflow-x-auto no-scrollbar pt-1 border-t border-slate-100 dark:border-slate-800 text-xs">
                    <span class="text-[11px] font-extrabold uppercase text-slate-400 dark:text-slate-500 tracking-wider whitespace-nowrap mr-1">Trending:</span>

                    <?php
                    $quickChips = [
                        ['id' => 'all', 'label' => 'All Opportunities', 'icon' => 'globe'],
                        ['id' => 'trending', 'label' => '🔥 Top Active Deals', 'icon' => 'trending-up'],
                        ['id' => 'ai', 'label' => 'AI & Copilots', 'icon' => 'cpu'],
                        ['id' => 'fintech', 'label' => 'FinTech & BFSI', 'icon' => 'credit-card'],
                        ['id' => 'healthtech', 'label' => 'HealthTech', 'icon' => 'activity'],
                        ['id' => 'cleantech', 'label' => 'CleanTech / EV', 'icon' => 'zap'],
                        ['id' => 'seed', 'label' => 'Seed Rounds', 'icon' => 'sprout'],
                        ['id' => 'saved', 'label' => '📌 My Saved Watchlist (' . count($watchlistIds) . ')', 'icon' => 'bookmark']
                    ];
                    ?>

                    <?php foreach ($quickChips as $chip):
                        $isActive = ($tab === $chip['id']);
                        $chipUrl = url('investor/discover.php?tab=' . $chip['id']);
                        if (!empty($search)) $chipUrl .= '&q=' . urlencode($search);
                    ?>
                        <a href="<?= $chipUrl ?>"
                            class="px-3.5 py-1.5 rounded-full font-bold whitespace-nowrap transition-all duration-150 flex items-center space-x-1.5 <?= $isActive ? 'bg-[#0A66C2] text-white shadow-xs' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' ?>">
                            <span><?= $chip['label'] ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- ========================================================
                 3. LINKEDIN-STYLE 2-COLUMN DEALFLOW FEED & SIDE PANEL
                 ======================================================== -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

                <!-- ----------------------------------------------------
                     LEFT COLUMN: LINKEDIN VENTURE CARDS STREAM (8 COLS)
                     ---------------------------------------------------- -->
                <div class="lg:col-span-8 space-y-5">

                    <?php if (empty($startups)): ?>
                        <!-- Empty State -->
                        <div class="linkedin-card rounded-2xl p-12 text-center">
                            <div class="w-14 h-14 rounded-full bg-blue-50 dark:bg-blue-950/60 text-[#0A66C2] dark:text-blue-400 flex items-center justify-center mx-auto mb-3.5">
                                <i data-lucide="search-x" class="w-7 h-7"></i>
                            </div>
                            <h3 class="text-base font-extrabold text-slate-900 dark:text-white mb-1">No Matching Syndicates Found</h3>
                            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 max-w-md mx-auto leading-relaxed">
                                We couldn't find any active venture deals matching your query. Try broadening your industry or stage filters.
                            </p>
                            <a href="<?= url('investor/discover.php') ?>"
                                class="inline-flex items-center space-x-2 mt-4 px-4 py-2 bg-[#0A66C2] text-white rounded-xl text-xs font-bold hover:bg-[#004182] transition">
                                <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                                <span>Reset All Filters</span>
                            </a>
                        </div>
                    <?php else: ?>

                        <?php foreach ($startups as $s):
                            $hashId = hash_id_encode($s['id']);
                            $isSaved = in_array($s['id'], $watchlistIds);
                            $targetAmount = (float) ($s['target_amount'] ?? 0);
                            $raisedAmount = (float) ($s['amount_raised'] ?? 0);
                            $pct = $targetAmount > 0 ? round(($raisedAmount / $targetAmount) * 100) : 0;
                            $roundHash = !empty($s['round_id']) ? hash_id_encode($s['round_id']) : '';
                            $founderHash = !empty($s['founder_user_id']) ? hash_id_encode($s['founder_user_id']) : '';
                            $isLiveRound = in_array($s['round_status'] ?? '', ['LIVE', 'PARTIALLY_FUNDED']);
                        ?>

                            <!-- SINGLE VENTURE DEAL POST CARD -->
                            <article class="linkedin-card rounded-2xl overflow-hidden p-5 sm:p-6 space-y-4 relative">

                                <!-- Card Header: Founder Endorsement & Deal Timing -->
                                <div class="flex items-center justify-between pb-3.5 border-b border-slate-100 dark:border-slate-800 text-xs">
                                    <div class="flex items-center space-x-3 min-w-0">
                                        <img src="<?= $s['founder_avatar'] ?: 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=80' ?>"
                                            class="w-9 h-9 rounded-full object-cover border border-slate-200 dark:border-slate-700 flex-shrink-0"
                                            alt="<?= htmlspecialchars($s['founder_name'] ?? 'Founder') ?>">
                                        <div class="min-w-0">
                                            <div class="flex items-center space-x-1.5 font-bold text-slate-900 dark:text-white truncate">
                                                <span class="truncate"><?= htmlspecialchars($s['founder_name'] ?? 'Founding Team') ?></span>
                                                <span class="text-slate-400 font-normal">• 2nd</span>
                                            </div>
                                            <div class="text-[11px] text-slate-500 dark:text-slate-400 font-medium truncate">
                                                <?= htmlspecialchars($s['founder_designation'] ?? 'Founder & CEO') ?> at <?= htmlspecialchars($s['name']) ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex items-center space-x-2 flex-shrink-0">
                                        <?php if ($isLiveRound): ?>
                                            <span class="hidden sm:inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/40">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5 animate-pulse"></span>
                                                ACCEPTING CAPITAL
                                            </span>
                                        <?php endif; ?>

                                        <!-- Watchlist Toggle Action Button -->
                                        <form action="<?= url('investor/discover.php') ?>" method="POST" class="inline">
                                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                            <input type="hidden" name="action" value="toggle_watchlist">
                                            <input type="hidden" name="company_id" value="<?= $hashId ?>">
                                            <button type="submit"
                                                title="<?= $isSaved ? 'Remove from Saved' : 'Save to Watchlist' ?>"
                                                class="p-2 rounded-xl border transition cursor-pointer <?= $isSaved ? 'bg-amber-50 dark:bg-amber-950/40 border-amber-300 dark:border-amber-700/60 text-amber-600 dark:text-amber-400' : 'bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 hover:text-[#0A66C2] dark:hover:text-blue-400 hover:border-[#0A66C2] dark:hover:border-blue-500 hover:bg-blue-50/50 dark:hover:bg-slate-700' ?>">
                                                <i data-lucide="bookmark" class="w-4 h-4 <?= $isSaved ? 'fill-amber-500' : '' ?>"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>

                                <!-- Company Core Identity Strip -->
                                <div class="flex items-start space-x-4">
                                    <a href="<?= url('investor/startup_detail.php?id=' . $hashId) ?>" class="flex-shrink-0 group">
                                        <img src="<?= $s['logo_url'] ?: 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=120' ?>"
                                            class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl object-cover border border-slate-200 dark:border-slate-700 shadow-2xs group-hover:scale-105 transition-transform duration-200"
                                            alt="<?= htmlspecialchars($s['name']) ?>">
                                    </a>

                                    <div class="flex-1 min-w-0">
                                        <div class="flex flex-wrap items-center gap-2 mb-1">
                                            <a href="<?= url('investor/startup_detail.php?id=' . $hashId) ?>"
                                                class="text-lg sm:text-xl font-extrabold text-slate-900 dark:text-white hover:text-[#0A66C2] dark:hover:text-blue-400 transition tracking-tight">
                                                <?= htmlspecialchars($s['name']) ?>
                                            </a>
                                            <!-- LinkedIn Blue Verified Badge -->
                                            <span class="inline-flex items-center text-[#0A66C2] dark:text-blue-400" title="MCA & SEBI Diligence Verified">
                                                <i data-lucide="badge-check" class="w-4.5 h-4.5 fill-[#0A66C2]/15"></i>
                                            </span>
                                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                                <?= htmlspecialchars($s['industry']) ?>
                                            </span>
                                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-blue-50 dark:bg-blue-950/60 text-[#0A66C2] dark:text-blue-300">
                                                <?= htmlspecialchars($s['stage']) ?>
                                            </span>
                                            <?php if (!empty($s['city'])): ?>
                                                <span class="text-xs text-slate-500 dark:text-slate-400 flex items-center gap-1 font-medium">
                                                    <i data-lucide="map-pin" class="w-3.5 h-3.5"></i>
                                                    <?= htmlspecialchars($s['city']) ?><?= !empty($s['state']) ? ', ' . htmlspecialchars($s['state']) : '' ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>

                                        <p class="text-xs sm:text-sm font-semibold text-slate-800 dark:text-slate-200 leading-snug">
                                            <?= htmlspecialchars($s['pitch']) ?>
                                        </p>
                                    </div>
                                </div>

                                <!-- Brief Description Synopsis -->
                                <?php if (!empty($s['description'])): ?>
                                    <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed line-clamp-2">
                                        <?= htmlspecialchars($s['description']) ?>
                                    </p>
                                <?php endif; ?>

                                <!-- Deal Metadata & Business Model Tags -->
                                <div class="flex flex-wrap items-center gap-2 text-xs">
                                    <?php if (!empty($s['business_model'])): ?>
                                        <span class="px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-semibold flex items-center gap-1">
                                            <i data-lucide="briefcase" class="w-3 h-3 text-slate-500"></i>
                                            <?= htmlspecialchars($s['business_model']) ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if (!empty($s['cin_number'])): ?>
                                        <span class="px-2.5 py-1 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 font-semibold flex items-center gap-1">
                                            <i data-lucide="file-check" class="w-3 h-3 text-emerald-600"></i>
                                            CIN: <?= htmlspecialchars($s['cin_number']) ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if (!empty($s['docs_count']) && $s['docs_count'] > 0): ?>
                                        <span class="px-2.5 py-1 rounded-lg bg-purple-50 dark:bg-purple-950/40 text-purple-700 dark:text-purple-300 font-semibold flex items-center gap-1">
                                            <i data-lucide="files" class="w-3 h-3 text-purple-600"></i>
                                            <?= $s['docs_count'] ?> Verified Documents
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <!-- Financial Metrics Infobox (Clean LinkedIn Financial Grid) -->
                                <?php if (!empty($targetAmount)): ?>
                                    <div class="bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700/60 rounded-xl p-4 space-y-3">
                                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                                            <div>
                                                <span class="text-[10.5px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 block">Target Round</span>
                                                <span class="text-sm font-black text-slate-900 dark:text-white"><?= format_inr($targetAmount) ?></span>
                                            </div>
                                            <div>
                                                <span class="text-[10.5px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 block">Valuation</span>
                                                <span class="text-sm font-black text-slate-900 dark:text-white"><?= !empty($s['valuation']) ? format_inr($s['valuation']) : 'Confidential' ?></span>
                                            </div>
                                            <div>
                                                <span class="text-[10.5px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 block">Min Commitment</span>
                                                <span class="text-sm font-black text-[#0A66C2] dark:text-blue-400"><?= !empty($s['min_investment']) ? format_inr($s['min_investment']) : '₹1,00,000' ?></span>
                                            </div>
                                            <div>
                                                <span class="text-[10.5px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 block">Equity Offered</span>
                                                <span class="text-sm font-black text-emerald-600 dark:text-emerald-400"><?= !empty($s['equity_offered']) ? htmlspecialchars($s['equity_offered']) . '%' : 'Direct Safe/CCPS' ?></span>
                                            </div>
                                        </div>

                                        <!-- Raised Progress Track -->
                                        <div>
                                            <div class="flex justify-between items-center text-xs font-bold mb-1.5">
                                                <span class="text-slate-600 dark:text-slate-400 flex items-center gap-1.5">
                                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                                    <?= format_inr($raisedAmount) ?> Committed
                                                </span>
                                                <span class="text-[#0A66C2] dark:text-blue-400 font-extrabold"><?= $pct ?>% Raised</span>
                                            </div>
                                            <div class="w-full h-2 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
                                                <div class="h-full bg-gradient-to-r from-[#0A66C2] to-emerald-500 rounded-full transition-all duration-500" style="width: <?= min(100, $pct) ?>%"></div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- LinkedIn Action Bar -->
                                <div class="flex flex-wrap items-center justify-between gap-3 pt-2">
                                    <div class="flex items-center space-x-2">
                                        <a href="<?= url('investor/startup_detail.php?id=' . $hashId) ?>"
                                            class="px-4 sm:px-5 py-2.5 rounded-xl bg-[#0A66C2] hover:bg-[#004182] text-white text-xs sm:text-sm font-bold transition flex items-center space-x-1.5 shadow-sm">
                                            <span>Review Due Diligence</span>
                                            <i data-lucide="arrow-right" class="w-4 h-4"></i>
                                        </a>

                                        <?php if ($isLiveRound && !empty($roundHash)): ?>
                                            <a href="<?= url('investor/invest.php?round=' . $roundHash) ?>"
                                                class="px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs sm:text-sm font-bold transition flex items-center space-x-1.5 shadow-sm">
                                                <i data-lucide="wallet" class="w-3.5 h-3.5"></i>
                                                <span>Invest Now</span>
                                            </a>
                                        <?php endif; ?>
                                    </div>

                                    <div class="flex items-center space-x-2">
                                        <?php if (!empty($founderHash)): ?>
                                            <a href="<?= url('investor/messages.php?founder=' . $founderHash . '&company=' . $hashId) ?>"
                                                class="px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 hover:bg-blue-50 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:text-[#0A66C2] dark:hover:text-blue-400 hover:border-[#0A66C2]/40 dark:hover:border-blue-500/40 text-xs font-bold transition flex items-center space-x-1.5">
                                                <i data-lucide="message-circle" class="w-4 h-4 text-[#0A66C2] dark:text-blue-400"></i>
                                                <span class="hidden sm:inline">Direct Founder Chat</span>
                                            </a>
                                        <?php endif; ?>

                                        <button type="button" onclick="copyDealLink('<?= url('investor/startup_detail.php?id=' . $hashId) ?>')"
                                            class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white hover:border-slate-300 dark:hover:border-slate-600 transition cursor-pointer"
                                            title="Copy Deal Link">
                                            <i data-lucide="share-2" class="w-4 h-4"></i>
                                        </button>
                                    </div>
                                </div>

                            </article>
                        <?php endforeach; ?>

                    <?php endif; ?>

                </div>

                <!-- ----------------------------------------------------
                     RIGHT COLUMN: LINKEDIN SYNDICATE WIDGETS (4 COLS)
                     ---------------------------------------------------- -->
                <aside class="lg:col-span-4 space-y-5 sticky top-20">

                    <!-- Investor Profile Snapshot Card -->
                    <div class="linkedin-card rounded-2xl p-5 space-y-4">
                        <div class="flex items-center space-x-3.5">
                            <img src="<?= $user['avatar_url'] ?: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=100' ?>"
                                class="w-12 h-12 rounded-full object-cover border-2 border-[#0A66C2]/30 shadow-xs"
                                alt="<?= htmlspecialchars($user['name']) ?>">
                            <div class="min-w-0">
                                <div class="text-sm font-black text-slate-900 dark:text-white truncate">
                                    <?= htmlspecialchars($user['name']) ?>
                                </div>
                                <div class="text-xs text-slate-500 dark:text-slate-400 truncate">
                                    <?= htmlspecialchars($investorProfile['investor_type'] ?? 'Accredited Angel Lead') ?>
                                </div>
                            </div>
                        </div>

                        <div class="pt-3 border-t border-slate-100 dark:border-slate-800 space-y-2 text-xs">
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500 dark:text-slate-400">Accreditation:</span>
                                <span class="font-bold text-emerald-600 flex items-center gap-1">
                                    <i data-lucide="shield-check" class="w-3.5 h-3.5"></i>
                                    SEBI Verified Angel
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500 dark:text-slate-400">Target Ticket:</span>
                                <span class="font-bold text-slate-800 dark:text-slate-200">
                                    <?= !empty($investorPreferences['min_ticket']) ? format_inr($investorPreferences['min_ticket']) : '₹2.5L' ?> – <?= !empty($investorPreferences['max_ticket']) ? format_inr($investorPreferences['max_ticket']) : '₹25L' ?>
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500 dark:text-slate-400">Preferred Sectors:</span>
                                <span class="font-semibold text-slate-800 dark:text-slate-200 truncate max-w-[150px]">
                                    <?= htmlspecialchars($investorPreferences['preferred_industries'] ?? 'AI, FinTech, SaaS') ?>
                                </span>
                            </div>
                        </div>

                        <a href="<?= url('investor/profile.php') ?>"
                            class="w-full py-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 hover:bg-[#EAF2FF] dark:hover:bg-slate-700 text-[#0A66C2] dark:text-blue-400 hover:text-[#004182] dark:hover:text-blue-300 hover:border-[#0A66C2]/40 dark:hover:border-blue-500/40 text-xs font-bold transition flex items-center justify-center space-x-1.5 border border-slate-200 dark:border-slate-700">
                            <i data-lucide="sliders-horizontal" class="w-3.5 h-3.5"></i>
                            <span>Edit Investment Criteria</span>
                        </a>
                    </div>

                    <!-- Trending Venture Sectors Widget -->
                    <div class="linkedin-card rounded-2xl p-5 space-y-3.5">
                        <div class="flex items-center justify-between">
                            <h3 class="text-xs font-black uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                Top Sector Activity
                            </h3>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-blue-50 dark:bg-blue-950/60 text-[#0A66C2] dark:text-blue-300">
                                Q1 2026
                            </span>
                        </div>

                        <div class="space-y-2 text-xs">
                            <?php foreach ($industryCounts as $indName => $cnt): ?>
                                <a href="<?= url('investor/discover.php?industry=' . urlencode($indName)) ?>"
                                    class="flex items-center justify-between p-2.5 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800/80 transition <?= $industry === $indName ? 'bg-blue-50 dark:bg-blue-950/50 text-[#0A66C2] font-bold' : 'text-slate-700 dark:text-slate-300' ?>">
                                    <span class="flex items-center space-x-2">
                                        <i data-lucide="hash" class="w-3.5 h-3.5 text-slate-400"></i>
                                        <span><?= htmlspecialchars($indName) ?></span>
                                    </span>
                                    <span class="text-xs font-bold text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-700/60 px-2 py-0.5 rounded-md">
                                        <?= $cnt ?> deals
                                    </span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Platform Regulatory & Security Seal -->
                    <div class="linkedin-card rounded-2xl p-5 space-y-3 bg-gradient-to-br from-white to-slate-50 dark:from-slate-900 dark:to-slate-850">
                        <div class="flex items-center space-x-2 text-[#0A66C2] dark:text-blue-400 font-black text-xs uppercase tracking-wider">
                            <i data-lucide="shield-alert" class="w-4 h-4"></i>
                            <span>Syndicate Security Safeguard</span>
                        </div>

                        <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
                            Every transaction on this portal strictly adheres to SEBI Angel Syndicate Guidelines with RBI-regulated Escrow Bank custody.
                        </p>

                        <div class="pt-2 border-t border-slate-100 dark:border-slate-800 space-y-1.5 text-[11px] font-semibold text-slate-500 dark:text-slate-400">
                            <div class="flex items-center space-x-2">
                                <i data-lucide="check" class="w-3.5 h-3.5 text-emerald-500"></i>
                                <span>100% MCA21 Verified Incorporation</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <i data-lucide="check" class="w-3.5 h-3.5 text-emerald-500"></i>
                                <span>DigiLocker Identity Verified Founders</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <i data-lucide="check" class="w-3.5 h-3.5 text-emerald-500"></i>
                                <span>Zero-Fee Syndicate Data Rooms</span>
                            </div>
                        </div>
                    </div>

                </aside>
            </div>

        </main>
    </div>

    <!-- Deal Link Copied Toast -->
    <div id="toast-deal-link" class="fixed bottom-6 right-6 z-50 transform translate-y-20 opacity-0 transition-all duration-300 bg-slate-900 text-white text-xs font-bold px-4 py-3 rounded-xl shadow-xl flex items-center space-x-2">
        <i data-lucide="check-circle" class="w-4 h-4 text-emerald-400"></i>
        <span>Deal Room link copied to clipboard!</span>
    </div>

    <script>
        lucide.createIcons();
        gsap.from("#discover-main", { duration: 0.35, y: 10, opacity: 0, ease: "power2.out" });

        function copyDealLink(url) {
            navigator.clipboard.writeText(url).then(() => {
                const toast = document.getElementById('toast-deal-link');
                if (toast) {
                    toast.classList.remove('translate-y-20', 'opacity-0');
                    setTimeout(() => {
                        toast.classList.add('translate-y-20', 'opacity-0');
                    }, 2500);
                }
            });
        }
    </script>
</body>

</html>