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
$industryCounts = [];
$stageCounts = [];
$totalMarketCapital = 0;
$totalRaisedCapital = 0;

if ($db) {
    // 1. Watchlist IDs for this investor
    $wlStmt = $db->prepare("SELECT company_id FROM watchlists WHERE investor_user_id = ?");
    $wlStmt->execute([$user['id']]);
    $watchlistIds = $wlStmt->fetchAll(PDO::FETCH_COLUMN);

    // 2. Category & Stage Counts for LinkedIn-style filters
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

/**
 * Helper to retrieve rich company showcase gallery images
 */
function get_company_gallery_images($company) {
    $industry = strtolower($company['industry'] ?? '');
    $compName = strtolower($company['name'] ?? '');

    if (strpos($compName, 'techpulse') !== false || strpos($industry, 'ai') !== false || strpos($industry, 'saas') !== false) {
        return [
            ['url' => 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=1200&auto=format&fit=crop&q=80', 'title' => 'Enterprise AI Risk & Compliance Platform', 'caption' => 'Multi-agent LLM risk assessment pipeline automated for BFSI enterprises.'],
            ['url' => 'https://images.unsplash.com/photo-1519389950473-47ba0277781c?w=1200&auto=format&fit=crop&q=80', 'title' => 'Core AI Engineering Team', 'caption' => 'Bengaluru engineering hub building neural compliance pipelines.'],
            ['url' => 'https://images.unsplash.com/photo-1451187580459-43490279c0fa?w=1200&auto=format&fit=crop&q=80', 'title' => 'Scalable Cloud Infrastructure', 'caption' => 'Multi-tenant high-throughput inference cluster running with enterprise-grade SLA.'],
            ['url' => 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=1200&auto=format&fit=crop&q=80', 'title' => 'Real-Time Financial Dashboard', 'caption' => 'Audit metrics and executive summaries generated in sub-second latency.']
        ];
    } elseif (strpos($compName, 'biozenith') !== false || strpos($industry, 'health') !== false || strpos($industry, 'bio') !== false) {
        return [
            ['url' => 'https://images.unsplash.com/photo-1576086213369-97a306d36557?w=1200&auto=format&fit=crop&q=80', 'title' => 'Advanced Diagnostics Laboratory', 'caption' => 'Non-invasive micro-spectroscopy clinical diagnostics workstation.'],
            ['url' => 'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?w=1200&auto=format&fit=crop&q=80', 'title' => 'Point-of-Care Micro-Spectrometer', 'caption' => '3-minute portable blood biomarker detection system.'],
            ['url' => 'https://images.unsplash.com/photo-1532187863486-abf9dbad1b69?w=1200&auto=format&fit=crop&q=80', 'title' => 'Hospital Trial Validation', 'caption' => 'Active clinical validation underway across top hospital chains in Mumbai.'],
            ['url' => 'https://images.unsplash.com/photo-1579684385127-1ef15d508118?w=1200&auto=format&fit=crop&q=80', 'title' => 'Instant Cloud Telemetry Sync', 'caption' => 'Encrypted diagnostic reports delivered directly to doctors and patients.']
        ];
    } elseif (strpos($compName, 'solaris') !== false || strpos($industry, 'clean') !== false || strpos($industry, 'ev') !== false || strpos($industry, 'mobility') !== false) {
        return [
            ['url' => 'https://images.unsplash.com/photo-1508873696983-2df5293cb32f?w=1200&auto=format&fit=crop&q=80', 'title' => 'Automated Solar Swapping Station', 'caption' => 'Rapid 90-second automated battery swap hub designed for commercial delivery fleets.'],
            ['url' => 'https://images.unsplash.com/photo-1558981403-c5f9899a28bc?w=1200&auto=format&fit=crop&q=80', 'title' => 'Commercial EV Fleet Deployment', 'caption' => 'Over 120+ active 2W and 3W electric delivery fleets utilizing our hubs daily.'],
            ['url' => 'https://images.unsplash.com/photo-1497435334941-8c899ee9e8e9?w=1200&auto=format&fit=crop&q=80', 'title' => 'Solar Microgrid Architecture', 'caption' => 'Zero-carbon grid-independent energy storage and intelligent charging grid.'],
            ['url' => 'https://images.unsplash.com/photo-1513836279014-a89f7a76ae86?w=1200&auto=format&fit=crop&q=80', 'title' => 'IoT Battery Management Telemetry', 'caption' => 'Continuous live health monitoring, thermal safety and cycle-life optimization.']
        ];
    } elseif (strpos($industry, 'fintech') !== false) {
        return [
            ['url' => 'https://images.unsplash.com/photo-1559526324-4b87b5e36e44?w=1200&auto=format&fit=crop&q=80', 'title' => 'Next-Gen Financial Rails', 'caption' => 'Unified payment infrastructure with multi-currency real-time settlements.'],
            ['url' => 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=1200&auto=format&fit=crop&q=80', 'title' => 'Algorithmic Risk Management Engine', 'caption' => 'AI-driven automated underwriting and merchant risk scoring.'],
            ['url' => 'https://images.unsplash.com/photo-1563986768609-322da13575f3?w=1200&auto=format&fit=crop&q=80', 'title' => 'Connected Merchant Terminal', 'caption' => 'Next-generation contactless POS and instant settlement network.'],
            ['url' => 'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?w=1200&auto=format&fit=crop&q=80', 'title' => 'Institutional Portfolio Reporting', 'caption' => 'Comprehensive analytics suite for angel syndicates and venture funds.']
        ];
    } else {
        $logo = !empty($company['logo_url']) ? $company['logo_url'] : 'https://images.unsplash.com/photo-1551434678-e076c223a692?w=1200&auto=format&fit=crop&q=80';
        return [
            ['url' => $logo, 'title' => htmlspecialchars($company['name']) . ' Innovation Showcase', 'caption' => htmlspecialchars($company['pitch'] ?? 'Pioneering innovative solutions.')],
            ['url' => 'https://images.unsplash.com/photo-1522071820081-009f0129c71c?w=1200&auto=format&fit=crop&q=80', 'title' => 'Founding Team Collaboration', 'caption' => 'Agile team executing product milestones with high velocity.'],
            ['url' => 'https://images.unsplash.com/photo-1504384308090-c894fdcc538d?w=1200&auto=format&fit=crop&q=80', 'title' => 'Technology Architecture', 'caption' => 'Robust, enterprise-grade architecture designed for scale.'],
            ['url' => 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=1200&auto=format&fit=crop&q=80', 'title' => 'Market Traction & Milestones', 'caption' => 'Sustainable growth metrics with validated customer demand.']
        ];
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
        body,
        html:not(.dark) body {
            font-family: "Vay Portal", Sans-serif;
            background-color: #F4F2EE !important;
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

<body class="bg-[#F4F2EE] dark:bg-[#0B1120] text-slate-900 dark:text-slate-100 flex min-h-screen">

    <!-- Investor Navigation Sidebar -->
    <?php include __DIR__ . '/../includes/investor/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0">
        <!-- Top Navbar -->
        <?php include __DIR__ . '/../includes/investor/navbar.php'; ?>

        <main class="w-full max-w-7xl mx-auto p-4 sm:p-6 lg:p-8 space-y-6" id="discover-main">

            <!-- ========================================================
                 1. LINKEDIN-STYLE PLATFORM BANNER & MARKET STATUS
                 ======================================================== -->
            <div
                class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 sm:p-6 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-5">
                <div class="space-y-1.5 max-w-2xl">
                    <div class="flex items-center space-x-2">
                        <span
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold badge-linkedin">
                            <i data-lucide="shield-check" class="w-3.5 h-3.5 mr-1"></i>
                            SEBI & MCA Verified Dealflow
                        </span>
                        <span class="text-xs text-slate-500 dark:text-slate-400 font-semibold">• Live Syndicate
                            Allocations</span>
                    </div>
                    <h1
                        class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight leading-tight">
                        Discover High-Growth Startups & Syndicates
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                        Back top-tier founders raising institutional capital under audited MCA due diligence and
                        RBI-regulated escrow custody.
                    </p>
                </div>

                <div class="flex items-center gap-3 sm:gap-4 flex-shrink-0">
                    <div
                        class="bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700/60 rounded-xl px-4 py-2.5 text-center">
                        <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                            Total Live Deals</div>
                        <div class="text-lg sm:text-xl font-black text-[#0A66C2] dark:text-blue-400">
                            <?= count($startups) ?> Ventures</div>
                    </div>
                    <div
                        class="bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700/60 rounded-xl px-4 py-2.5 text-center">
                        <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                            Active Capital</div>
                        <div class="text-lg sm:text-xl font-black text-emerald-600 dark:text-emerald-400">
                            <?= format_inr($totalMarketCapital) ?></div>
                    </div>
                </div>
            </div>

            <!-- ========================================================
                 2. LINKEDIN-STYLE HORIZONTAL FILTER PILLS & SEARCH BAR
                 ======================================================== -->
            <div
                class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 sm:p-5 shadow-xs space-y-4">
                <!-- Search Input Bar with Quick Filter Controls -->
                <form action="<?= url('investor/discover.php') ?>" method="GET" class="space-y-3">
                    <div class="flex flex-col md:flex-row items-center gap-3">
                        <div class="relative flex-1 w-full">
                            <i data-lucide="search"
                                class="w-4.5 h-4.5 text-slate-400 dark:text-slate-500 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                            <input type="text" name="q" value="<?= htmlspecialchars($search) ?>"
                                placeholder="Search by startup name, founder, industry keywords, tech stack..."
                                class="w-full pl-10 pr-10 py-3 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-slate-100 text-xs sm:text-sm font-medium outline-none focus:bg-white dark:focus:bg-slate-900 focus:border-[#0A66C2] dark:focus:border-blue-400 focus:ring-2 focus:ring-[#0A66C2]/15 transition">
                            <?php if (!empty($search)): ?>
                                <a href="<?= url('investor/discover.php') ?>"
                                    class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-white">
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
                                    <option value="<?= $ind ?>" <?= $industry === $ind ? 'selected' : '' ?>><?= $ind ?>
                                        <?= isset($industryCounts[$ind]) ? "({$industryCounts[$ind]})" : '' ?></option>
                                <?php endforeach; ?>
                            </select>

                            <select name="stage" onchange="this.form.submit()"
                                class="w-1/2 md:w-40 px-3.5 py-3 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-800 dark:text-slate-200 text-xs font-semibold outline-none focus:border-[#0A66C2]">
                                <option value="">All Stages</option>
                                <?php foreach (['Idea / MVP', 'Pre-Seed', 'Seed', 'Pre-Series A', 'Series A'] as $stg): ?>
                                    <option value="<?= $stg ?>" <?= $stage === $stg ? 'selected' : '' ?>><?= $stg ?>
                                        <?= isset($stageCounts[$stg]) ? "({$stageCounts[$stg]})" : '' ?></option>
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
                <div
                    class="flex items-center space-x-2 overflow-x-auto no-scrollbar pt-1 border-t border-slate-100 dark:border-slate-800 text-xs">
                    <span
                        class="text-[11px] font-extrabold uppercase text-slate-400 dark:text-slate-500 tracking-wider whitespace-nowrap mr-1">Trending:</span>

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
                        if (!empty($search))
                            $chipUrl .= '&q=' . urlencode($search);
                        ?>
                        <a href="<?= $chipUrl ?>"
                            class="px-3.5 py-1.5 rounded-full font-bold whitespace-nowrap transition-all duration-150 flex items-center space-x-1.5 <?= $isActive ? 'bg-[#0A66C2] text-white shadow-xs' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' ?>">
                            <span><?= $chip['label'] ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- ========================================================
                 3. DEALFLOW FEED
                 ======================================================== -->
            <div class="space-y-5">

                <?php if (empty($startups)): ?>
                    <!-- Empty State -->
                    <div class="linkedin-card rounded-2xl p-12 text-center">
                        <div
                            class="w-14 h-14 rounded-full bg-blue-50 dark:bg-blue-950/60 text-[#0A66C2] dark:text-blue-400 flex items-center justify-center mx-auto mb-3.5">
                            <i data-lucide="search-x" class="w-7 h-7"></i>
                        </div>
                        <h3 class="text-base font-extrabold text-slate-900 dark:text-white mb-1">No Matching Syndicates
                            Found</h3>
                        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 max-w-md mx-auto leading-relaxed">
                            We couldn't find any active venture deals matching your query. Try broadening your industry or
                            stage filters.
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

                        <!-- SIMPLIFIED VENTURE SHOWCASE CARD -->
                        <article class="linkedin-card rounded-2xl overflow-hidden p-5 sm:p-6 space-y-4 relative">

                            <!-- Company Header: Logo, Name, Verified Badge, Industry, Stage & Bookmark -->
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-center space-x-3.5 min-w-0">
                                    <a href="<?= url('investor/startup_detail.php?id=' . $hashId) ?>" class="flex-shrink-0 group">
                                        <img src="<?= $s['logo_url'] ?: 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=120' ?>"
                                            class="w-12 h-12 sm:w-14 sm:h-14 rounded-2xl object-cover border border-slate-200 dark:border-slate-700 shadow-2xs group-hover:scale-105 transition-transform"
                                            alt="<?= htmlspecialchars($s['name']) ?>">
                                    </a>
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2 mb-0.5">
                                            <a href="<?= url('investor/startup_detail.php?id=' . $hashId) ?>"
                                                class="text-lg sm:text-xl font-extrabold text-slate-900 dark:text-white hover:text-[#0A66C2] dark:hover:text-blue-400 transition tracking-tight truncate">
                                                <?= htmlspecialchars($s['name']) ?>
                                            </a>
                                            <span class="inline-flex items-center text-[#0A66C2] dark:text-blue-400" title="MCA & SEBI Verified">
                                                <i data-lucide="badge-check" class="w-4.5 h-4.5 fill-[#0A66C2]/15"></i>
                                            </span>
                                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                                <?= htmlspecialchars($s['industry']) ?>
                                            </span>
                                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-blue-50 dark:bg-blue-950/60 text-[#0A66C2] dark:text-blue-300">
                                                <?= htmlspecialchars($s['stage']) ?>
                                            </span>
                                        </div>
                                        <p class="text-xs sm:text-sm font-medium text-slate-600 dark:text-slate-300 line-clamp-1">
                                            <?= htmlspecialchars($s['pitch']) ?>
                                        </p>
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

                            <!-- Showcase Images Section: Big Image + Beside Thumbnails with 2+ Badge -->
                            <?php
                            $gallery = get_company_gallery_images($s);
                            $galleryJson = htmlspecialchars(json_encode([
                                'company_name' => $s['name'],
                                'logo_url' => $s['logo_url'] ?: 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=120',
                                'detail_url' => url('investor/startup_detail.php?id=' . $hashId),
                                'images' => $gallery
                            ]), ENT_QUOTES, 'UTF-8');
                            $bigImg = $gallery[0];
                            $thumb1 = $gallery[1] ?? null;
                            $thumb2 = $gallery[2] ?? null;
                            $remainingCount = count($gallery) - 2;
                            ?>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-2.5 rounded-xl overflow-hidden group/gallery">
                                <!-- Big Main Image -->
                                <div class="md:col-span-2 relative h-56 sm:h-64 md:h-72 rounded-xl overflow-hidden bg-slate-100 dark:bg-slate-800 cursor-pointer"
                                     onclick='openGalleryModal(<?= $galleryJson ?>, 0)'>
                                    <img src="<?= htmlspecialchars($bigImg['url']) ?>" 
                                         alt="<?= htmlspecialchars($bigImg['title']) ?>"
                                         class="w-full h-full object-cover transition-transform duration-500 hover:scale-[1.02]">
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/10 to-transparent pointer-events-none"></div>
                                    <div class="absolute bottom-3 left-3 right-3 text-white pointer-events-none">
                                        <span class="inline-flex items-center space-x-1 px-2 py-0.5 rounded-md bg-black/60 backdrop-blur-xs text-[10px] font-bold uppercase tracking-wider text-slate-200 mb-1">
                                            <i data-lucide="sparkles" class="w-3 h-3 text-amber-400"></i>
                                            <span>Featured Showcase</span>
                                        </span>
                                        <h4 class="text-sm sm:text-base font-bold text-white drop-shadow-xs line-clamp-1">
                                            <?= htmlspecialchars($bigImg['title']) ?>
                                        </h4>
                                    </div>
                                    <div class="absolute top-3 right-3 bg-black/60 text-white text-[11px] font-semibold px-2 py-1 rounded-lg backdrop-blur-xs flex items-center space-x-1 pointer-events-none">
                                        <i data-lucide="maximize-2" class="w-3.5 h-3.5"></i>
                                        <span>Click to View</span>
                                    </div>
                                </div>

                                <!-- Beside Side Thumbnails with 2+ Badge -->
                                <div class="grid grid-cols-2 md:grid-cols-1 gap-2.5">
                                    <?php if ($thumb1): ?>
                                        <div class="relative h-28 sm:h-32 md:h-[139px] rounded-xl overflow-hidden bg-slate-100 dark:bg-slate-800 cursor-pointer"
                                             onclick='openGalleryModal(<?= $galleryJson ?>, 1)'>
                                            <img src="<?= htmlspecialchars($thumb1['url']) ?>" 
                                                 alt="<?= htmlspecialchars($thumb1['title']) ?>"
                                                 class="w-full h-full object-cover transition-transform duration-300 hover:scale-105">
                                            <div class="absolute inset-0 bg-black/10 hover:bg-transparent transition-colors"></div>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($thumb2): ?>
                                        <div class="relative h-28 sm:h-32 md:h-[139px] rounded-xl overflow-hidden bg-slate-100 dark:bg-slate-800 cursor-pointer"
                                             onclick='openGalleryModal(<?= $galleryJson ?>, 2)'>
                                            <img src="<?= htmlspecialchars($thumb2['url']) ?>" 
                                                 alt="<?= htmlspecialchars($thumb2['title']) ?>"
                                                 class="w-full h-full object-cover">
                                            <!-- 2+ Overlay Badge to View All Multi-Pic / Multicap Images -->
                                            <div class="absolute inset-0 bg-slate-900/70 hover:bg-slate-900/80 transition-all flex flex-col items-center justify-center text-white backdrop-blur-[1px] p-2 text-center">
                                                <span class="text-2xl sm:text-3xl font-black tracking-tight text-white leading-none">+<?= max(2, $remainingCount) ?></span>
                                                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-200 flex items-center gap-1 mt-1">
                                                    <i data-lucide="images" class="w-3.5 h-3.5 text-blue-400"></i>
                                                    <span>More Photos</span>
                                                </span>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Simplified Action Buttons -->
                            <div class="flex flex-wrap items-center justify-between gap-3 pt-1 border-t border-slate-100 dark:border-slate-800/80">
                                <div class="flex items-center space-x-2.5">
                                    <!-- Read Company Details Button (Opens Full Details page) -->
                                    <a href="<?= url('investor/startup_detail.php?id=' . $hashId) ?>"
                                        class="px-5 py-2.5 rounded-xl bg-[#0A66C2] hover:bg-[#004182] text-white text-xs sm:text-sm font-bold transition flex items-center space-x-2 shadow-sm">
                                        <span>Read Company Details</span>
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
                                            class="px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 hover:bg-blue-50 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:text-[#0A66C2] dark:hover:text-blue-400 text-xs font-bold transition flex items-center space-x-1.5">
                                            <i data-lucide="message-circle" class="w-4 h-4 text-[#0A66C2] dark:text-blue-400"></i>
                                            <span class="hidden sm:inline">Founder Chat</span>
                                        </a>
                                    <?php endif; ?>

                                    <button type="button"
                                        onclick="copyDealLink('<?= url('investor/startup_detail.php?id=' . $hashId) ?>')"
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
        </main>
    </div>

    <!-- Interactive Multi-Photo Lightbox Modal -->
    <div id="companyGalleryModal" class="fixed inset-0 z-50 hidden items-center justify-center p-3 sm:p-5 bg-black/85 backdrop-blur-md transition-opacity duration-200">
        <div class="relative w-full max-w-4xl bg-slate-900 rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[92vh] border border-slate-700">
            <!-- Modal Header -->
            <div class="flex items-center justify-between px-5 py-3.5 border-b border-slate-800 bg-slate-950/80">
                <div class="flex items-center space-x-3 min-w-0">
                    <img id="galleryModalLogo" src="" class="w-9 h-9 rounded-xl object-cover border border-slate-700 flex-shrink-0" alt="">
                    <div class="min-w-0">
                        <h4 id="galleryModalCompany" class="text-sm sm:text-base font-bold text-white truncate"></h4>
                        <p id="galleryModalCounter" class="text-[11px] text-slate-400 font-medium"></p>
                    </div>
                </div>
                <div class="flex items-center space-x-2.5 flex-shrink-0">
                    <a id="galleryModalDetailsLink" href="#" class="px-4 py-2 rounded-xl bg-[#0A66C2] hover:bg-[#004182] text-white text-xs font-bold transition flex items-center space-x-1.5 shadow-sm">
                        <span>Read Full Details</span>
                        <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                    </a>
                    <button type="button" onclick="closeGalleryModal()" class="p-2 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 transition cursor-pointer">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
            </div>

            <!-- Modal Active Image Stage -->
            <div class="relative flex-1 bg-black flex items-center justify-center min-h-[300px] max-h-[58vh] overflow-hidden select-none">
                <img id="galleryModalMainImg" src="" alt="" class="max-w-full max-h-[58vh] object-contain transition-all duration-300">

                <!-- Previous / Next Navigation Arrows -->
                <button type="button" onclick="prevGalleryImage()" 
                    class="absolute left-3 top-1/2 -translate-y-1/2 p-2.5 sm:p-3 rounded-full bg-slate-900/70 hover:bg-slate-900 text-white transition backdrop-blur-xs cursor-pointer shadow-lg">
                    <i data-lucide="chevron-left" class="w-5 h-5"></i>
                </button>
                <button type="button" onclick="nextGalleryImage()" 
                    class="absolute right-3 top-1/2 -translate-y-1/2 p-2.5 sm:p-3 rounded-full bg-slate-900/70 hover:bg-slate-900 text-white transition backdrop-blur-xs cursor-pointer shadow-lg">
                    <i data-lucide="chevron-right" class="w-5 h-5"></i>
                </button>

                <!-- Caption / Multicap Info Overlay -->
                <div class="absolute bottom-0 inset-x-0 bg-gradient-to-t from-black/90 via-black/50 to-transparent p-4 text-white">
                    <h5 id="galleryModalTitle" class="text-sm sm:text-base font-bold text-white"></h5>
                    <p id="galleryModalCaption" class="text-xs text-slate-300 mt-0.5 line-clamp-2"></p>
                </div>
            </div>

            <!-- Bottom Thumbnails Strip -->
            <div id="galleryModalThumbs" class="flex items-center space-x-2.5 p-3.5 bg-slate-950 overflow-x-auto border-t border-slate-800/80">
            </div>
        </div>
    </div>

    <!-- Deal Link Copied Toast -->
    <div id="toast-deal-link"
        class="fixed bottom-6 right-6 z-50 transform translate-y-20 opacity-0 transition-all duration-300 bg-slate-900 text-white text-xs font-bold px-4 py-3 rounded-xl shadow-xl flex items-center space-x-2">
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

        // Gallery Modal Logic
        let activeGallery = null;
        let activeGalleryIndex = 0;

        function openGalleryModal(galleryData, startIndex = 0) {
            activeGallery = galleryData;
            activeGalleryIndex = startIndex;
            
            document.getElementById('galleryModalCompany').textContent = galleryData.company_name;
            document.getElementById('galleryModalLogo').src = galleryData.logo_url;
            document.getElementById('galleryModalDetailsLink').href = galleryData.detail_url;

            renderGalleryModalState();

            const modal = document.getElementById('companyGalleryModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';
            lucide.createIcons();
        }

        function closeGalleryModal() {
            const modal = document.getElementById('companyGalleryModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.style.overflow = '';
            activeGallery = null;
        }

        function renderGalleryModalState() {
            if (!activeGallery || !activeGallery.images || !activeGallery.images.length) return;
            const item = activeGallery.images[activeGalleryIndex];
            
            document.getElementById('galleryModalMainImg').src = item.url;
            document.getElementById('galleryModalTitle').textContent = item.title;
            document.getElementById('galleryModalCaption').textContent = item.caption;
            document.getElementById('galleryModalCounter').textContent = `Photo ${activeGalleryIndex + 1} of ${activeGallery.images.length}`;

            // Render Thumbnails
            const thumbsContainer = document.getElementById('galleryModalThumbs');
            thumbsContainer.innerHTML = '';
            activeGallery.images.forEach((img, idx) => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = `w-14 h-14 rounded-lg overflow-hidden flex-shrink-0 border-2 transition cursor-pointer ${idx === activeGalleryIndex ? 'border-[#0A66C2] scale-105 opacity-100' : 'border-transparent opacity-50 hover:opacity-100'}`;
                btn.onclick = () => {
                    activeGalleryIndex = idx;
                    renderGalleryModalState();
                };
                btn.innerHTML = `<img src="${img.url}" class="w-full h-full object-cover" alt="">`;
                thumbsContainer.appendChild(btn);
            });
            lucide.createIcons();
        }

        function nextGalleryImage() {
            if (!activeGallery || !activeGallery.images.length) return;
            activeGalleryIndex = (activeGalleryIndex + 1) % activeGallery.images.length;
            renderGalleryModalState();
        }

        function prevGalleryImage() {
            if (!activeGallery || !activeGallery.images.length) return;
            activeGalleryIndex = (activeGalleryIndex - 1 + activeGallery.images.length) % activeGallery.images.length;
            renderGalleryModalState();
        }

        // Close on backdrop click & Keyboard Navigation
        document.getElementById('companyGalleryModal')?.addEventListener('click', (e) => {
            if (e.target.id === 'companyGalleryModal') {
                closeGalleryModal();
            }
        });

        document.addEventListener('keydown', (e) => {
            const modal = document.getElementById('companyGalleryModal');
            if (modal && !modal.classList.contains('hidden')) {
                if (e.key === 'Escape') closeGalleryModal();
                if (e.key === 'ArrowRight') nextGalleryImage();
                if (e.key === 'ArrowLeft') prevGalleryImage();
            }
        });
    </script>
</body>

</html>