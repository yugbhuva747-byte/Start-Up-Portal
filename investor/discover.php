<?php
/**
 * Investor Module: Startup Discovery & Deal Flow Feed
 * Upgraded: funding metrics, progress bars, sorting, pagination, sector rail
 */
require_once __DIR__ . '/../config.php';
$user = require_auth('investor');
$db = get_db();
$pageTitle = 'Discover Startups & Deal Flow';

/* ------------------------------------------------------------------
 * 1. Watchlist toggle (handled FIRST, before any output)
 * ------------------------------------------------------------------ */
if ($db && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle_watchlist') {
    if (verify_csrf($_POST['csrf_token'] ?? '')) {
        $targetCompId = hash_id_decode($_POST['company_id'] ?? '');
        if ($targetCompId > 0) {
            $chk = $db->prepare("SELECT id FROM watchlists WHERE investor_user_id = ? AND company_id = ?");
            $chk->execute([$user['id'], $targetCompId]);
            if ($chk->fetch()) {
                $db->prepare("DELETE FROM watchlists WHERE investor_user_id = ? AND company_id = ?")->execute([$user['id'], $targetCompId]);
            } else {
                $db->prepare("INSERT INTO watchlists (investor_user_id, company_id) VALUES (?, ?)")->execute([$user['id'], $targetCompId]);
            }
        }
    }
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

/* ------------------------------------------------------------------
 * 2. Filters (navbar sends ?search=, page sends ?q= -> both work)
 * ------------------------------------------------------------------ */
$search = trim($_GET['q'] ?? ($_GET['search'] ?? ''));
$industry = trim($_GET['industry'] ?? '');
$stage = trim($_GET['stage'] ?? '');
$location = trim($_GET['location'] ?? '');
$tab = trim($_GET['tab'] ?? 'all');
$sort = trim($_GET['sort'] ?? 'active');
$minFunding = (float) ($_GET['min_funding'] ?? 0);
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 6;

$startups = [];
$watchlistIds = [];
$industryCounts = [];
$stageCounts = [];
$totalMarketCapital = 0;
$totalRaisedCapital = 0;

/** Build a discover URL keeping current filters, overriding some. null = remove */
function discover_link(array $override = []): string
{
    $q = array_merge($_GET, $override);
    $q = array_filter($q, fn($v) => $v !== null && $v !== '');
    return url('investor/discover.php') . (empty($q) ? '' : '?' . http_build_query($q));
}

if ($db) {
    // Watchlist IDs
    $wlStmt = $db->prepare("SELECT company_id FROM watchlists WHERE investor_user_id = ?");
    $wlStmt->execute([$user['id']]);
    $watchlistIds = $wlStmt->fetchAll(PDO::FETCH_COLUMN);

    // Sidebar / dropdown counts
    $indStmt = $db->query("SELECT c.industry, COUNT(*) AS cnt FROM companies c WHERE c.verified_status = 'verified' GROUP BY c.industry");
    if ($indStmt)
        $industryCounts = $indStmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $stgStmt = $db->query("SELECT c.stage, COUNT(*) AS cnt FROM companies c WHERE c.verified_status = 'verified' GROUP BY c.stage");
    if ($stgStmt)
        $stageCounts = $stgStmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // One row per company: best round + first founder (no duplicates)
    $query = "
        SELECT c.*,
               fr.id AS round_id, fr.round_name, fr.target_amount, fr.amount_raised, fr.min_investment,
               fr.valuation, fr.equity_offered, fr.status AS round_status, fr.end_date, fr.purpose,
               u.id AS founder_user_id, u.name AS founder_name, u.avatar_url AS founder_avatar,
               cf.designation AS founder_designation,
               (SELECT COUNT(*) FROM watchlists w WHERE w.company_id = c.id) AS saves_count,
               (SELECT COUNT(*) FROM company_documents d WHERE d.company_id = c.id AND d.document_type <> 'Gallery Photo') AS docs_count
        FROM companies c
        LEFT JOIN funding_rounds fr ON fr.id = (
            SELECT fr2.id FROM funding_rounds fr2
            WHERE fr2.company_id = c.id
              AND fr2.status IN ('LIVE','PARTIALLY_FUNDED','FULLY_FUNDED','CLOSED')
            ORDER BY (fr2.status IN ('LIVE','PARTIALLY_FUNDED')) DESC, fr2.id DESC
            LIMIT 1
        )
        LEFT JOIN company_founders cf ON cf.company_id = c.id
            AND cf.user_id = (SELECT MIN(cf2.user_id) FROM company_founders cf2 WHERE cf2.company_id = c.id)
        LEFT JOIN users u ON u.id = cf.user_id
        WHERE c.verified_status = 'verified'
    ";
    $params = [];

    // Tab filter
    if ($tab === 'saved') {
        if (!empty($watchlistIds)) {
            $query .= " AND c.id IN (" . implode(',', array_fill(0, count($watchlistIds), '?')) . ")";
            $params = array_merge($params, $watchlistIds);
        } else {
            $query .= " AND 1=0";
        }
    } elseif ($tab === 'trending') {
        $query .= " AND fr.amount_raised > 0";
    } elseif ($tab === 'seed') {
        $query .= " AND (c.stage LIKE '%Seed%')";
    } elseif ($tab === 'series_a') {
        $query .= " AND (c.stage LIKE '%Series A%')";
    } elseif ($tab === 'ai') {
        $query .= " AND c.industry = 'AI/SaaS'";
    } elseif ($tab === 'fintech') {
        $query .= " AND c.industry = 'FinTech'";
    } elseif ($tab === 'healthtech') {
        $query .= " AND c.industry = 'HealthTech'";
    } elseif ($tab === 'cleantech') {
        $query .= " AND c.industry = 'CleanTech'";
    }

    if ($search !== '') {
        $query .= " AND (c.name LIKE ? OR c.pitch LIKE ? OR c.description LIKE ? OR u.name LIKE ? OR c.industry LIKE ?)";
        $sTerm = "%{$search}%";
        array_push($params, $sTerm, $sTerm, $sTerm, $sTerm, $sTerm);
    }
    if ($industry !== '') {
        $query .= " AND c.industry = ?";
        $params[] = $industry;
    }
    if ($stage !== '') {
        $query .= " AND c.stage = ?";
        $params[] = $stage;
    }
    if ($location !== '') {
        $query .= " AND (c.city LIKE ? OR c.state LIKE ?)";
        $params[] = "%{$location}%";
        $params[] = "%{$location}%";
    }
    if ($minFunding > 0) {
        $query .= " AND fr.target_amount >= ?";
        $params[] = $minFunding;
    }

    $orderBy = match ($sort) {
        'newest' => 'c.created_at DESC',
        'target' => 'fr.target_amount DESC',
        'ending' => '(fr.end_date IS NULL), fr.end_date ASC',
        default => "(fr.status IN ('LIVE','PARTIALLY_FUNDED')) DESC, fr.amount_raised DESC, c.created_at DESC",
    };
    $query .= " ORDER BY $orderBy";

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $startups = $stmt->fetchAll();

    foreach ($startups as $st) {
        $totalMarketCapital += (float) ($st['target_amount'] ?? 0);
        $totalRaisedCapital += (float) ($st['amount_raised'] ?? 0);
    }
}

// Pagination
$totalDeals = count($startups);
$totalPages = max(1, (int) ceil($totalDeals / $perPage));
$page = min($page, $totalPages);
$pageStartups = array_slice($startups, ($page - 1) * $perPage, $perPage);

// Real photos uploaded by startups (company_documents, type = 'Gallery Photo')
$companyImages = [];
if ($db && !empty($pageStartups)) {
    $ids = array_column($pageStartups, 'id');
    $in = implode(',', array_fill(0, count($ids), '?'));
    $imgStmt = $db->prepare("
        SELECT company_id, title, file_path
        FROM company_documents
        WHERE document_type = 'Gallery Photo'
          AND access_level IN ('public','registered_investors')
          AND company_id IN ($in)
        ORDER BY id ASC
    ");
    $imgStmt->execute($ids);
    foreach ($imgStmt->fetchAll() as $img) {
        $path = $img['file_path'];
        $companyImages[$img['company_id']][] = [
            'url' => preg_match('#^https?://#i', $path) ? $path : url($path),
            'title' => $img['title'] ?: 'Company Photo',
            'caption' => '',
        ];
    }
}

/**
 * Gallery images. Uses the startup's own cover image first (if column exists),
 * then curated fallbacks. Replace with real uploaded startup photos when available.
 */
function get_company_gallery_images($company, array $real = [])
{
    if (!empty($real)) {
        return $real; // real uploads win; fake placeholders below are only for empty companies
    }
    $industry = strtolower($company['industry'] ?? '');
    $compName = strtolower($company['name'] ?? '');
    $own = [];
    if (!empty($company['cover_url'])) {
        $own[] = ['url' => $company['cover_url'], 'title' => ($company['name'] ?? 'Company') . ' — Cover', 'caption' => $company['pitch'] ?? ''];
    }

    if (strpos($compName, 'techpulse') !== false || strpos($industry, 'ai') !== false || strpos($industry, 'saas') !== false) {
        $set = [
            ['url' => 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=1200&auto=format&fit=crop&q=80', 'title' => 'Enterprise AI Risk & Compliance Platform', 'caption' => 'Multi-agent LLM risk assessment pipeline automated for BFSI enterprises.'],
            ['url' => 'https://images.unsplash.com/photo-1519389950473-47ba0277781c?w=1200&auto=format&fit=crop&q=80', 'title' => 'Core AI Engineering Team', 'caption' => 'Engineering hub building neural compliance pipelines.'],
            ['url' => 'https://images.unsplash.com/photo-1451187580459-43490279c0fa?w=1200&auto=format&fit=crop&q=80', 'title' => 'Scalable Cloud Infrastructure', 'caption' => 'Multi-tenant high-throughput inference cluster with enterprise-grade SLA.'],
            ['url' => 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=1200&auto=format&fit=crop&q=80', 'title' => 'Real-Time Financial Dashboard', 'caption' => 'Audit metrics and executive summaries in sub-second latency.'],
        ];
    } elseif (strpos($compName, 'biozenith') !== false || strpos($industry, 'health') !== false || strpos($industry, 'bio') !== false) {
        $set = [
            ['url' => 'https://images.unsplash.com/photo-1576086213369-97a306d36557?w=1200&auto=format&fit=crop&q=80', 'title' => 'Advanced Diagnostics Laboratory', 'caption' => 'Non-invasive micro-spectroscopy clinical diagnostics workstation.'],
            ['url' => 'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?w=1200&auto=format&fit=crop&q=80', 'title' => 'Point-of-Care Micro-Spectrometer', 'caption' => '3-minute portable blood biomarker detection system.'],
            ['url' => 'https://images.unsplash.com/photo-1532187863486-abf9dbad1b69?w=1200&auto=format&fit=crop&q=80', 'title' => 'Hospital Trial Validation', 'caption' => 'Clinical validation underway across leading hospital chains.'],
            ['url' => 'https://images.unsplash.com/photo-1579684385127-1ef15d508118?w=1200&auto=format&fit=crop&q=80', 'title' => 'Instant Cloud Telemetry Sync', 'caption' => 'Encrypted diagnostic reports delivered to doctors and patients.'],
        ];
    } elseif (strpos($compName, 'solaris') !== false || strpos($industry, 'clean') !== false || strpos($industry, 'ev') !== false || strpos($industry, 'mobility') !== false) {
        $set = [
            ['url' => 'https://images.unsplash.com/photo-1508873696983-2df5293cb32f?w=1200&auto=format&fit=crop&q=80', 'title' => 'Automated Solar Swapping Station', 'caption' => 'Rapid 90-second battery swap hub for commercial delivery fleets.'],
            ['url' => 'https://images.unsplash.com/photo-1558981403-c5f9899a28bc?w=1200&auto=format&fit=crop&q=80', 'title' => 'Commercial EV Fleet Deployment', 'caption' => '120+ active electric delivery fleets using the hubs daily.'],
            ['url' => 'https://images.unsplash.com/photo-1497435334941-8c899ee9e8e9?w=1200&auto=format&fit=crop&q=80', 'title' => 'Solar Microgrid Architecture', 'caption' => 'Zero-carbon energy storage and intelligent charging grid.'],
            ['url' => 'https://images.unsplash.com/photo-1513836279014-a89f7a76ae86?w=1200&auto=format&fit=crop&q=80', 'title' => 'IoT Battery Management Telemetry', 'caption' => 'Live health monitoring, thermal safety and cycle-life optimization.'],
        ];
    } elseif (strpos($industry, 'fintech') !== false) {
        $set = [
            ['url' => 'https://images.unsplash.com/photo-1559526324-4b87b5e36e44?w=1200&auto=format&fit=crop&q=80', 'title' => 'Next-Gen Financial Rails', 'caption' => 'Unified payment infrastructure with real-time settlements.'],
            ['url' => 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=1200&auto=format&fit=crop&q=80', 'title' => 'Algorithmic Risk Engine', 'caption' => 'AI-driven underwriting and merchant risk scoring.'],
            ['url' => 'https://images.unsplash.com/photo-1563986768609-322da13575f3?w=1200&auto=format&fit=crop&q=80', 'title' => 'Connected Merchant Terminal', 'caption' => 'Contactless POS and instant settlement network.'],
            ['url' => 'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?w=1200&auto=format&fit=crop&q=80', 'title' => 'Institutional Portfolio Reporting', 'caption' => 'Analytics suite for angel syndicates and venture funds.'],
        ];
    } else {
        $logo = !empty($company['logo_url']) ? $company['logo_url'] : 'https://images.unsplash.com/photo-1551434678-e076c223a692?w=1200&auto=format&fit=crop&q=80';
        $set = [
            ['url' => $logo, 'title' => ($company['name'] ?? 'Company') . ' Innovation Showcase', 'caption' => $company['pitch'] ?? 'Pioneering innovative solutions.'],
            ['url' => 'https://images.unsplash.com/photo-1522071820081-009f0129c71c?w=1200&auto=format&fit=crop&q=80', 'title' => 'Founding Team Collaboration', 'caption' => 'Agile team executing product milestones.'],
            ['url' => 'https://images.unsplash.com/photo-1504384308090-c894fdcc538d?w=1200&auto=format&fit=crop&q=80', 'title' => 'Technology Architecture', 'caption' => 'Enterprise-grade architecture designed for scale.'],
            ['url' => 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=1200&auto=format&fit=crop&q=80', 'title' => 'Market Traction & Milestones', 'caption' => 'Sustainable growth with validated customer demand.'],
        ];
    }
    return array_merge($own, $set);
}

$fallbackLogo = 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=120';
$roundLabels = [
    'LIVE' => ['Accepting Capital', 'emerald'],
    'PARTIALLY_FUNDED' => ['Partially Funded', 'blue'],
    'FULLY_FUNDED' => ['Fully Funded', 'slate'],
    'CLOSED' => ['Round Closed', 'slate'],
];
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
            font-family: 'Satoshi', system-ui, -apple-system, sans-serif;
            background-color: #F4F2EE !important;
            color: #0F172A;
        }

        html.dark body {
            background-color: #0B0F19 !important;
            color: #F8FAFC !important;
        }

        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }

        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
    </style>
</head>

<body
    class="bg-[#F4F2EE] dark:bg-[#0B0F19] text-slate-900 dark:text-slate-100 flex min-h-screen antialiased selection:bg-indigo-500 selection:text-white">

    <?php include __DIR__ . '/../includes/investor/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0">
        <?php include __DIR__ . '/../includes/investor/navbar.php'; ?>

        <main class="w-full max-w-[1440px] mx-auto px-3 sm:px-6 md:px-8 py-5 space-y-5" id="discover-main">

            <!-- 1. HERO / MARKET STATUS (Matching Founder Feed Design) -->
            <div
                class="rounded-3xl p-5 sm:p-6 bg-white dark:bg-[#111827] border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col lg:flex-row lg:items-center justify-between gap-5">
                <div class="space-y-1.5 max-w-2xl">
                    <div class="flex items-center flex-wrap gap-2">
                        <span
                            class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-extrabold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200/80 dark:border-indigo-800/80">
                            <i data-lucide="shield-check" class="w-3.5 h-3.5 text-indigo-600"></i>
                            SEBI &amp; MCA Verified Dealflow
                        </span>
                        <span class="text-xs text-slate-500 dark:text-slate-400 font-semibold">• Live Syndicate
                            Allocations</span>
                    </div>
                    <h1
                        class="text-lg sm:text-xl font-extrabold text-slate-900 dark:text-white tracking-tight leading-tight">
                        Discover High-Growth Startups &amp; Deal Flow
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 leading-relaxed font-normal">
                        Back verified founders raising capital under institutional due diligence, transparent traction
                        metrics, and escrow-secured investor syndicate protections.
                    </p>
                </div>
            </div>

            <!-- 2. SEARCH + FILTERS (Sticky on desktop, matching Founder Feed inputs) -->
            <div
                class="lg:sticky lg:top-20 z-20 rounded-2xl p-4 sm:p-5 bg-white dark:bg-[#111827] border border-slate-200 dark:border-slate-800 shadow-sm">
                <form action="<?= url('investor/discover.php') ?>" method="GET">
                    <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
                    <div class="flex flex-col lg:flex-row items-stretch gap-3">
                        <div class="relative flex-1">
                            <i data-lucide="search"
                                class="w-4 h-4 text-slate-400 dark:text-slate-500 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                            <input type="text" name="q" value="<?= htmlspecialchars($search) ?>"
                                placeholder="Search startup, founder, industry, keyword..."
                                class="w-full pl-10 pr-10 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-slate-100 text-xs sm:text-sm font-medium outline-none focus:bg-white dark:focus:bg-slate-900 focus:border-indigo-500 dark:focus:border-indigo-400 focus:ring-2 focus:ring-indigo-500/15 transition">
                            <?php if ($search !== ''): ?>
                                <a href="<?= discover_link(['q' => null, 'search' => null, 'page' => null]) ?>"
                                    class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-white"
                                    title="Clear search">
                                    <i data-lucide="x" class="w-4 h-4"></i>
                                </a>
                            <?php endif; ?>
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-4 lg:flex items-stretch gap-2">
                            <select name="industry" onchange="this.form.submit()"
                                class="px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-800 dark:text-slate-200 text-xs font-semibold outline-none focus:border-indigo-500">
                                <option value="">All Industries</option>
                                <?php foreach (['AI/SaaS', 'FinTech', 'HealthTech', 'CleanTech', 'DeepTech', 'E-Commerce', 'EdTech'] as $ind): ?>
                                    <option value="<?= $ind ?>" <?= $industry === $ind ? 'selected' : '' ?>>
                                        <?= $ind ?>     <?= isset($industryCounts[$ind]) ? " ({$industryCounts[$ind]})" : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <select name="stage" onchange="this.form.submit()"
                                class="px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-800 dark:text-slate-200 text-xs font-semibold outline-none focus:border-indigo-500">
                                <option value="">All Stages</option>
                                <?php foreach (['Idea / MVP', 'Pre-Seed', 'Seed', 'Pre-Series A', 'Series A'] as $stg): ?>
                                    <option value="<?= $stg ?>" <?= $stage === $stg ? 'selected' : '' ?>>
                                        <?= $stg ?>     <?= isset($stageCounts[$stg]) ? " ({$stageCounts[$stg]})" : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <select name="sort" onchange="this.form.submit()"
                                class="px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-800 dark:text-slate-200 text-xs font-semibold outline-none focus:border-indigo-500">
                                <option value="active" <?= $sort === 'active' ? 'selected' : '' ?>>Sort: Most Active
                                </option>
                                <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Sort: Newest</option>
                                <option value="target" <?= $sort === 'target' ? 'selected' : '' ?>>Sort: Biggest Round
                                </option>
                                <option value="ending" <?= $sort === 'ending' ? 'selected' : '' ?>>Sort: Closing Soon
                                </option>
                            </select>

                            <button type="submit"
                                class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl transition flex items-center justify-center gap-1.5 shadow-sm cursor-pointer">
                                <i data-lucide="sliders" class="w-4 h-4"></i>
                                <span>Apply</span>
                            </button>
                        </div>

                    </div>
                </form>
            </div>


            <!-- 3. FEED + RIGHT RAIL (Matching Founder Feed Layout) -->
            <div class="flex flex-col lg:flex-row gap-6 items-start">

                <!-- FEED -->
                <div class="flex-1 min-w-0 space-y-5" id="deal-feed">

                    <div
                        class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 font-semibold px-1">
                        <span>Showing
                            <?= $totalDeals ? (($page - 1) * $perPage + 1) : 0 ?>–<?= min($page * $perPage, $totalDeals) ?>
                            of <?= $totalDeals ?> startups</span>
                        <?php if ($search !== '' || $industry !== '' || $stage !== '' || $tab !== 'all'): ?>
                            <a href="<?= url('investor/discover.php') ?>"
                                class="text-indigo-600 dark:text-indigo-400 hover:underline flex items-center space-x-1">
                                <i data-lucide="rotate-ccw" class="w-3 h-3"></i><span>Reset filters</span>
                            </a>
                        <?php endif; ?>
                    </div>

                    <?php if (empty($pageStartups)): ?>
                        <div
                            class="rounded-3xl p-12 bg-white dark:bg-[#111827] border border-slate-200 dark:border-slate-800 text-center space-y-3">
                            <i data-lucide="search-x" class="w-12 h-12 text-slate-300 dark:text-slate-600 mx-auto"></i>
                            <h3 class="text-base font-bold text-slate-800 dark:text-slate-200">No Matching Startups Found
                            </h3>
                            <p class="text-xs text-slate-500 max-w-sm mx-auto">Try a broader keyword search, or reset the
                                industry and funding stage filters.</p>
                            <a href="<?= url('investor/discover.php') ?>"
                                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-indigo-600 text-white font-bold text-xs shadow-md hover:bg-indigo-700 transition">
                                <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                                <span>Reset All Filters</span>
                            </a>
                        </div>
                    <?php else: ?>

                        <?php foreach ($pageStartups as $s):
                            $hashId = hash_id_encode($s['id']);
                            $detailUrl = url('founder/company_view.php?id=' . $hashId);
                            $isSaved = in_array($s['id'], $watchlistIds);
                            $targetAmount = (float) ($s['target_amount'] ?? 0);
                            $raisedAmount = (float) ($s['amount_raised'] ?? 0);
                            $pct = $targetAmount > 0 ? min(100, (int) round(($raisedAmount / $targetAmount) * 100)) : 0;
                            $roundHash = !empty($s['round_id']) ? hash_id_encode($s['round_id']) : '';
                            $founderHash = !empty($s['founder_user_id']) ? hash_id_encode($s['founder_user_id']) : '';
                            $roundStatus = $s['round_status'] ?? '';
                            $isLiveRound = in_array($roundStatus, ['LIVE', 'PARTIALLY_FUNDED'], true);
                            $hasRound = !empty($s['round_id']);
                            [$statusText, $statusColor] = $roundLabels[$roundStatus] ?? ['No Active Round', 'slate'];
                            $daysLeft = !empty($s['end_date']) ? (int) ceil((strtotime($s['end_date']) - time()) / 86400) : null;
                            $logo = !empty($s['logo_url']) ? $s['logo_url'] : $fallbackLogo;
                            $gallery = get_company_gallery_images($s, $companyImages[$s['id']] ?? []);
                            $galleryData = [
                                'company_name' => $s['name'],
                                'logo_url' => $logo,
                                'detail_url' => $detailUrl,
                                'images' => $gallery,
                            ];
                            $city = trim(($s['city'] ?? '') . (!empty($s['state']) ? ', ' . $s['state'] : ''), ', ');
                            ?>
                            <!-- Single LinkedIn Style Deal / Post Card (Matching Founder Feed) -->
                            <article id="deal-<?= $s['id'] ?>"
                                class="deal-card rounded-3xl bg-white dark:bg-[#111827] border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden space-y-4 p-5 sm:p-6 transition hover:border-slate-300 dark:hover:border-slate-700"
                                data-gallery="<?= htmlspecialchars(json_encode($galleryData), ENT_QUOTES, 'UTF-8') ?>"
                                data-link="<?= htmlspecialchars($detailUrl, ENT_QUOTES, 'UTF-8') ?>">

                                <!-- Header: Logo, Startup Name, Verified Badge, Subtitle & Status Pill -->
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex items-center gap-3.5 min-w-0">
                                        <!-- Startup Logo -->
                                        <a href="<?= $detailUrl ?>" class="flex-shrink-0">
                                            <img src="<?= htmlspecialchars($logo) ?>" loading="lazy"
                                                onerror="this.onerror=null;this.src='<?= $fallbackLogo ?>'"
                                                alt="<?= htmlspecialchars($s['name']) ?>"
                                                class="w-12 h-12 rounded-2xl border border-slate-200 dark:border-slate-700 object-cover object-center shadow-xs hover:scale-105 transition">
                                        </a>

                                        <div class="min-w-0">
                                            <div class="flex items-center gap-1.5 flex-wrap">
                                                <a href="<?= $detailUrl ?>"
                                                    class="font-extrabold text-sm sm:text-base text-slate-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 transition truncate">
                                                    <?= htmlspecialchars($s['name']) ?>
                                                </a>
                                                <i data-lucide="badge-check" class="w-4 h-4 text-indigo-600 flex-shrink-0"
                                                    title="MCA &amp; SEBI Verified Deal"></i>
                                            </div>
                                            <p class="text-[11.5px] text-slate-500 dark:text-slate-400 truncate">
                                                Led by <?php if (!empty($founderHash)): ?>
                                                    <a href="<?= url('founder/view.php?id=' . $founderHash) ?>" class="font-bold text-slate-800 dark:text-slate-200 hover:text-indigo-600 dark:hover:text-indigo-400 hover:underline"><?= htmlspecialchars($s['founder_name'] ?? 'Founding Team') ?></a>
                                                <?php else: ?>
                                                    <?= htmlspecialchars($s['founder_name'] ?? 'Founding Team') ?>
                                                <?php endif; ?>
                                                (<?= htmlspecialchars($s['founder_designation'] ?? 'Founder') ?>) •
                                                <?= htmlspecialchars($s['stage']) ?>        <?php if ($city): ?> •
                                                    <?= htmlspecialchars($city) ?>        <?php endif; ?>
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Category & Status Badge -->
                                    <div class="flex items-center gap-2 flex-shrink-0">
                                        <?php if (!empty($s['founder_user_id']) && $s['founder_user_id'] != $user['id']): ?>
                                            <?= render_connect_button((int) $s['founder_user_id'], [
                                                'name' => $s['founder_name'] ?? 'Founder',
                                                'headline' => ($s['founder_designation'] ?? 'Founder') . ' at ' . $s['name'],
                                                'avatar' => get_avatar_url($s['founder_avatar'] ?? ''),
                                                'class' => 'text-[11px] py-1 px-2.5 inline-flex'
                                            ]) ?>
                                        <?php endif; ?>

                                        <span
                                            class="px-3 py-1 rounded-full text-xs font-extrabold flex items-center gap-1.5 <?= $isLiveRound ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200/80 dark:border-emerald-800/80' : 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200/80 dark:border-indigo-800/80' ?>">
                                            <?php if ($isLiveRound): ?>
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                            <?php endif; ?>
                                            <?= htmlspecialchars($s['industry']) ?> • <?= $statusText ?>
                                        </span>
                                    </div>
                                </div>

                                <!-- Pitch Headline & Content Body -->
                                <div class="space-y-2.5">
                                    <h3 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white leading-snug">
                                        <a href="<?= $detailUrl ?>"
                                            class="hover:text-indigo-600 dark:hover:text-indigo-400 transition">
                                            <?= htmlspecialchars($s['name']) ?> —
                                            <?= htmlspecialchars($s['pitch'] ?? 'Pioneering innovative solutions with validated product-market fit.') ?>
                                        </a>
                                    </h3>

                                    <?php if (!empty($s['description'])): ?>
                                        <p
                                            class="text-xs sm:text-sm text-slate-700 dark:text-slate-300 leading-relaxed font-normal whitespace-pre-line line-clamp-3">
                                            <?= htmlspecialchars($s['description']) ?>
                                        </p>
                                    <?php endif; ?>

                                    <!-- Traction Metrics Pill (Matching Founder Feed) -->
                                    <div class="flex flex-wrap items-center gap-2 pt-0.5">
                                        <div
                                            class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 text-xs font-bold">
                                            <i data-lucide="trending-up" class="w-3.5 h-3.5 text-emerald-600"></i>
                                            <span><?= $isLiveRound ? 'Round Active: ' . format_inr($raisedAmount) . ' Committed' : 'Verified Venture • ' . htmlspecialchars($s['stage']) ?></span>
                                        </div>
                                        <?php if ($hasRound): ?>
                                            <div
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-indigo-50 dark:bg-indigo-950/40 text-indigo-800 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 text-xs font-bold">
                                                <i data-lucide="circle-dollar-sign" class="w-3.5 h-3.5 text-indigo-600"></i>
                                                <span>Target: <?= format_inr($targetAmount) ?> (<?= $pct ?>% Raised)</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Elevated Funding Progress & Key Metrics Box -->
                                <?php if ($hasRound): ?>
                                    <div
                                        class="rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200/80 dark:border-slate-800 p-4 space-y-3">
                                        <div class="flex items-end justify-between gap-3">
                                            <div>
                                                <div
                                                    class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                                    <?= htmlspecialchars($s['round_name'] ?? 'Syndicate Funding Round') ?>
                                                </div>
                                                <div
                                                    class="text-base sm:text-lg font-black text-slate-900 dark:text-white leading-tight">
                                                    <?= format_inr($raisedAmount) ?>
                                                    <span
                                                        class="text-xs sm:text-sm font-semibold text-slate-500 dark:text-slate-400">raised
                                                        of <?= format_inr($targetAmount) ?></span>
                                                </div>
                                            </div>
                                            <div class="text-right">
                                                <div class="text-lg sm:text-xl font-black text-indigo-600 dark:text-indigo-400">
                                                    <?= $pct ?>%</div>
                                            </div>
                                        </div>
                                        <div class="h-2 rounded-full bg-slate-200 dark:bg-slate-700 overflow-hidden">
                                            <div class="h-full rounded-full bg-gradient-to-r from-indigo-600 to-emerald-500 transition-all duration-500"
                                                style="width: <?= $pct ?>%"></div>
                                        </div>
                                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                            <div
                                                class="p-2.5 rounded-xl bg-white dark:bg-[#111827] border border-slate-200/80 dark:border-slate-800">
                                                <span
                                                    class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Min.
                                                    Ticket</span>
                                                <span
                                                    class="text-xs sm:text-sm font-extrabold text-slate-900 dark:text-white"><?= !empty($s['min_investment']) ? format_inr($s['min_investment']) : '—' ?></span>
                                            </div>
                                            <div
                                                class="p-2.5 rounded-xl bg-white dark:bg-[#111827] border border-slate-200/80 dark:border-slate-800">
                                                <span
                                                    class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Valuation</span>
                                                <span
                                                    class="text-xs sm:text-sm font-extrabold text-slate-900 dark:text-white"><?= !empty($s['valuation']) ? format_inr($s['valuation']) : '—' ?></span>
                                            </div>
                                            <div
                                                class="p-2.5 rounded-xl bg-white dark:bg-[#111827] border border-slate-200/80 dark:border-slate-800">
                                                <span
                                                    class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Equity</span>
                                                <span
                                                    class="text-xs sm:text-sm font-extrabold text-slate-900 dark:text-white"><?= isset($s['equity_offered']) && $s['equity_offered'] !== '' ? htmlspecialchars(rtrim(rtrim(number_format((float) $s['equity_offered'], 2), '0'), '.')) . '%' : '—' ?></span>
                                            </div>
                                            <div
                                                class="p-2.5 rounded-xl bg-white dark:bg-[#111827] border border-slate-200/80 dark:border-slate-800">
                                                <span
                                                    class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Closing</span>
                                                <span
                                                    class="text-xs sm:text-sm font-extrabold <?= ($daysLeft !== null && $daysLeft <= 7 && $daysLeft > 0) ? 'text-rose-600 dark:text-rose-400' : 'text-slate-900 dark:text-white' ?>">
                                                    <?= $daysLeft === null ? '—' : ($daysLeft > 0 ? $daysLeft . ' days left' : 'Closed') ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Post Media Image (Matching Founder Feed full-width preview) -->
                                <?php if (!empty($gallery[0]['url'])): ?>
                                    <div class="rounded-2xl overflow-hidden max-h-96 border border-slate-100 dark:border-slate-800 bg-slate-900 cursor-pointer relative group"
                                        onclick="openGalleryFromCard(this, 0)">
                                        <img src="<?= htmlspecialchars($gallery[0]['url']) ?>" loading="lazy"
                                            alt="<?= htmlspecialchars($s['name']) ?>"
                                            class="w-full h-full max-h-96 object-cover object-center group-hover:scale-101 transition duration-500">
                                        <div
                                            class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent pointer-events-none">
                                        </div>
                                        <div
                                            class="absolute bottom-3 left-4 right-4 flex items-center justify-between text-white pointer-events-none">
                                            <span
                                                class="text-xs font-bold drop-shadow truncate max-w-[280px]"><?= htmlspecialchars($gallery[0]['title'] ?? $s['name']) ?></span>
                                            <span
                                                class="px-2.5 py-1 rounded-lg bg-black/60 backdrop-blur-xs text-[11px] font-semibold flex items-center gap-1.5">
                                                <i data-lucide="images" class="w-3.5 h-3.5 text-indigo-400"></i>
                                                <span>View <?= count($gallery) ?> Photos</span>
                                            </span>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Post Social Proof & Due Diligence Stats Bar -->
                                <div
                                    class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 pt-2 border-t border-slate-100 dark:border-slate-800/80 font-medium">
                                    <div class="flex items-center gap-1.5">
                                        <span
                                            class="w-5 h-5 rounded-full bg-indigo-600 text-white flex items-center justify-center shadow-xs">
                                            <i data-lucide="bookmark" class="w-2.5 h-2.5 fill-white text-white"></i>
                                        </span>
                                        <span><?= (int) $s['saves_count'] ?> investors tracking</span>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <span class="flex items-center gap-1">
                                            <i data-lucide="file-text" class="w-3.5 h-3.5 text-slate-400"></i>
                                            <?= (int) $s['docs_count'] ?> pitch docs
                                        </span>
                                        <span class="flex items-center gap-1">
                                            <i data-lucide="shield-check" class="w-3.5 h-3.5 text-indigo-500"></i>
                                            Verified
                                        </span>
                                    </div>
                                </div>

                                <!-- Interactive Action Buttons (Matching Founder Feed 4-Grid Layout) -->
                                <div
                                    class="grid grid-cols-2 sm:grid-cols-4 gap-2 pt-2 border-t border-slate-100 dark:border-slate-800/80">
                                    <!-- 1. Save / Bookmark Toggle -->
                                    <form action="<?= url('investor/discover.php') ?>" method="POST" class="w-full">
                                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                        <input type="hidden" name="action" value="toggle_watchlist">
                                        <input type="hidden" name="company_id" value="<?= $hashId ?>">
                                        <button type="submit"
                                            class="w-full py-2 px-2 rounded-xl text-xs font-bold transition flex items-center justify-center gap-1.5 cursor-pointer <?= $isSaved ? 'text-amber-600 bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/60' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 border border-transparent' ?>">
                                            <i data-lucide="bookmark"
                                                class="w-4 h-4 <?= $isSaved ? 'fill-amber-500 text-amber-500' : '' ?>"></i>
                                            <span><?= $isSaved ? 'Saved' : 'Save' ?></span>
                                        </button>
                                    </form>

                                    <!-- 2. Company Profile / Deal Room -->
                                    <a href="<?= $detailUrl ?>"
                                        class="w-full py-2 px-2 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition flex items-center justify-center gap-1.5 border border-transparent">
                                        <i data-lucide="building-2" class="w-4 h-4 text-indigo-500"></i>
                                        <span>Company Profile</span>
                                    </a>

                                    <!-- 3. Chat with Founder / Share -->
                                    <?php if ($founderHash): ?>
                                        <a href="<?= url('investor/messages.php?founder=' . $founderHash . '&company=' . $hashId) ?>"
                                            class="w-full py-2 px-2 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition flex items-center justify-center gap-1.5 border border-transparent">
                                            <i data-lucide="message-circle" class="w-4 h-4 text-indigo-500"></i>
                                            <span>Chat Founder</span>
                                        </a>
                                    <?php else: ?>
                                        <button type="button"
                                            onclick="copyDealLink('<?= htmlspecialchars($detailUrl, ENT_QUOTES, 'UTF-8') ?>')"
                                            class="w-full py-2 px-2 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition flex items-center justify-center gap-1.5 border border-transparent cursor-pointer">
                                            <i data-lucide="share-2" class="w-4 h-4 text-indigo-500"></i>
                                            <span>Share Deal</span>
                                        </button>
                                    <?php endif; ?>

                                    <!-- 4. Invest Now CTA Button -->
                                    <?php if ($isLiveRound && $roundHash): ?>
                                        <a href="<?= url('investor/invest.php?round=' . $roundHash) ?>"
                                            class="w-full py-2 px-2 rounded-xl text-xs font-black bg-emerald-600 hover:bg-emerald-700 text-white transition flex items-center justify-center gap-1.5 shadow-sm">
                                            <i data-lucide="coins" class="w-3.5 h-3.5 text-white"></i>
                                            <span>Invest Now</span>
                                        </a>
                                    <?php else: ?>
                                        <a href="<?= $detailUrl ?>"
                                            class="w-full py-2 px-2 rounded-xl text-xs font-black bg-indigo-50 dark:bg-indigo-950/50 hover:bg-indigo-100 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 transition flex items-center justify-center gap-1.5">
                                            <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                                            <span>Explore</span>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>

                        <!-- Pagination (Clean Rounded Buttons) -->
                        <?php if ($totalPages > 1): ?>
                            <nav class="flex items-center justify-center flex-wrap gap-2 pt-2" aria-label="Pagination">
                                <?php if ($page > 1): ?>
                                    <a href="<?= discover_link(['page' => $page - 1]) ?>"
                                        class="px-3.5 py-2 rounded-xl bg-white dark:bg-[#111827] border border-slate-200 dark:border-slate-800 text-xs font-bold text-slate-700 dark:text-slate-200 hover:border-indigo-500 transition">
                                        ← Prev
                                    </a>
                                <?php endif; ?>
                                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                                    <a href="<?= discover_link(['page' => $p]) ?>"
                                        class="w-9 h-9 flex items-center justify-center rounded-xl text-xs font-bold transition <?= $p === $page ? 'bg-indigo-600 text-white shadow-xs' : 'bg-white dark:bg-[#111827] border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-200 hover:border-indigo-500' ?>"><?= $p ?></a>
                                <?php endfor; ?>
                                <?php if ($page < $totalPages): ?>
                                    <a href="<?= discover_link(['page' => $page + 1]) ?>"
                                        class="px-3.5 py-2 rounded-xl bg-white dark:bg-[#111827] border border-slate-200 dark:border-slate-800 text-xs font-bold text-slate-700 dark:text-slate-200 hover:border-indigo-500 transition">
                                        Next →
                                    </a>
                                <?php endif; ?>
                            </nav>
                        <?php endif; ?>

                    <?php endif; ?>
                </div>

                <!-- RIGHT RAIL (Matching Founder Feed Sidebar Design with Read More toggles) -->
                <aside
                    class="w-full lg:w-[280px] xl:w-[300px] flex-shrink-0 lg:sticky lg:top-24 lg:self-start space-y-3.5">
                    <!-- Browse by Sector Card -->
                    <div
                        class="rounded-2xl p-4 bg-white dark:bg-[#111827] border border-slate-200 dark:border-slate-800 shadow-sm space-y-3">
                        <div
                            class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2">
                            <h3 class="text-xs font-extrabold text-slate-900 dark:text-white flex items-center gap-1.5">
                                <i data-lucide="layers" class="w-3.5 h-3.5 text-indigo-600"></i>
                                <span>Browse by Sector</span>
                            </h3>
                            <span class="text-[10.5px] text-indigo-600 font-bold"><?= count($industryCounts) ?>
                                Sectors</span>
                        </div>

                        <div class="space-y-1.5" id="sector-list-container">
                            <?php
                            $indIndex = 0;
                            foreach ($industryCounts as $ind => $cnt):
                                $isExtra = $indIndex >= 2;
                                $indIndex++;
                                ?>
                                <a href="<?= discover_link(['industry' => $ind, 'tab' => null, 'page' => null]) ?>"
                                    class="<?= $isExtra ? 'sector-extra-item hidden' : '' ?> flex items-center justify-between px-3 py-2 rounded-xl text-xs font-bold transition <?= $industry === $ind ? 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800' : 'text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800/60' ?>">
                                    <span class="truncate"><?= htmlspecialchars($ind) ?></span>
                                    <span
                                        class="px-2 py-0.5 rounded-full text-[10px] <?= $industry === $ind ? 'bg-indigo-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-500' ?>"><?= (int) $cnt ?></span>
                                </a>
                            <?php endforeach; ?>
                            <?php if (empty($industryCounts)): ?>
                                <p class="text-xs text-slate-400 py-1">No sectors recorded yet.</p>
                            <?php endif; ?>
                        </div>

                        <?php if (count($industryCounts) > 2): ?>
                            <button type="button" id="btn-toggle-sectors" onclick="toggleSectorList()"
                                class="w-full py-1.5 px-3 rounded-xl bg-slate-50 dark:bg-slate-800/80 hover:bg-indigo-50 dark:hover:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 text-xs font-bold transition flex items-center justify-center gap-1.5 border border-slate-200/60 dark:border-slate-700/60 cursor-pointer shadow-2xs">
                                <i data-lucide="chevrons-down" class="w-3.5 h-3.5 text-indigo-500"
                                    id="icon-toggle-sectors"></i>
                                <span id="text-toggle-sectors">Read More (+<?= count($industryCounts) - 2 ?>)</span>
                            </button>
                        <?php endif; ?>
                    </div>

                    <!-- Browse by Stage Card -->
                    <div
                        class="rounded-2xl p-4 bg-white dark:bg-[#111827] border border-slate-200 dark:border-slate-800 shadow-sm space-y-3">
                        <div
                            class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2">
                            <h3 class="text-xs font-extrabold text-slate-900 dark:text-white flex items-center gap-1.5">
                                <i data-lucide="rocket" class="w-3.5 h-3.5 text-indigo-600"></i>
                                <span>Venture Stages</span>
                            </h3>
                            <span class="text-[10.5px] text-slate-400 font-bold"><?= count($stageCounts) ?></span>
                        </div>

                        <div class="flex flex-wrap gap-1.5" id="stage-list-container">
                            <?php
                            $stgIndex = 0;
                            foreach ($stageCounts as $stg => $cnt):
                                $isExtraStg = $stgIndex >= 3;
                                $stgIndex++;
                                ?>
                                <a href="<?= discover_link(['stage' => $stg, 'tab' => null, 'page' => null]) ?>"
                                    class="<?= $isExtraStg ? 'stage-extra-item hidden' : '' ?> px-2.5 py-1 rounded-xl text-[11px] font-bold transition <?= $stage === $stg ? 'bg-indigo-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-indigo-50 hover:text-indigo-600 dark:hover:bg-slate-700' ?>">
                                    <?= htmlspecialchars($stg) ?> · <?= (int) $cnt ?>
                                </a>
                            <?php endforeach; ?>
                        </div>

                        <?php if (count($stageCounts) > 3): ?>
                            <button type="button" id="btn-toggle-stages" onclick="toggleStageList()"
                                class="w-full py-1.5 px-3 rounded-xl bg-slate-50 dark:bg-slate-800/80 hover:bg-indigo-50 dark:hover:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 text-xs font-bold transition flex items-center justify-center gap-1.5 border border-slate-200/60 dark:border-slate-700/60 cursor-pointer shadow-2xs">
                                <i data-lucide="chevrons-down" class="w-3.5 h-3.5 text-indigo-500"
                                    id="icon-toggle-stages"></i>
                                <span id="text-toggle-stages">Read More (+<?= count($stageCounts) - 3 ?>)</span>
                            </button>
                        <?php endif; ?>
                    </div>

                    <!-- How Investing Works Card -->
                    <div
                        class="rounded-2xl p-4 bg-white dark:bg-[#111827] border border-slate-200 dark:border-slate-800 shadow-sm space-y-3">
                        <h3
                            class="text-xs font-extrabold text-slate-900 dark:text-white flex items-center gap-1.5 border-b border-slate-100 dark:border-slate-800 pb-2">
                            <i data-lucide="shield-check" class="w-3.5 h-3.5 text-indigo-600"></i>
                            <span>Institutional Protections</span>
                        </h3>
                        <ol class="space-y-2.5 text-xs text-slate-600 dark:text-slate-300">
                            <li class="flex items-start gap-2.5">
                                <span
                                    class="w-5 h-5 rounded-full bg-indigo-600 text-white text-[10px] font-black flex items-center justify-center flex-shrink-0 mt-0.5 shadow-2xs">1</span>
                                <span class="leading-relaxed">Audit the data room, audited pitch decks, and verified
                                    capitalization tables.</span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <span
                                    class="w-5 h-5 rounded-full bg-indigo-600 text-white text-[10px] font-black flex items-center justify-center flex-shrink-0 mt-0.5 shadow-2xs">2</span>
                                <span class="leading-relaxed">Direct messaging with verified founders to discuss
                                    syndication and terms.</span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <span
                                    class="w-5 h-5 rounded-full bg-emerald-600 text-white text-[10px] font-black flex items-center justify-center flex-shrink-0 mt-0.5 shadow-2xs">3</span>
                                <span class="leading-relaxed">Escrow-backed investment flow with digital legal
                                    signatures and automated share allocation.</span>
                            </li>
                        </ol>
                    </div>
                </aside>
            </div>
        </main>
    </div>

    <!-- Gallery Lightbox -->
    <div id="companyGalleryModal"
        class="fixed inset-0 z-[60] hidden items-center justify-center p-3 sm:p-5 bg-black/85 backdrop-blur-md">
        <div
            class="relative w-full max-w-4xl bg-slate-900 rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[92vh] border border-slate-700">
            <div class="flex items-center justify-between px-5 py-3.5 border-b border-slate-800 bg-slate-950/80">
                <div class="flex items-center space-x-3 min-w-0">
                    <img id="galleryModalLogo" src=""
                        class="w-9 h-9 rounded-xl object-cover border border-slate-700 flex-shrink-0" alt="">
                    <div class="min-w-0">
                        <h4 id="galleryModalCompany" class="text-sm sm:text-base font-bold text-white truncate"></h4>
                        <p id="galleryModalCounter" class="text-[11px] text-slate-400 font-medium"></p>
                    </div>
                </div>
                <div class="flex items-center space-x-2.5 flex-shrink-0">
                    <a id="galleryModalDetailsLink" href="#"
                        class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold transition flex items-center space-x-1.5 shadow-sm">
                        <span>View Deal Room</span><i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                    </a>
                    <button type="button" onclick="closeGalleryModal()"
                        class="p-2 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 transition cursor-pointer"
                        aria-label="Close"><i data-lucide="x" class="w-5 h-5"></i></button>
                </div>
            </div>
            <div
                class="relative flex-1 bg-black flex items-center justify-center min-h-[300px] max-h-[58vh] overflow-hidden select-none">
                <img id="galleryModalMainImg" src="" alt="" class="max-w-full max-h-[58vh] object-contain">
                <button type="button" onclick="prevGalleryImage()"
                    class="absolute left-3 top-1/2 -translate-y-1/2 p-2.5 rounded-full bg-slate-900/70 hover:bg-slate-900 text-white transition cursor-pointer shadow-lg"
                    aria-label="Previous"><i data-lucide="chevron-left" class="w-5 h-5"></i></button>
                <button type="button" onclick="nextGalleryImage()"
                    class="absolute right-3 top-1/2 -translate-y-1/2 p-2.5 rounded-full bg-slate-900/70 hover:bg-slate-900 text-white transition cursor-pointer shadow-lg"
                    aria-label="Next"><i data-lucide="chevron-right" class="w-5 h-5"></i></button>
                <div
                    class="absolute bottom-0 inset-x-0 bg-gradient-to-t from-black/90 via-black/50 to-transparent p-4 text-white">
                    <h5 id="galleryModalTitle" class="text-sm sm:text-base font-bold"></h5>
                    <p id="galleryModalCaption" class="text-xs text-slate-300 mt-0.5 line-clamp-2"></p>
                </div>
            </div>
            <div id="galleryModalThumbs"
                class="flex items-center space-x-2.5 p-3.5 bg-slate-950 overflow-x-auto border-t border-slate-800/80">
            </div>
        </div>
    </div>

    <!-- Toast -->
    <div id="toast-deal-link"
        class="fixed bottom-6 right-6 z-[70] transform translate-y-20 opacity-0 transition-all duration-300 bg-slate-900 text-white text-xs font-bold px-4 py-3 rounded-xl shadow-xl flex items-center space-x-2">
        <i data-lucide="check-circle" class="w-4 h-4 text-emerald-400"></i>
        <span>Deal link copied to clipboard!</span>
    </div>

    <script>
        if (window.lucide) lucide.createIcons();

        // Entrance animation
        (function () {
            const cards = document.querySelectorAll('.deal-card');
            if (!window.gsap || !cards.length) return;
            gsap.fromTo(cards, { y: 14, opacity: 0 },
                { y: 0, opacity: 1, duration: 0.45, stagger: 0.07, ease: 'power2.out', clearProps: 'opacity,transform' });
            setTimeout(() => cards.forEach(c => { if (getComputedStyle(c).opacity < 1) { c.style.opacity = 1; c.style.transform = 'none'; } }), 2000);
        })();

        function copyDealLink(link) {
            const full = new URL(link, window.location.href).href;
            navigator.clipboard.writeText(full).then(() => {
                const toast = document.getElementById('toast-deal-link');
                if (!toast) return;
                toast.classList.remove('translate-y-20', 'opacity-0');
                setTimeout(() => toast.classList.add('translate-y-20', 'opacity-0'), 2500);
            });
        }

        // Sector List Read More Toggle
        let sectorsExpanded = false;
        function toggleSectorList() {
            sectorsExpanded = !sectorsExpanded;
            const items = document.querySelectorAll('.sector-extra-item');
            items.forEach(el => el.classList.toggle('hidden', !sectorsExpanded));
            const txt = document.getElementById('text-toggle-sectors');
            const icon = document.getElementById('icon-toggle-sectors');
            if (txt) txt.textContent = sectorsExpanded ? 'Show Less' : 'Read More (+' + items.length + ')';
            if (icon) icon.setAttribute('data-lucide', sectorsExpanded ? 'chevrons-up' : 'chevrons-down');
            if (window.lucide) lucide.createIcons();
        }

        // Stage List Read More Toggle
        let stagesExpanded = false;
        function toggleStageList() {
            stagesExpanded = !stagesExpanded;
            const items = document.querySelectorAll('.stage-extra-item');
            items.forEach(el => el.classList.toggle('hidden', !stagesExpanded));
            const txt = document.getElementById('text-toggle-stages');
            const icon = document.getElementById('icon-toggle-stages');
            if (txt) txt.textContent = stagesExpanded ? 'Show Less' : 'Read More (+' + items.length + ')';
            if (icon) icon.setAttribute('data-lucide', stagesExpanded ? 'chevrons-up' : 'chevrons-down');
            if (window.lucide) lucide.createIcons();
        }

        // Gallery modal
        let activeGallery = null;
        let activeGalleryIndex = 0;

        function openGalleryFromCard(el, index) {
            const card = el.closest('article');
            if (!card) return;
            try { openGalleryModal(JSON.parse(card.dataset.gallery), index); } catch (e) { console.error(e); }
        }

        function openGalleryModal(data, startIndex = 0) {
            activeGallery = data;
            activeGalleryIndex = startIndex;
            document.getElementById('galleryModalCompany').textContent = data.company_name;
            document.getElementById('galleryModalLogo').src = data.logo_url;
            document.getElementById('galleryModalDetailsLink').href = data.detail_url;
            renderGalleryModalState();
            const modal = document.getElementById('companyGalleryModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';
            if (window.lucide) lucide.createIcons();
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
            document.getElementById('galleryModalCaption').textContent = item.caption || '';
            document.getElementById('galleryModalCounter').textContent = `Photo ${activeGalleryIndex + 1} of ${activeGallery.images.length}`;

            const strip = document.getElementById('galleryModalThumbs');
            strip.innerHTML = '';
            activeGallery.images.forEach((img, idx) => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = `w-14 h-14 rounded-lg overflow-hidden flex-shrink-0 border-2 transition cursor-pointer ${idx === activeGalleryIndex ? 'border-indigo-500 opacity-100 ring-2 ring-indigo-500/30' : 'border-transparent opacity-50 hover:opacity-100'}`;
                btn.onclick = () => { activeGalleryIndex = idx; renderGalleryModalState(); };
                const im = document.createElement('img');
                im.src = img.url; im.alt = ''; im.className = 'w-full h-full object-cover';
                btn.appendChild(im);
                strip.appendChild(btn);
            });
        }

        function nextGalleryImage() {
            if (!activeGallery) return;
            activeGalleryIndex = (activeGalleryIndex + 1) % activeGallery.images.length;
            renderGalleryModalState();
        }
        function prevGalleryImage() {
            if (!activeGallery) return;
            activeGalleryIndex = (activeGalleryIndex - 1 + activeGallery.images.length) % activeGallery.images.length;
            renderGalleryModalState();
        }

        document.getElementById('companyGalleryModal')?.addEventListener('click', (e) => {
            if (e.target.id === 'companyGalleryModal') closeGalleryModal();
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