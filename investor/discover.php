<?php
/**
 * Investor Module: Startup Discovery & Structured Search
 * Clean White / Light Theme, Small Crisp Typography, Professional Look
 */
require_once __DIR__ . '/../config.php';
$user = require_auth('investor');
$db = get_db();
$pageTitle = 'Discover Startups & Deal Flow';

$search = trim($_GET['q'] ?? '');
$industry = trim($_GET['industry'] ?? '');
$stage = trim($_GET['stage'] ?? '');
$location = trim($_GET['location'] ?? '');
$minFunding = (float) ($_GET['min_funding'] ?? 0);

$startups = [];
$watchlistIds = [];

if ($db) {
    $wlStmt = $db->prepare("SELECT company_id FROM watchlists WHERE investor_user_id = ?");
    $wlStmt->execute([$user['id']]);
    $watchlistIds = $wlStmt->fetchAll(PDO::FETCH_COLUMN);

    $query = "
        SELECT c.*, 
               fr.id as round_id, fr.round_name, fr.target_amount, fr.amount_raised, fr.min_investment, fr.valuation, fr.equity_offered, fr.status as round_status,
               u.name as founder_name, u.avatar_url as founder_avatar
        FROM companies c
        LEFT JOIN funding_rounds fr ON c.id = fr.company_id
        LEFT JOIN company_founders cf ON c.id = cf.company_id
        LEFT JOIN users u ON cf.user_id = u.id
        WHERE c.verified_status = 'verified'
    ";
    $params = [];

    if (!empty($search)) {
        $query .= " AND (c.name LIKE ? OR c.pitch LIKE ? OR c.description LIKE ? OR u.name LIKE ?)";
        $sTerm = "%{$search}%";
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

    $query .= " ORDER BY fr.amount_raised DESC, c.created_at DESC";

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $startups = $stmt->fetchAll();
}

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
    <title>Discover Startups • <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background-color: #FAFAFB;
            color: #0F172A;
        }
        .card-clean {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.03);
        }
    </style>
</head>
<body class="bg-[#FAFAFB] text-slate-900 flex min-h-screen">
    
    <!-- Investor Sidebar -->
    <?php include __DIR__ . '/../includes/investor/sidebar.php'; ?>
    <div class="flex-1 flex flex-col min-w-0">
        <?php include __DIR__ . '/../includes/investor/navbar.php'; ?>

        <main class="p-4 sm:p-6 md:p-8 lg:p-10 space-y-8 sm:space-y-10 w-full mx-auto" id="discover-main">
            
            <!-- Page Identity Header -->
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 border-b border-[#E4E8EF] dark:border-slate-800 pb-6">
                <div>
                    <div class="text-xs font-bold text-[#123B7A] dark:text-blue-400 tracking-wider uppercase mb-1 flex items-center gap-2">
                        <span>Marketplace & Dealflow</span>
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 pulse-beacon"></span>
                        <span class="text-[#667085] dark:text-slate-400 font-semibold">Active Syndicates</span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl md:text-4xl font-black text-[#0B1F3A] dark:text-white tracking-tight">Discover Companies & Syndicates</h1>
                    <p class="text-sm sm:text-base text-[#667085] dark:text-slate-300 mt-1.5">Verified early-stage ventures raising active capital rounds under institutional due diligence.</p>
                </div>
                <div class="text-xs sm:text-sm text-[#667085] dark:text-slate-300 bg-white dark:bg-slate-900 border border-[#E4E8EF] dark:border-slate-800 px-4 py-2.5 rounded-xl shadow-2xs">
                    Showing <strong class="text-[#0B1F3A] dark:text-white font-black"><?= count($startups) ?></strong> verified opportunities
                </div>
            </div>

            <!-- Filter Row -->
            <div class="bg-white dark:bg-slate-900 border border-[#E4E8EF] dark:border-slate-800 rounded-2xl p-4 sm:p-6 shadow-xs">
                <form action="<?= url('investor/discover.php') ?>" method="GET" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-3.5 text-xs sm:text-sm">
                    
                    <div class="md:col-span-2 relative">
                        <i data-lucide="search" class="w-4 h-4 text-[#667085] dark:text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search startups, sectors, keywords..."
                               class="w-full pl-10 pr-3.5 py-3 bg-[#FAFBFD] dark:bg-slate-800 border border-[#E4E8EF] dark:border-slate-700 rounded-xl text-[#111827] dark:text-slate-100 outline-none focus:bg-white dark:focus:bg-slate-800 focus:border-[#123B7A] dark:focus:border-blue-400 transition text-xs sm:text-sm font-medium">
                    </div>

                    <div>
                        <select name="industry" class="w-full px-3.5 py-3 bg-[#FAFBFD] dark:bg-slate-800 border border-[#E4E8EF] dark:border-slate-700 rounded-xl text-[#111827] dark:text-slate-100 outline-none focus:bg-white dark:focus:bg-slate-800 focus:border-[#123B7A] dark:focus:border-blue-400 transition text-xs sm:text-sm font-medium">
                            <option value="">All Industries</option>
                            <?php foreach (['AI/SaaS', 'FinTech', 'HealthTech', 'CleanTech', 'DeepTech', 'E-Commerce', 'EdTech'] as $ind): ?>
                                    <option value="<?= $ind ?>" <?= $industry === $ind ? 'selected' : '' ?>><?= $ind ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <select name="stage" class="w-full px-3.5 py-3 bg-[#FAFBFD] dark:bg-slate-800 border border-[#E4E8EF] dark:border-slate-700 rounded-xl text-[#111827] dark:text-slate-100 outline-none focus:bg-white dark:focus:bg-slate-800 focus:border-[#123B7A] dark:focus:border-blue-400 transition text-xs sm:text-sm font-medium">
                            <option value="">All Stages</option>
                            <?php foreach (['Idea / MVP', 'Pre-Seed', 'Seed', 'Pre-Series A', 'Series A'] as $stg): ?>
                                    <option value="<?= $stg ?>" <?= $stage === $stg ? 'selected' : '' ?>><?= $stg ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="flex items-center space-x-2">
                        <button type="submit" class="flex-1 py-3 bg-[#123B7A] hover:bg-[#0B1F3A] dark:bg-blue-600 dark:hover:bg-blue-700 text-white font-bold rounded-xl transition flex items-center justify-center space-x-2 shadow-sm text-xs sm:text-sm cursor-pointer">
                            <i data-lucide="filter" class="w-4 h-4"></i>
                            <span>Filter Deals</span>
                        </button>
                        <?php if (!empty($search) || !empty($industry) || !empty($stage)): ?>
                            <a href="<?= url('investor/discover.php') ?>" title="Reset filters" class="p-3 bg-[#FAFBFD] dark:bg-slate-800 hover:bg-[#EAF2FF] dark:hover:bg-slate-700 border border-[#E4E8EF] dark:border-slate-700 rounded-xl text-[#667085] dark:text-slate-300 hover:text-[#123B7A] dark:hover:text-blue-400 transition flex items-center justify-center">
                                <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Opportunities List (Editorial Layout) -->
            <?php if (empty($startups)): ?>
                <div class="bg-white border border-[#E4E8EF] rounded-xl p-12 text-center text-xs text-[#667085]">
                    <i data-lucide="search-x" class="w-10 h-10 text-[#667085] mx-auto mb-3 opacity-60"></i>
                    <div class="text-sm font-bold text-[#0B1F3A] mb-1">No matching opportunities found</div>
                    <div>Try adjusting or clearing your search filters to view active syndicate rounds.</div>
                    <a href="<?= url('investor/discover.php') ?>" class="inline-block mt-4 text-[#123B7A] font-bold hover:underline">Reset Filters →</a>
                </div>
            <?php else: 
                $featuredStartup = $startups[0];
                $featPct = ($featuredStartup['target_amount'] ?? 0) > 0 ? round(($featuredStartup['amount_raised'] / $featuredStartup['target_amount']) * 100) : 0;
                $featHashId = hash_id_encode($featuredStartup['id']);
                $isFeatSaved = in_array($featuredStartup['id'], $watchlistIds);
            ?>

                <!-- 1. FEATURED OPPORTUNITY (Large Editorial Section) -->
                <section class="space-y-3">
                    <div class="text-[11px] font-bold text-[#667085] uppercase tracking-wider">
                        Featured Opportunity
                    </div>

                    <div class="bg-white border border-[#E4E8EF] rounded-xl p-6 sm:p-8 hover:border-[#123B7A]/40 transition relative">
                        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-3">
                                    <span class="px-2.5 py-0.5 rounded text-[10px] font-bold bg-[#EAF2FF] text-[#123B7A] uppercase tracking-wider">
                                        Active Round
                                    </span>
                                    <span class="text-[#667085] text-xs">•</span>
                                    <span class="text-xs font-semibold text-[#111827]"><?= htmlspecialchars($featuredStartup['industry']) ?></span>
                                    <span class="text-[#667085] text-xs">•</span>
                                    <span class="text-xs text-[#667085]"><?= htmlspecialchars($featuredStartup['stage']) ?></span>
                                    <?php if (!empty($featuredStartup['city'])): ?>
                                        <span class="text-[#667085] text-xs">•</span>
                                        <span class="text-xs text-[#667085]"><?= htmlspecialchars($featuredStartup['city']) ?></span>
                                    <?php endif; ?>
                                </div>

                                <div class="flex items-start space-x-4 mb-4">
                                    <img src="<?= $featuredStartup['logo_url'] ?: 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=120' ?>"
                                        class="w-14 h-14 rounded-xl object-cover border border-[#E4E8EF] flex-shrink-0">
                                    <div class="min-w-0">
                                        <h2 class="text-xl sm:text-2xl font-extrabold text-[#0B1F3A] tracking-tight">
                                            <?= htmlspecialchars($featuredStartup['name']) ?>
                                        </h2>
                                        <p class="text-xs sm:text-sm text-[#667085] mt-1.5 leading-relaxed max-w-2xl">
                                            <?= htmlspecialchars($featuredStartup['pitch']) ?>
                                        </p>
                                    </div>
                                </div>

                                <div class="flex flex-wrap items-center gap-6 text-xs text-[#111827] pt-2">
                                    <?php if (!empty($featuredStartup['target_amount'])): ?>
                                        <div>
                                            <span class="text-[10.5px] text-[#667085] block">Target Round</span>
                                            <span class="font-bold text-[#111827]"><?= format_inr($featuredStartup['target_amount']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($featuredStartup['valuation'])): ?>
                                        <div>
                                            <span class="text-[10.5px] text-[#667085] block">Valuation</span>
                                            <span class="font-bold text-[#111827]"><?= format_inr($featuredStartup['valuation']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($featuredStartup['min_investment'])): ?>
                                        <div>
                                            <span class="text-[10.5px] text-[#667085] block">Min Ticket</span>
                                            <span class="font-bold text-[#111827]"><?= format_inr($featuredStartup['min_investment']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="lg:w-72 flex flex-col justify-between pt-4 lg:pt-0 lg:border-l lg:border-[#E4E8EF] lg:pl-8 space-y-4">
                                <?php if (!empty($featuredStartup['target_amount'])): ?>
                                    <div>
                                        <div class="flex justify-between text-xs mb-1.5 font-bold">
                                            <span class="text-[#667085]">Raised <?= format_inr($featuredStartup['amount_raised']) ?></span>
                                            <span class="text-[#123B7A]"><?= $featPct ?>%</span>
                                        </div>
                                        <div class="w-full h-2 bg-[#E4E8EF] rounded-full overflow-hidden">
                                            <div class="h-full bg-[#123B7A] rounded-full" style="width: <?= min(100, $featPct) ?>%"></div>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <div class="flex items-center space-x-2">
                                    <a href="<?= url('investor/startup_detail.php?id=' . $featHashId) ?>"
                                        class="flex-1 py-2.5 rounded-lg bg-[#123B7A] hover:bg-[#0B1F3A] text-white text-xs font-bold text-center block transition shadow-sm">
                                        View Company Profile →
                                    </a>
                                    <form action="<?= url('investor/discover.php') ?>" method="POST" class="inline">
                                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                        <input type="hidden" name="action" value="toggle_watchlist">
                                        <input type="hidden" name="company_id" value="<?= $featHashId ?>">
                                        <button type="submit" title="<?= $isFeatSaved ? 'Remove from Saved' : 'Save Company' ?>"
                                            class="p-2.5 rounded-lg bg-[#FAFBFD] hover:bg-[#EAF2FF] border border-[#E4E8EF] transition <?= $isFeatSaved ? 'text-amber-500' : 'text-[#667085] hover:text-[#123B7A]' ?>">
                                            <i data-lucide="bookmark" class="w-4 h-4 <?= $isFeatSaved ? 'fill-amber-500' : '' ?>"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- 2. MORE OPPORTUNITIES (Clean Horizontal Rows with Separators) -->
                <?php if (count($startups) > 1): ?>
                    <section class="space-y-2">
                        <div class="text-[11px] font-bold text-[#667085] uppercase tracking-wider pb-1">
                            More Opportunities
                        </div>

                        <div class="bg-white border border-[#E4E8EF] rounded-xl divide-y divide-[#E4E8EF]">
                            <?php for ($i = 1; $i < count($startups); $i++):
                                $s = $startups[$i];
                                $pct = ($s['target_amount'] ?? 0) > 0 ? round(($s['amount_raised'] / $s['target_amount']) * 100) : 0;
                                $hashId = hash_id_encode($s['id']);
                                $isSaved = in_array($s['id'], $watchlistIds);
                            ?>
                                <div class="p-4 sm:p-5 flex flex-col md:flex-row md:items-center justify-between gap-4 hover:bg-[#FAFBFD] transition">
                                    <!-- Company Identity & Pitch -->
                                    <div class="flex items-start space-x-4 min-w-0 flex-1">
                                        <img src="<?= $s['logo_url'] ?: 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=80' ?>"
                                            class="w-12 h-12 rounded-xl object-cover border border-[#E4E8EF] flex-shrink-0">
                                        <div class="min-w-0 flex-1">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <a href="<?= url('investor/startup_detail.php?id=' . $hashId) ?>" class="text-sm font-bold text-[#0B1F3A] hover:text-[#123B7A] transition truncate">
                                                    <?= htmlspecialchars($s['name']) ?>
                                                </a>
                                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-[#FAFBFD] text-[#667085] border border-[#E4E8EF]">
                                                    <?= htmlspecialchars($s['industry']) ?>
                                                </span>
                                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-[#FAFBFD] text-[#667085] border border-[#E4E8EF]">
                                                    <?= htmlspecialchars($s['stage']) ?>
                                                </span>
                                            </div>
                                            <p class="text-xs text-[#667085] line-clamp-1 mt-1 leading-relaxed">
                                                <?= htmlspecialchars($s['pitch']) ?>
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Financial Information & Actions -->
                                    <div class="flex items-center justify-between md:justify-end gap-6 text-xs flex-shrink-0 pt-2 md:pt-0 border-t md:border-t-0 border-[#E4E8EF]">
                                        <?php if (!empty($s['target_amount'])): ?>
                                            <div class="text-left md:text-right">
                                                <span class="text-[10px] text-[#667085] block">Target</span>
                                                <span class="font-bold text-[#111827]"><?= format_inr($s['target_amount']) ?></span>
                                            </div>
                                            <div class="text-left md:text-right">
                                                <span class="text-[10px] text-[#667085] block">Raised</span>
                                                <span class="font-bold text-[#123B7A]"><?= $pct ?>%</span>
                                            </div>
                                        <?php endif; ?>

                                        <div class="flex items-center space-x-2">
                                            <a href="<?= url('investor/startup_detail.php?id=' . $hashId) ?>"
                                                class="px-4 py-2 rounded-lg bg-[#FAFBFD] hover:bg-[#EAF2FF] text-[#123B7A] hover:text-[#0B1F3A] border border-[#E4E8EF] font-bold text-xs transition">
                                                Review Deal →
                                            </a>
                                            <form action="<?= url('investor/discover.php') ?>" method="POST" class="inline">
                                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                <input type="hidden" name="action" value="toggle_watchlist">
                                                <input type="hidden" name="company_id" value="<?= $hashId ?>">
                                                <button type="submit" title="<?= $isSaved ? 'Remove from Saved' : 'Save Company' ?>"
                                                    class="p-2 rounded-lg bg-[#FAFBFD] hover:bg-[#EAF2FF] border border-[#E4E8EF] transition <?= $isSaved ? 'text-amber-500' : 'text-[#667085] hover:text-[#123B7A]' ?>">
                                                    <i data-lucide="bookmark" class="w-3.5 h-3.5 <?= $isSaved ? 'fill-amber-500' : '' ?>"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </section>
                <?php endif; ?>

            <?php endif; ?>

        </main>
    </div>

    <script>
        lucide.createIcons();
        gsap.from("#discover-main", { duration: 0.4, y: 8, opacity: 0, ease: "power2.out" });
    </script>
</body>
</html>
